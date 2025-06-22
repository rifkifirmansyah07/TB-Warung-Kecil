<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Detail Transaksi', 'transaksi.php');

$transaction = null;
$details = [];
$error_message = '';

$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($transaction_id <= 0) {
    header('Location: riwayat-transaksi.php');
    exit();
}

try {
    $conn = getConnection();
    
    // Ambil data transaksi
    $stmt = $conn->prepare("
        SELECT 
            tp.*,
            p.nama_pelanggan,
            p.no_telepon,
            p.alamat
        FROM transaksi_penjualan tp
        LEFT JOIN pelanggan p ON tp.id_pelanggan = p.id_pelanggan
        WHERE tp.id_transaksi = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        $error_message = 'Transaksi tidak ditemukan!';
    } else {
        // Ambil detail transaksi
        $stmt = $conn->prepare("
            SELECT 
                dt.*,
                p.barcode,
                p.satuan,
                COALESCE(k.nama_kategori, 'Tanpa Kategori') as kategori
            FROM detail_transaksi dt
            LEFT JOIN produk p ON dt.id_produk = p.id_produk
            LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
            WHERE dt.id_transaksi = ?
            ORDER BY dt.id_detail ASC
        ");
        $stmt->execute([$transaction_id]);
        $details = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data: ' . $e->getMessage();
}
?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav style="margin-bottom: 20px;">
    <a href="riwayat-transaksi.php" style="color: var(--primary-color); text-decoration: none;">
        <i class="fas fa-arrow-left"></i> Kembali ke Riwayat Transaksi
    </a>
</nav>

<?php if ($transaction): ?>
<!-- Info Transaksi -->
<div class="card mb-25">    <div class="card-header">
        <i class="fas fa-receipt"></i> Detail Transaksi: <?php echo htmlspecialchars($transaction['no_transaksi']); ?>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <div>
                <strong>Nomor Transaksi:</strong><br>
                <?php echo htmlspecialchars($transaction['no_transaksi']); ?>
            </div>
            <div>
                <strong>Tanggal & Waktu:</strong><br>
                <?php echo date('d/m/Y H:i:s', strtotime($transaction['tanggal_transaksi'])); ?>
            </div>
            <div>
                <strong>Kasir:</strong><br>
                <?php echo htmlspecialchars($transaction['kasir']); ?>
            </div>
            <div>
                <strong>Pelanggan:</strong><br>
                <?php echo htmlspecialchars($transaction['nama_pelanggan'] ?: 'Pelanggan Umum'); ?>
                <?php if ($transaction['no_telepon'] && $transaction['no_telepon'] !== '-'): ?>
                <br><small style="color: var(--text-light);"><?php echo htmlspecialchars($transaction['no_telepon']); ?></small>
                <?php endif; ?>
            </div>
        </div>
          <!-- Informasi Pembayaran -->
        <div style="background: var(--secondary-color); padding: 15px; border-radius: 6px;">
            <h4 style="margin-bottom: 10px; color: var(--text-color);">Informasi Pembayaran</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Subtotal:</strong><br>
                    Rp <?php echo number_format($transaction['subtotal']); ?>
                </div>
                
                <?php if ($transaction['diskon'] > 0): ?>
                <div>
                    <strong>Diskon:</strong><br>
                    <span style="color: var(--danger-color);">-Rp <?php echo number_format($transaction['diskon']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($transaction['pajak'] > 0): ?>
                <div>
                    <strong>Pajak:</strong><br>
                    Rp <?php echo number_format($transaction['pajak']); ?>
                </div>
                <?php endif; ?>
                
                <div>
                    <strong>Total Bayar:</strong><br>
                    <span style="font-size: 18px; font-weight: bold; color: var(--primary-color);">
                        Rp <?php echo number_format($transaction['total_bayar']); ?>
                    </span>
                </div>
                
                <div>
                    <strong>Uang Bayar:</strong><br>
                    Rp <?php echo number_format($transaction['uang_bayar']); ?>
                </div>
                
                <div>
                    <strong>Kembalian:</strong><br>
                    <span style="color: var(--success-color); font-weight: bold;">
                        Rp <?php echo number_format($transaction['kembalian']); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($transaction['catatan']): ?>
            <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 4px; border-left: 4px solid var(--primary-color);">
                <strong>Catatan:</strong><br>
                <?php echo nl2br(htmlspecialchars($transaction['catatan'])); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <div style="margin-top: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="cetak-struk.php?id=<?php echo $transaction['id_transaksi']; ?>" 
               class="btn btn-warning" target="_blank">
                <i class="fas fa-print"></i> Cetak Struk
            </a>            <a href="riwayat-transaksi.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Lihat Semua Transaksi
            </a>
        </div>
    </div>
</div>

<!-- Detail Items -->
<div class="card mb-25">
    <div class="card-header">
        <i class="fas fa-shopping-cart"></i> Item yang Dibeli (<?php echo count($details); ?> item)
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Barcode</th>
                    <th>Harga Satuan</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>                <?php 
                $no = 1;
                $total_items = 0;
                foreach ($details as $detail): 
                    $total_items += $detail['jumlah'];
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($detail['nama_produk']); ?></strong>
                        <?php if ($detail['satuan']): ?>
                        <br><small class="text-muted">Satuan: <?php echo htmlspecialchars($detail['satuan']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($detail['kategori']); ?></td>
                    <td>
                        <?php if ($detail['barcode']): ?>
                            <code><?php echo htmlspecialchars($detail['barcode']); ?></code>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>                    <td>Rp <?php echo number_format($detail['harga_satuan']); ?></td>
                    <td>
                        <span style="font-weight: bold; color: var(--primary-color);">
                            <?php echo number_format($detail['jumlah']); ?>
                        </span>
                    </td>
                    <td>
                        <strong style="color: var(--success-color);">
                            Rp <?php echo number_format($detail['subtotal']); ?>
                        </strong>
                        <?php if ($detail['diskon_item'] > 0): ?>
                        <br><small style="color: var(--danger-color);">
                            Diskon: -Rp <?php echo number_format($detail['diskon_item']); ?>
                        </small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot style="background: var(--secondary-color);">
                <tr>
                    <td colspan="5"><strong>Total:</strong></td>                    <td><strong><?php echo number_format($total_items); ?> item</strong></td>
                    <td><strong style="color: var(--primary-color); font-size: 16px;">Rp <?php echo number_format($transaction['total_bayar']); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Ringkasan Transaksi -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-calculator"></i> Ringkasan Transaksi
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 15px; background: #e3f2fd; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #1976d2;">
                    <?php echo count($details); ?>
                </div>
                <div style="color: #1976d2;">Jenis Produk</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f3e5f5; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #7b1fa2;">
                    <?php echo number_format($total_items); ?>
                </div>
                <div style="color: #7b1fa2;">Total Item</div>
            </div>
              <div style="text-align: center; padding: 15px; background: #d4edda; border-radius: 6px;">
                <div style="font-size: 18px; font-weight: bold; color: #155724;">
                    Rp <?php echo number_format($transaction['total_bayar'] / $total_items); ?>
                </div>
                <div style="color: #155724;">Harga Rata-rata</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: var(--secondary-color); border-radius: 6px;">
                <div style="font-size: 18px; font-weight: bold; color: var(--primary-color);">
                    Rp <?php echo number_format($transaction['total_bayar']); ?>
                </div>
                <div style="color: var(--text-color);">Total Transaksi</div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="alert alert-danger">
    Transaksi tidak ditemukan.
</div>
<?php endif; ?>

<?php endLayout(); ?>
