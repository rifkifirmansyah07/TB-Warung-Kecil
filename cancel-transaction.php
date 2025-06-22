<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Ambil data JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['transaction_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid']);
    exit();
}

$transaction_id = (int)$input['transaction_id'];

try {
    $conn = getConnection();
    $conn->beginTransaction();
    
    // Ambil data transaksi
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_transaksi = ? AND status = 'completed'");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        throw new Exception('Transaksi tidak ditemukan atau sudah dibatalkan');
    }
    
    // Ambil detail transaksi untuk mengembalikan stok
    $stmt = $conn->prepare("SELECT * FROM transaksi_detail WHERE id_transaksi = ?");
    $stmt->execute([$transaction_id]);
    $details = $stmt->fetchAll();
    
    // Kembalikan stok produk
    $update_stock_stmt = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
    $log_stock_stmt = $conn->prepare("
        INSERT INTO log_stok (
            id_produk, 
            jenis_transaksi, 
            jumlah, 
            stok_sebelum, 
            stok_sesudah, 
            referensi, 
            keterangan, 
            user_input
        ) VALUES (?, 'masuk', ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($details as $detail) {
        // Get current stock
        $stock_stmt = $conn->prepare("SELECT stok FROM produk WHERE id_produk = ?");
        $stock_stmt->execute([$detail['id_produk']]);
        $current_stock = $stock_stmt->fetchColumn();
        
        // Update stock
        $update_stock_stmt->execute([$detail['quantity'], $detail['id_produk']]);
        
        // Log stock movement
        $new_stock = $current_stock + $detail['quantity'];
        $log_stock_stmt->execute([
            $detail['id_produk'],
            $detail['quantity'],
            $current_stock,
            $new_stock,
            $transaction['nomor_transaksi'],
            "Pembatalan transaksi - {$transaction['nomor_transaksi']}",
            $_SESSION['username']
        ]);
    }
    
    // Update status transaksi
    $stmt = $conn->prepare("UPDATE transaksi SET status = 'cancelled', updated_at = NOW() WHERE id_transaksi = ?");
    $stmt->execute([$transaction_id]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Transaksi berhasil dibatalkan dan stok dikembalikan'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
