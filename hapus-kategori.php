<?php
session_start();
require_once 'config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id_kategori = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kategori <= 0) {
    header('Location: kategori.php?error=' . urlencode('ID kategori tidak valid'));
    exit();
}

try {
    $conn = getConnection();
    
    // Ambil data kategori untuk validasi
    $stmt = $conn->prepare("
        SELECT k.nama_kategori, COUNT(p.id_produk) as jumlah_produk 
        FROM kategori k 
        LEFT JOIN produk p ON k.id_kategori = p.id_kategori 
        WHERE k.id_kategori = ?
        GROUP BY k.id_kategori, k.nama_kategori
    ");
    $stmt->execute([$id_kategori]);
    $category = $stmt->fetch();
    
    if (!$category) {
        header('Location: kategori.php?error=' . urlencode('Kategori tidak ditemukan'));
        exit();
    }
    
    // Cek apakah kategori masih digunakan oleh produk
    if ($category['jumlah_produk'] > 0) {
        header('Location: kategori.php?error=' . urlencode('Kategori "' . $category['nama_kategori'] . '" tidak dapat dihapus karena masih digunakan oleh ' . $category['jumlah_produk'] . ' produk'));
        exit();
    }
    
    // Hapus kategori
    $delete_stmt = $conn->prepare("DELETE FROM kategori WHERE id_kategori = ?");
    $delete_stmt->execute([$id_kategori]);
    
    header('Location: kategori.php?success=' . urlencode('Kategori "' . $category['nama_kategori'] . '" berhasil dihapus'));
    
} catch (Exception $e) {
    header('Location: kategori.php?error=' . urlencode('Terjadi kesalahan: ' . $e->getMessage()));
}

exit();
?>
