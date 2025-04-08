<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

// Check if admin is logged in
if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

// Create exports directory if it doesn't exist
$export_dir = __DIR__ . '/../exports';
if (!file_exists($export_dir)) {
    mkdir($export_dir, 0777, true);
}

// Fetch all student data with complete fields
$stmt = $pdo->query("
    SELECT 
        p.*,
        jp.nama_jalur,
        v.status_verifikasi,
        v.catatan as catatan_verifikasi,
        pg.status_penerimaan,
        pg.catatan as catatan_pengumuman,
        GROUP_CONCAT(
            CONCAT(pr.bidang_prestasi, ' (', pr.tingkat, ' - ', pr.peringkat, ')')
            SEPARATOR '; '
        ) as prestasi
    FROM peserta p
    LEFT JOIN jalur_pendaftaran jp ON p.jalur_id = jp.id
    LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id
    LEFT JOIN pengumuman pg ON p.id = pg.peserta_id
    LEFT JOIN prestasi pr ON p.id = pr.peserta_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$data = $stmt->fetchAll();

// Send headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Data_Peserta_PPDB_'.date('Y-m-d_His').'.xls"');
header('Cache-Control: max-age=0');

// Output the Excel content directly (no saving to file)
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" 
      xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Data Peserta PPDB</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        td, th {
            mso-number-format:"\@";
            border: 1px solid #000000;
        }
    </style>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th bgcolor="#f0f0f0">No</th>
                <th bgcolor="#f0f0f0">NISN</th>
                <th bgcolor="#f0f0f0">NIK</th>
                <th bgcolor="#f0f0f0">Nama Lengkap</th>
                <th bgcolor="#f0f0f0">Tempat Lahir</th>
                <th bgcolor="#f0f0f0">Tanggal Lahir</th>
                <th bgcolor="#f0f0f0">Jenis Kelamin</th>
                <th bgcolor="#f0f0f0">Agama</th>
                <th bgcolor="#f0f0f0">Asal Sekolah</th>
                <th bgcolor="#f0f0f0">Tahun Lulus</th>
                <th bgcolor="#f0f0f0">No. WA Siswa</th>
                <th bgcolor="#f0f0f0">Alamat Siswa</th>
                <th bgcolor="#f0f0f0">RT</th>
                <th bgcolor="#f0f0f0">RW</th>
                <th bgcolor="#f0f0f0">Kelurahan</th>
                <th bgcolor="#f0f0f0">Kecamatan</th>
                <th bgcolor="#f0f0f0">Kota</th>
                <th bgcolor="#f0f0f0">No. KK</th>
                <th bgcolor="#f0f0f0">Nama Ayah</th>
                <th bgcolor="#f0f0f0">Tempat Lahir Ayah</th>
                <th bgcolor="#f0f0f0">Tanggal Lahir Ayah</th>
                <th bgcolor="#f0f0f0">Pekerjaan Ayah</th>
                <th bgcolor="#f0f0f0">Agama Ayah</th>
                <th bgcolor="#f0f0f0">Nama Ibu</th>
                <th bgcolor="#f0f0f0">Tempat Lahir Ibu</th>
                <th bgcolor="#f0f0f0">Tanggal Lahir Ibu</th>
                <th bgcolor="#f0f0f0">Pekerjaan Ibu</th>
                <th bgcolor="#f0f0f0">Agama Ibu</th>
                <th bgcolor="#f0f0f0">No. Telp Ortu</th>
                <th bgcolor="#f0f0f0">Alamat Ortu</th>
                <th bgcolor="#f0f0f0">RT</th>
                <th bgcolor="#f0f0f0">RW</th>
                <th bgcolor="#f0f0f0">Kelurahan</th>
                <th bgcolor="#f0f0f0">Kecamatan</th>
                <th bgcolor="#f0f0f0">Kota</th>
                <th bgcolor="#f0f0f0">Jalur Pendaftaran</th>
                <th bgcolor="#f0f0f0">Jarak ke Sekolah (km)</th>
                <th bgcolor="#f0f0f0">Program Keahlian</th>
                <th bgcolor="#f0f0f0">Prestasi</th>
                <th bgcolor="#f0f0f0">Status Verifikasi</th>
                <th bgcolor="#f0f0f0">Catatan Verifikasi</th>
                <th bgcolor="#f0f0f0">Status Penerimaan</th>
                <th bgcolor="#f0f0f0">Catatan Pengumuman</th>
                <th bgcolor="#f0f0f0">Tanggal Pendaftaran</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach($data as $row): 
                $prestasi = str_replace('"', "'", $row['prestasi'] ?? '');
                $catatan_verifikasi = str_replace('"', "'", $row['catatan_verifikasi'] ?? '');
                $catatan_pengumuman = str_replace('"', "'", $row['catatan_pengumuman'] ?? '');
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nisn']) ?></td>
                <td><?= htmlspecialchars($row['nik']) ?></td>
                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                <td><?= htmlspecialchars($row['tempat_lahir']) ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal_lahir'])) ?></td>
                <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                <td><?= htmlspecialchars($row['agama_siswa']) ?></td>
                <td><?= htmlspecialchars($row['asal_sekolah']) ?></td>
                <td><?= htmlspecialchars($row['tahun_lulus']) ?></td>
                <td><?= htmlspecialchars($row['no_wa_siswa']) ?></td>
                <td><?= htmlspecialchars($row['alamat_siswa_jalan']) ?></td>
                <td><?= htmlspecialchars($row['alamat_siswa_rt']) ?></td>
                <td><?= htmlspecialchars($row['alamat_siswa_rw']) ?></td>
                <td><?= htmlspecialchars($row['alamat_siswa_kelurahan']) ?></td>
                <td><?= htmlspecialchars($row['alamat_siswa_kecamatan']) ?></td>
                <td><?= htmlspecialchars($row['alamat_siswa_kota']) ?></td>
                <td><?= htmlspecialchars($row['no_kk']) ?></td>
                <td><?= htmlspecialchars($row['nama_ayah']) ?></td>
                <td><?= htmlspecialchars($row['tempat_lahir_ayah']) ?></td>
                <td><?= $row['tanggal_lahir_ayah'] ? date('d/m/Y', strtotime($row['tanggal_lahir_ayah'])) : '' ?></td>
                <td><?= htmlspecialchars($row['pekerjaan_ayah']) ?></td>
                <td><?= htmlspecialchars($row['agama_ayah']) ?></td>
                <td><?= htmlspecialchars($row['nama_ibu']) ?></td>
                <td><?= htmlspecialchars($row['tempat_lahir_ibu']) ?></td>
                <td><?= $row['tanggal_lahir_ibu'] ? date('d/m/Y', strtotime($row['tanggal_lahir_ibu'])) : '' ?></td>
                <td><?= htmlspecialchars($row['pekerjaan_ibu']) ?></td>
                <td><?= htmlspecialchars($row['agama_ibu']) ?></td>
                <td><?= htmlspecialchars($row['no_telp_ortu']) ?></td>
                <td><?= htmlspecialchars($row['alamat_ortu_jalan']) ?></td>
                <td><?= htmlspecialchars($row['alamat_ortu_rt']) ?></td>
                <td><?= htmlspecialchars($row['alamat_ortu_rw']) ?></td>
                <td><?= htmlspecialchars($row['alamat_ortu_kelurahan']) ?></td>
                <td><?= htmlspecialchars($row['alamat_ortu_kecamatan']) ?></td>
                <td><?= htmlspecialchars($row['alamat_ortu_kota']) ?></td>
                <td><?= htmlspecialchars($row['nama_jalur']) ?></td>
                <td><?= htmlspecialchars($row['jarak_ke_sekolah']) ?></td>
                <td><?= htmlspecialchars($row['program_keahlian']) ?></td>
                <td><?= htmlspecialchars($prestasi) ?></td>
                <td><?= htmlspecialchars($row['status_verifikasi'] ?? 'Pending') ?></td>
                <td><?= htmlspecialchars($catatan_verifikasi) ?></td>
                <td><?= htmlspecialchars($row['status_penerimaan'] ?? 'Belum diputuskan') ?></td>
                <td><?= htmlspecialchars($catatan_pengumuman) ?></td>
                <td><?= date('d/m/Y H:i:s', strtotime($row['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php
exit;
