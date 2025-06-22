<?php
session_start();
require_once 'config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($transaction_id <= 0) {
    echo "ID transaksi tidak valid";
    exit();
}

try {
    $conn = getConnection();
    
    // Ambil data transaksi
    $stmt = $conn->prepare("
        SELECT 
            tp.*,
            p.nama_pelanggan
        FROM transaksi_penjualan tp
        LEFT JOIN pelanggan p ON tp.id_pelanggan = p.id_pelanggan
        WHERE tp.id_transaksi = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        echo "Transaksi tidak ditemukan";
        exit();
    }
    
    // Ambil detail transaksi
    $stmt = $conn->prepare("
        SELECT * FROM detail_transaksi 
        WHERE id_transaksi = ? 
        ORDER BY id_detail ASC
    ");
    $stmt->execute([$transaction_id]);
    $details = $stmt->fetchAll();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi - <?php echo htmlspecialchars($transaction['nomor_transaksi']); ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .receipt {
            width: 300px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .store-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .store-info {
            font-size: 10px;
            color: #666;
        }
        
        .transaction-info {
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        .transaction-info div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .items {
            margin-bottom: 15px;
        }
        
        .item {
            margin-bottom: 8px;
        }
        
        .item-name {
            font-weight: bold;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        
        .summary {
            border-top: 1px dashed #333;
            padding-top: 10px;
            margin-top: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .total {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .payment-info {
            margin-top: 15px;
            border-top: 1px dashed #333;
            padding-top: 10px;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            border-top: 1px dashed #333;
            padding-top: 10px;
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .receipt {
                border: none;
                width: 100%;
                max-width: 300px;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="store-name">🏪 WARUNG CEPRIPKI</div>
            <div class="store-info">
                Sistem Manajemen Toko<br>
                Jl. Contoh No. 123, Kota<br>
                Telp: (021) 1234-5678
            </div>
        </div>
          <!-- Transaction Info -->
        <div class="transaction-info">
            <div>
                <span>No. Transaksi:</span>
                <span><?php echo htmlspecialchars($transaction['no_transaksi']); ?></span>
            </div>
            <div>
                <span>Tanggal:</span>
                <span><?php echo date('d/m/Y H:i:s', strtotime($transaction['tanggal_transaksi'])); ?></span>
            </div>
            <div>
                <span>Kasir:</span>
                <span><?php echo htmlspecialchars($transaction['kasir']); ?></span>
            </div>
            <?php if ($transaction['nama_pelanggan'] && $transaction['nama_pelanggan'] !== 'Pelanggan Umum'): ?>
            <div>
                <span>Pelanggan:</span>
                <span><?php echo htmlspecialchars($transaction['nama_pelanggan']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Items -->
        <div class="items">            <?php foreach ($details as $detail): ?>
            <div class="item">
                <div class="item-name"><?php echo htmlspecialchars($detail['nama_produk']); ?></div>
                <div class="item-details">
                    <span><?php echo number_format($detail['jumlah']); ?> x Rp <?php echo number_format($detail['harga_satuan']); ?></span>
                    <span>Rp <?php echo number_format($detail['subtotal']); ?></span>
                </div>
                <?php if ($detail['diskon_item'] > 0): ?>
                <div class="item-details" style="color: #ff6b6b; font-size: 9px;">
                    <span>Diskon:</span>
                    <span>-Rp <?php echo number_format($detail['diskon_item']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
          <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>Rp <?php echo number_format($transaction['subtotal']); ?></span>
            </div>
            <?php if ($transaction['diskon'] > 0): ?>
            <div class="summary-row">
                <span>Diskon:</span>
                <span>-Rp <?php echo number_format($transaction['diskon']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($transaction['pajak'] > 0): ?>
            <div class="summary-row">
                <span>Pajak:</span>
                <span>Rp <?php echo number_format($transaction['pajak']); ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row total">
                <span>TOTAL:</span>
                <span>Rp <?php echo number_format($transaction['total_bayar']); ?></span>
            </div>
        </div>
        
        <!-- Payment Info -->
        <div class="payment-info">
            <div class="summary-row">
                <span>Tunai:</span>
                <span>Rp <?php echo number_format($transaction['uang_bayar']); ?></span>
            </div>
            <div class="summary-row">
                <span>Kembalian:</span>
                <span>Rp <?php echo number_format($transaction['kembalian']); ?></span>
            </div>
            
            <?php if ($transaction['catatan']): ?>
            <div style="margin-top: 10px; font-size: 10px;">
                <strong>Catatan:</strong><br>
                <?php echo htmlspecialchars($transaction['catatan']); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            Terima kasih atas kunjungan Anda!<br>
            Barang yang sudah dibeli tidak dapat dikembalikan<br>
            <br>
            <small>Struk ini dicetak pada <?php echo date('d/m/Y H:i:s'); ?></small>
        </div>
    </div>
    
    <!-- Print Controls -->
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
            <i class="fas fa-print"></i> Cetak Struk
        </button>
        <button onclick="window.close()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-times"></i> Tutup
        </button>
    </div>
    
    <script>
        // Auto print when page loads
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>
