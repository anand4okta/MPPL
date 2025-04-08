<?php
require_once __DIR__.'/../config/db.php';

function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generateCaptcha($type = 'register') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Hilangkan karakter ambigu
    $captcha = substr(str_shuffle($chars), 0, 6);
    $_SESSION['captcha'][$type] = [
        'code' => $captcha,
        'time' => time(),
        'attempts' => 0
    ];
    return $captcha;
}

function validateCaptcha($input, $type = 'register') {
    if (empty($_SESSION['captcha'][$type]) || empty($input)) {
        return false;
    }

    // Cek expired (90 detik)
    if (time() - $_SESSION['captcha'][$type]['time'] > 90) {
        unset($_SESSION['captcha'][$type]);
        return false;
    }

    // Cek max attempt (3x)
    if ($_SESSION['captcha'][$type]['attempts'] >= 3) {
        unset($_SESSION['captcha'][$type]);
        return false;
    }

    $_SESSION['captcha'][$type]['attempts']++;

    // Case-insensitive comparison
    $isValid = (strtoupper($input) === strtoupper($_SESSION['captcha'][$type]['code']));
    
    if ($isValid) {
        unset($_SESSION['captcha'][$type]);
    }
    
    return $isValid;
}

function getActiveJalur($pdo) {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT j.* 
        FROM jalur_pendaftaran j
        WHERE ? BETWEEN j.tanggal_buka AND j.tanggal_tutup
        AND (
            SELECT COUNT(*) 
            FROM peserta 
            WHERE jalur_id = j.id
        ) < j.kuota
    ");
    $stmt->execute([$now]);
    return $stmt->fetchAll();
}

function getRegistrationStatus($jalurId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT tanggal_buka, tanggal_tutup, tanggal_pengumuman 
            FROM jalur_pendaftaran 
            WHERE id = ?
        ");
        $stmt->execute([$jalurId]);
        $dates = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dates) {
            return ['status' => 'error', 'message' => 'Jalur pendaftaran tidak ditemukan'];
        }

        $now = new DateTime();
        $openDate = new DateTime($dates['tanggal_buka']);
        $closeDate = new DateTime($dates['tanggal_tutup']);
        $announceDate = new DateTime($dates['tanggal_pengumuman']);

        if ($now < $openDate) {
            return [
                'status' => 'not_started',
                'message' => 'Pendaftaran akan dibuka pada ' . $openDate->format('d F Y'),
                'date' => $dates['tanggal_buka']
            ];
        }

        if ($now > $closeDate) {
            return [
                'status' => 'closed',
                'message' => 'Pendaftaran telah ditutup pada ' . $closeDate->format('d F Y'),
                'date' => $dates['tanggal_tutup']
            ];
        }

        return [
            'status' => 'open',
            'message' => 'Pendaftaran sedang berlangsung sampai ' . $closeDate->format('d F Y'),
            'date' => $dates['tanggal_tutup']
        ];
    } catch (Exception $e) {
        error_log("Error in getRegistrationStatus: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Terjadi kesalahan sistem'];
    }
}

function getAnnouncementStatus($userId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.jalur_id, j.tanggal_pengumuman, v.status_verifikasi, pg.status_penerimaan
            FROM peserta p
            LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id
            LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id
            LEFT JOIN pengumuman pg ON p.id = pg.peserta_id
            WHERE p.id = ?
        ");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return ['status' => 'error', 'message' => 'Data tidak ditemukan'];
        }

        // Check verification status first
        if ($data['status_verifikasi'] !== 'Verified') {
            return [
                'status' => 'unverified',
                'message' => 'Data Anda belum diverifikasi',
                'canViewResult' => false
            ];
        }

        $now = new DateTime();
        $announceDate = new DateTime($data['tanggal_pengumuman']);

        if ($now < $announceDate) {
            return [
                'status' => 'scheduled',
                'message' => 'Pengumuman akan dirilis pada ' . $announceDate->format('d F Y'),
                'canViewResult' => false,
                'date' => $data['tanggal_pengumuman']
            ];
        }

        // If we're past announcement date, check if result exists
        if ($data['status_penerimaan']) {
            return [
                'status' => 'announced',
                'message' => 'Hasil pengumuman sudah tersedia',
                'canViewResult' => true,
                'result' => $data['status_penerimaan'],
                'date' => $data['tanggal_pengumuman']
            ];
        }

        return [
            'status' => 'pending',
            'message' => 'Hasil pengumuman sedang diproses',
            'canViewResult' => false
        ];
    } catch (Exception $e) {
        error_log("Error in getAnnouncementStatus: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Terjadi kesalahan sistem'];
    }
}

function getFormattedFileName($original_name, $type, $nama_lengkap) {
    $safe_nama = preg_replace('/[^A-Za-z0-9]/', '', $nama_lengkap);
    
    switch($type) {
        case 'file_photo':
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            return 'FOTO_' . $safe_nama . '.' . $ext;
        case 'file_kk':
            return 'KK_' . $safe_nama . '.pdf';
        case 'file_akte':
            return 'AKTE_' . $safe_nama . '.pdf';
        case 'file_ijazah_skl':
            return 'IJAZAH_' . $safe_nama . '.pdf';
        case (preg_match('/^file_raport_[1-5]$/', $type) ? true : false):
            $semester = substr($type, -1);
            return 'RAPORT_SEM' . $semester . '_' . $safe_nama . '.pdf';
        default:
            return null;
    }
}

function getJavaScript() {
    return <<<'JS'
// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Function to initialize captcha on page load
function initializeCaptcha(captchaId, timerId, inputId, type = 'register') {
    refreshCaptcha(captchaId, timerId, inputId, type);
}

// Enhanced refresh captcha function
async function refreshCaptcha(captchaId, timerId, inputId, type = 'register') {
    const input = document.getElementById(inputId);
    const timer = document.getElementById(timerId);
    
    // Disable paste and right-click on input
    input.addEventListener('paste', e => e.preventDefault());
    input.addEventListener('contextmenu', e => e.preventDefault());
    
    // Clear existing interval
    if (window[`${timerId}_interval`]) {
        clearInterval(window[`${timerId}_interval`]);
    }
    
    input.value = '';
    input.disabled = false;
    
    try {
        const response = await fetch(`includes/captcha.php?type=${type}&t=${new Date().getTime()}`, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });
        
        if (!response.ok) throw new Error('Gagal memuat CAPTCHA');
        
        const captcha = await response.text();
        const captchaElement = document.getElementById(captchaId);
        
        // Convert captcha text to individual spans with random colors and rotations
        captchaElement.innerHTML = Array.from(captcha).map(char => {
            const rotation = Math.random() * 20 - 10; // Random rotation between -10 and 10 degrees
            const color = `hsl(${Math.random() * 360}, 70%, 40%)`; // Random color
            return `<span style="color: ${color}; display: inline-block; transform: rotate(${rotation}deg);">${char}</span>`;
        }).join('');
        
        // Add noise elements
        const noise = Array(3).fill().map(() => {
            const left = Math.random() * 100;
            return `<span class="noise" style="left: ${left}%"></span>`;
        }).join('');
        
        captchaElement.insertAdjacentHTML('beforeend', noise);
        
        startCaptchaTimer(timerId, captchaId, inputId, type);
        
    } catch (error) {
        console.error('Error:', error);
        document.getElementById(captchaId).textContent = "ERROR";
        document.getElementById(timerId).textContent = "00:00";
        input.disabled = true;
    }
}

// Captcha timer functionality with auto-refresh
function startCaptchaTimer(timerId, captchaId, inputId, type = 'register') {
    let timeLeft = 90;
    const timerElement = document.getElementById(timerId);
    const input = document.getElementById(inputId);
    const captchaElement = document.getElementById(captchaId);
    
    // Clear previous interval
    if (window[`${timerId}_interval`]) {
        clearInterval(window[`${timerId}_interval`]);
    }
    
    function updateDisplay() {
        const minutes = Math.floor(timeLeft / 60).toString().padStart(2, '0');
        const seconds = (timeLeft % 60).toString().padStart(2, '0');
        timerElement.textContent = `${minutes}:${seconds}`;
        
        // Update text color based on time remaining
        if (timeLeft <= 30) {
            timerElement.classList.remove('bg-warning', 'bg-danger');
            timerElement.classList.add('bg-danger');
        } else if (timeLeft <= 60) {
            timerElement.classList.remove('bg-danger');
            timerElement.classList.add('bg-warning');
        }
    }
    
    window[`${timerId}_interval`] = setInterval(() => {
        timeLeft--;
        updateDisplay();
        
        // Auto refresh when timer reaches 0
        if (timeLeft <= 0) {
            clearInterval(window[`${timerId}_interval`]);
            refreshCaptcha(captchaId, timerId, inputId, type);
        }
    }, 1000);
    
    updateDisplay();
}

// Initialize CAPTCHA timers when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize login captcha if exists
    if (document.getElementById('login-captcha-code')) {
        initializeCaptcha('login-captcha-code', 'login-captcha-timer', 'login-captcha-input', 'login');
    }
    
    // Initialize register captcha if exists
    if (document.getElementById('captcha-code')) {
        initializeCaptcha('captcha-code', 'captcha-timer', 'captcha-input', 'register');
    }
});
JS;
}

function handleFormUpload($formData, $files, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Handle regular form data
        if (!empty($formData)) {
            $updateFields = [];
            $updateValues = [];
            
            foreach ($formData as $field => $value) {
                if (!empty($value) && !str_starts_with($field, 'file_')) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $value;
                }
            }
            
            if (!empty($updateFields)) {
                $updateValues[] = $_SESSION['user_id'];
                $sql = "UPDATE peserta SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateValues);
            }
        }

        // Handle file uploads
        if (!empty($files)) {
            $fileFields = [];
            $fileValues = [];
            $targetDir = __DIR__ . '/../uploads/';
            
            foreach ($files as $fileField => $fileInfo) {
                if ($fileInfo['error'] === UPLOAD_ERR_OK) {
                    $fileName = getFormattedFileName($fileInfo['name'], $fileField, $formData['nama_lengkap'] ?? '');
                    
                    if ($fileName) {
                        $targetPath = $targetDir . $fileName;
                        
                        if (move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
                            $fileFields[] = "$fileField = ?";
                            $fileValues[] = $fileName;
                        } else {
                            throw new Exception("Gagal mengupload file: " . $fileField);
                        }
                    }
                }
            }
            
            if (!empty($fileFields)) {
                $fileValues[] = $_SESSION['user_id'];
                $sql = "UPDATE peserta SET " . implode(", ", $fileFields) . ", updated_at = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($fileValues);
            }
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Data berhasil disimpan'];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in handleFormUpload: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

function getUserFiles($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT file_kk, file_akte, file_photo FROM peserta WHERE id = ?");
    $stmt->execute([$userId]);
    $files = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($files as $type => $filename) {
        if ($filename) {
            $result[$type] = [
                'filename' => $filename,
                'display_name' => ucfirst(str_replace('file_', '', $type)) . ' - ' . $filename,
                'download_url' => 'download.php?file=' . urlencode($filename)
            ];
        }
    }
    return $result;
}
