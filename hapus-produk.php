<?php
session_start();
require_once 'config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produk <= 0) {
    header('Location: produk.php?error=' . urlencode('ID produk tidak valid'));
    exit();
}

try {
    $conn = getConnection();
    
    // Ambil data produk untuk validasi
    $stmt = $conn->prepare("SELECT nama_produk, stok FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: produk.php?error=' . urlencode('Produk tidak ditemukan'));
        exit();
    }
    
    // Cek apakah produk sudah pernah ada di transaksi
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM detail_transaksi WHERE id_produk = ?");
    $check_stmt->execute([$id_produk]);
    $transaction_count = $check_stmt->fetch()['count'];
    
    if ($transaction_count > 0) {
        // Jika sudah ada transaksi, ubah status menjadi nonaktif saja
        $update_stmt = $conn->prepare("UPDATE produk SET status = 'nonaktif' WHERE id_produk = ?");
        $update_stmt->execute([$id_produk]);
        
        header('Location: produk.php?success=' . urlencode('Produk "' . $product['nama_produk'] . '" telah dinonaktifkan karena sudah memiliki riwayat transaksi'));
    } else {
        // Jika belum ada transaksi, hapus permanent
        
        // Hapus log stok terlebih dahulu
        $delete_log = $conn->prepare("DELETE FROM log_stok WHERE id_produk = ?");
        $delete_log->execute([$id_produk]);
        
        // Hapus produk
        $delete_stmt = $conn->prepare("DELETE FROM produk WHERE id_produk = ?");
        $delete_stmt->execute([$id_produk]);
        
        header('Location: produk.php?success=' . urlencode('Produk "' . $product['nama_produk'] . '" berhasil dihapus'));
    }
    
} catch (Exception $e) {
    header('Location: produk.php?error=' . urlencode('Terjadi kesalahan: ' . $e->getMessage()));
}

exit();
?>
