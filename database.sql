USE  ppdb_man1mr;

CREATE TABLE jalur_pendaftaran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_jalur VARCHAR(50) UNIQUE,
    kuota INT,
    tanggal_buka DATETIME,
    tanggal_tutup DATETIME,
    tanggal_pengumuman DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update insert statements with precise timestamps
INSERT INTO jalur_pendaftaran (nama_jalur, kuota, tanggal_buka, tanggal_tutup, tanggal_pengumuman) VALUES
('Reguler', 30, '2025-03-01 00:00:00', '2025-05-31 23:59:59', '2025-06-01 08:00:00'),
('Prestasi', 30, '2025-04-01 00:00:00', '2025-04-30 23:59:59', '2025-05-01 08:00:00'),
('Pondok Pesantren', 22, '2025-05-01 00:00:00', '2025-06-30 23:59:59', '2025-07-01 08:00:00'),
('Afirmasi', 8, '2025-05-01 00:00:00', '2025-06-30 23:59:59', '2025-07-01 08:00:00'),
('Domisili', 60, '2025-05-01 00:00:00', '2025-06-30 23:59:59', '2025-07-01 08:00:00');

CREATE TABLE peserta (
    -- Biodata Siswa
    id INT PRIMARY KEY AUTO_INCREMENT,
    nisn VARCHAR(20) UNIQUE,
    nik VARCHAR(16) UNIQUE,
    password VARCHAR(255),
    nama_lengkap VARCHAR(100),
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('Laki-Laki', 'Perempuan'),
    agama_siswa ENUM('Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'),
    asal_sekolah VARCHAR(100),
    tahun_lulus YEAR,
    no_wa_siswa VARCHAR(20),
    alamat_siswa_jalan VARCHAR(255),
    alamat_siswa_rt VARCHAR(10),
    alamat_siswa_rw VARCHAR(10),
    alamat_siswa_kelurahan VARCHAR(100),
    alamat_siswa_kecamatan VARCHAR(100),
    alamat_siswa_kota VARCHAR(100),
    
    -- Biodata Orangtua
    no_kk VARCHAR(16) UNIQUE,
    nama_ayah VARCHAR(100),
    tempat_lahir_ayah VARCHAR(100),
    tanggal_lahir_ayah DATE,
    nama_ibu VARCHAR(100),
    tempat_lahir_ibu VARCHAR(100),
    tanggal_lahir_ibu DATE,
    pekerjaan_ayah VARCHAR(100),
    pekerjaan_ibu VARCHAR(100),
    agama_ayah ENUM('Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'),
    agama_ibu ENUM('Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'),
    no_telp_ortu VARCHAR(20),
    alamat_ortu_jalan VARCHAR(255),
    alamat_ortu_rt VARCHAR(10),
    alamat_ortu_rw VARCHAR(10),
    alamat_ortu_kelurahan VARCHAR(100),
    alamat_ortu_kecamatan VARCHAR(100),
    alamat_ortu_kota VARCHAR(100),
    
    -- Lain-lain
    jalur_id INT,
    jarak_ke_sekolah DECIMAL(5,2),
    program_keahlian ENUM('IPA', 'IPS', 'Bahasa', 'Keagamaan'),
    file_kk VARCHAR(255),
    file_akte VARCHAR(255),
    file_photo VARCHAR(255),
    file_ijazah_skl VARCHAR(255),
    file_raport_1 VARCHAR(255),
    file_raport_2 VARCHAR(255),
    file_raport_3 VARCHAR(255),
    file_raport_4 VARCHAR(255),
    file_raport_5 VARCHAR(255),
    remember_token VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jalur_id) REFERENCES jalur_pendaftaran(id)
);

CREATE TABLE prestasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peserta_id INT,
    bidang_prestasi VARCHAR(100),
    peringkat VARCHAR(50),
    tingkat ENUM('Kecamatan', 'Kabupaten', 'Provinsi', 'Nasional', 'Internasional'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id)
);

CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin account
INSERT INTO admin (username, password) 
VALUES ('man1mura', 'admin123');

-- Create table for tracking peserta verification
CREATE TABLE verifikasi_peserta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peserta_id INT,
    admin_id INT,
    jarak_ke_sekolah DECIMAL(5,2),
    status_verifikasi ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id),
    FOREIGN KEY (admin_id) REFERENCES admin(id)
);

CREATE TABLE pengumuman (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peserta_id INT,
    status_penerimaan ENUM('diterima', 'ditolak') NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    admin_id INT,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id),
    FOREIGN KEY (admin_id) REFERENCES admin(id)
);
