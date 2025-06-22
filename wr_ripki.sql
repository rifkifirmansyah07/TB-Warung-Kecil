CREATE DATABASE IF NOT EXISTS wrg;
USE wrg;
-- Tabel Kategori Produk
CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel User untuk Login Pemilik Warung
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(255) NOT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Tabel Produk/Barang
CREATE TABLE produk (
    id_produk INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(255) NOT NULL,
    id_kategori INT,
    harga_beli DECIMAL(10,2) NOT NULL,
    harga_jual DECIMAL(10,2) NOT NULL,
    stok INT DEFAULT 0,
    stok_minimum INT DEFAULT 5,
    satuan VARCHAR(50) DEFAULT 'pcs',
    barcode VARCHAR(100),
    deskripsi TEXT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE SET NULL,
    INDEX idx_nama_produk (nama_produk),
    INDEX idx_barcode (barcode),
    INDEX idx_status (status)
);

-- Tabel Pelanggan
CREATE TABLE pelanggan (
    id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelanggan VARCHAR(255) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT,
    email VARCHAR(100),
    total_pembelian DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nama_pelanggan (nama_pelanggan),
    INDEX idx_no_telepon (no_telepon)
);

-- Tabel Header Transaksi Penjualan
CREATE TABLE transaksi_penjualan (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) UNIQUE NOT NULL,
    id_pelanggan INT,
    tanggal_transaksi DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_item INT DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    pajak DECIMAL(15,2) DEFAULT 0,    total_bayar DECIMAL(15,2) NOT NULL,
    uang_bayar DECIMAL(15,2) NOT NULL,
    kembalian DECIMAL(15,2) DEFAULT 0,
    kasir VARCHAR(100),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE SET NULL,
    INDEX idx_no_transaksi (no_transaksi),
    INDEX idx_tanggal (tanggal_transaksi),
    INDEX idx_kasir (kasir)
);

-- Tabel Detail Transaksi Penjualan  
CREATE TABLE detail_transaksi (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_produk INT NOT NULL,
    nama_produk VARCHAR(255) NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL,
    jumlah INT NOT NULL,
    diskon_item DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_transaksi) REFERENCES transaksi_penjualan(id_transaksi) ON DELETE CASCADE,
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE RESTRICT,
    INDEX idx_transaksi (id_transaksi),
    INDEX idx_produk (id_produk)
);

-- Tabel Log Perubahan Stok
CREATE TABLE log_stok (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    jenis_transaksi ENUM('masuk', 'keluar', 'adjustment') NOT NULL,
    jumlah INT NOT NULL,
    stok_sebelum INT NOT NULL,
    stok_sesudah INT NOT NULL,
    referensi VARCHAR(100),
    keterangan TEXT,
    user_input VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE,
    INDEX idx_produk_log (id_produk),
    INDEX idx_tanggal_log (created_at),
    INDEX idx_jenis (jenis_transaksi)
);


-- View Laporan Penjualan Harian
CREATE VIEW laporan_penjualan_harian AS
SELECT 
    DATE(tanggal_transaksi) as tanggal,
    COUNT(*) as total_transaksi,
    SUM(total_item) as total_item_terjual,
    SUM(subtotal) as total_subtotal,
    SUM(diskon) as total_diskon,
    SUM(pajak) as total_pajak,
    SUM(total_bayar) as total_penjualan,
    ROUND(AVG(total_bayar), 2) as rata_rata_transaksi
FROM transaksi_penjualan 
GROUP BY DATE(tanggal_transaksi)
ORDER BY tanggal DESC;

-- View Produk dengan Stok Menipis
CREATE VIEW produk_stok_menipis AS
SELECT 
    p.id_produk,
    p.nama_produk,
    COALESCE(k.nama_kategori, 'Tanpa Kategori') as nama_kategori,
    p.stok,
    p.stok_minimum,
    p.harga_jual,
    (p.stok_minimum - p.stok) as kekurangan_stok
FROM produk p
LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
WHERE p.stok <= p.stok_minimum AND p.status = 'aktif'
ORDER BY p.stok ASC;

-- View Produk Terlaris
CREATE VIEW produk_terlaris AS
SELECT 
    p.id_produk,
    p.nama_produk,
    COALESCE(k.nama_kategori, 'Tanpa Kategori') as nama_kategori,
    SUM(dt.jumlah) as total_terjual,
    SUM(dt.subtotal) as total_pendapatan,
    COUNT(DISTINCT dt.id_transaksi) as frekuensi_transaksi,
    ROUND(AVG(dt.harga_satuan), 2) as rata_rata_harga
FROM detail_transaksi dt
JOIN produk p ON dt.id_produk = p.id_produk
LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
JOIN transaksi_penjualan tp ON dt.id_transaksi = tp.id_transaksi
GROUP BY p.id_produk, p.nama_produk, k.nama_kategori
ORDER BY total_terjual DESC;

-- View Aktivitas User
CREATE VIEW user_activity AS
SELECT 
    u.id_user,
    u.username,
    u.nama_lengkap,
    u.status,
    u.last_login,
    u.login_attempts,
    CASE 
        WHEN u.locked_until > CURRENT_TIMESTAMP THEN 'Terkunci'
        WHEN u.status = 'nonaktif' THEN 'Non-aktif'
        ELSE 'Aktif'
    END as status_login,
    u.locked_until,
    u.created_at
FROM users u
ORDER BY u.last_login DESC;

DELIMITER //

-- Procedure untuk Update Last Login
CREATE PROCEDURE update_last_login(IN user_id INT)
BEGIN
    UPDATE users 
    SET last_login = CURRENT_TIMESTAMP,
        login_attempts = 0,
        locked_until = NULL
    WHERE id_user = user_id;
END//

-- Procedure untuk Handle Failed Login Attempts
CREATE PROCEDURE handle_failed_login(IN user_id INT)
BEGIN
    DECLARE current_attempts INT DEFAULT 0;
    
    SELECT login_attempts INTO current_attempts 
    FROM users 
    WHERE id_user = user_id;
    
    SET current_attempts = current_attempts + 1;
    
    IF current_attempts >= 5 THEN
        UPDATE users 
        SET login_attempts = current_attempts,
            locked_until = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 MINUTE)
        WHERE id_user = user_id;
    ELSE
        UPDATE users 
        SET login_attempts = current_attempts
        WHERE id_user = user_id;
    END IF;
END//

-- Function untuk Generate Nomor Transaksi
CREATE FUNCTION generate_no_transaksi() 
RETURNS VARCHAR(50)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE nomor VARCHAR(50);
    DECLARE counter INT;
    DECLARE today VARCHAR(8);
    
    SET today = DATE_FORMAT(CURDATE(), '%Y%m%d');
    
    SELECT COUNT(*) + 1 INTO counter 
    FROM transaksi_penjualan 
    WHERE DATE(tanggal_transaksi) = CURDATE();
    
    SET nomor = CONCAT('TRX-', today, '-', LPAD(counter, 4, '0'));
    
    RETURN nomor;
END//

-- Trigger Update Stok Setelah Penjualan
CREATE TRIGGER update_stok_after_penjualan
AFTER INSERT ON detail_transaksi
FOR EACH ROW
BEGIN
    DECLARE stok_lama INT;
    DECLARE no_transaksi_ref VARCHAR(50);
    
    -- Ambil stok saat ini
    SELECT stok INTO stok_lama FROM produk WHERE id_produk = NEW.id_produk;
    
    -- Ambil nomor transaksi
    SELECT no_transaksi INTO no_transaksi_ref 
    FROM transaksi_penjualan 
    WHERE id_transaksi = NEW.id_transaksi;
    
    -- Update stok produk
    UPDATE produk 
    SET stok = stok - NEW.jumlah,
        updated_at = CURRENT_TIMESTAMP
    WHERE id_produk = NEW.id_produk;
    
    -- Log perubahan stok
    INSERT INTO log_stok (
        id_produk, 
        jenis_transaksi, 
        jumlah, 
        stok_sebelum, 
        stok_sesudah, 
        referensi, 
        keterangan
    ) VALUES (
        NEW.id_produk,
        'keluar',
        NEW.jumlah,
        stok_lama,
        stok_lama - NEW.jumlah,
        no_transaksi_ref,
        CONCAT('Penjualan - ', NEW.nama_produk)
    );
END//


CREATE TRIGGER update_total_transaksi
AFTER INSERT ON detail_transaksi
FOR EACH ROW
BEGIN
    UPDATE transaksi_penjualan 
    SET 
        total_item = (
            SELECT SUM(jumlah) 
            FROM detail_transaksi 
            WHERE id_transaksi = NEW.id_transaksi
        ),
        subtotal = (
            SELECT SUM(subtotal) 
            FROM detail_transaksi 
            WHERE id_transaksi = NEW.id_transaksi
        )
    WHERE id_transaksi = NEW.id_transaksi;
END//

DELIMITER ;

INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Makanan', 'Produk makanan dan cemilan'),
('Minuman', 'Minuman segar dan kemasan'),
('Elektronik', 'Perangkat elektronik dan aksesori'),
('Rumah Tangga', 'Kebutuhan rumah tangga sehari-hari'),
('Kesehatan', 'Produk kesehatan dan kecantikan'),
('ATK', 'Alat tulis kantor dan sekolah'),
('Lainnya', 'Produk lainnya');

INSERT INTO pelanggan (nama_pelanggan, no_telepon, alamat) VALUES
('Pelanggan Umum', '-', 'Alamat tidak diketahui');

-- Insert data awal untuk user (password default: "admin123" - harus di-hash di aplikasi)
INSERT INTO users (username, email, password_hash, nama_lengkap, status) VALUES
('owner', 'owner@warungcepripki.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pemilik Warung Cepripki', 'aktif');

INSERT INTO produk (nama_produk, id_kategori, harga_beli, harga_jual, stok, satuan, barcode, deskripsi) VALUES
('Indomie Goreng', 1, 2500, 3000, 100, 'pcs', '8998866200015', 'Mie instan rasa ayam'),
('Aqua 600ml', 2, 2000, 3000, 50, 'btl', '8996001600003', 'Air mineral kemasan'),
('Baterai AA', 3, 8000, 12000, 20, 'pcs', '1234567890123', 'Baterai alkaline AA'),
('Sabun Cuci Piring', 4, 15000, 18000, 15, 'btl', '8999999123456', 'Sabun pencuci piring'),
('Paracetamol 500mg', 5, 3000, 5000, 30, 'strip', '8888888888888', 'Obat penurun panas'),
('Pulpen Biru', 6, 2000, 3000, 25, 'pcs', '7777777777777', 'Pulpen tinta biru'),
('Rokok Sampoerna Mild', 7, 22000, 24000, 40, 'bks', '9999999999999', 'Rokok mild 16 batang'),
('Susu UHT Ultramilk', 2, 4500, 6000, 35, 'kotak', '1111111111111', 'Susu UHT rasa plain'),
('Teh Pucuk Harum', 2, 3500, 4500, 60, 'btl', '2222222222222', 'Teh kemasan botol'),
('Deterjen Rinso', 4, 28000, 32000, 10, 'pcs', '3333333333333', 'Deterjen bubuk 800gr');