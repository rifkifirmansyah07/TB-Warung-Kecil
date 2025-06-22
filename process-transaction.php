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

if (!$input || empty($input['items'])) {
    echo json_encode(['success' => false, 'message' => 'Data transaksi tidak valid']);
    exit();
}

try {
    $conn = getConnection();
    $conn->beginTransaction();
    
    // Generate nomor transaksi menggunakan function dari database
    $stmt = $conn->query("SELECT generate_no_transaksi() as no_transaksi");
    $result = $stmt->fetch();
    $no_transaksi = $result['no_transaksi'];
    
    // Hitung total dan kembalian
    $total_item = 0;
    $subtotal = 0;
    foreach ($input['items'] as $item) {
        $total_item += $item['quantity'];
        $subtotal += $item['total'];
    }
    
    $diskon = 0; // Bisa dikembangkan untuk sistem diskon
    $pajak = 0;  // Bisa dikembangkan untuk sistem pajak
    $total_bayar = $subtotal - $diskon + $pajak;
    $uang_bayar = $input['cash_amount'] ?? $total_bayar;
    $kembalian = $input['change'] ?? 0;
    
    // Insert transaksi utama
    $stmt = $conn->prepare("
        INSERT INTO transaksi_penjualan (
            no_transaksi, 
            id_pelanggan,
            tanggal_transaksi, 
            total_item,
            subtotal,
            diskon,
            pajak,
            total_bayar, 
            uang_bayar,
            kembalian,
            kasir, 
            catatan
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
      $kasir = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Unknown';
    
    $stmt->execute([
        $no_transaksi,
        1, // Default ke "Pelanggan Umum" - bisa dikembangkan untuk pilih pelanggan
        $total_item,
        $subtotal,
        $diskon,
        $pajak,
        $total_bayar,
        $uang_bayar,
        $kembalian,
        $kasir,
        $input['notes'] ?? ''
    ]);
    
    $transaction_id = $conn->lastInsertId();
    
    // Insert detail transaksi
    $detail_stmt = $conn->prepare("
        INSERT INTO detail_transaksi (
            id_transaksi, 
            id_produk, 
            nama_produk, 
            harga_satuan, 
            jumlah, 
            diskon_item,
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($input['items'] as $item) {
        // Cek stok terlebih dahulu
        $stock_stmt = $conn->prepare("SELECT stok FROM produk WHERE id_produk = ?");
        $stock_stmt->execute([$item['id']]);
        $current_stock = $stock_stmt->fetchColumn();
        
        if ($current_stock < $item['quantity']) {
            throw new Exception("Stok produk {$item['name']} tidak mencukupi");
        }
        
        // Insert detail (trigger akan otomatis update stok dan log)
        $detail_stmt->execute([
            $transaction_id,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity'],
            0, // diskon item
            $item['total']
        ]);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Transaksi berhasil diproses',
        'transaction_id' => $transaction_id,
        'transaction_number' => $no_transaksi
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
