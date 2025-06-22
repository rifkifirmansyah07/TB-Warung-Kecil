<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Log Stok', 'produk.php');

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produk <= 0) {
    header('Location: produk.php');
    exit();
}

try {
    $conn = getConnection();
    
    // Ambil data produk
    $stmt = $conn->prepare("
        SELECT p.*, COALESCE(k.nama_kategori, 'Tanpa Kategori') as nama_kategori 
        FROM produk p 
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
        WHERE p.id_produk = ?
    ");
    $stmt->execute([$id_produk]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: produk.php');
        exit();
    }
    
    // Ambil log stok
    $stmt = $conn->prepare("
        SELECT * FROM log_stok 
        WHERE id_produk = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$id_produk]);
    $logs = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data: ' . $e->getMessage();
}
?>

<!-- Breadcrumb -->
<nav style="margin-bottom: 20px;">
    <a href="produk.php" style="color: var(--primary-color); text-decoration: none;">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Produk
    </a>
</nav>

<!-- Info Produk -->
<div class="card mb-25">
    <div class="card-header">
        <i class="fas fa-info-circle"></i> Log Stok - Informasi Produk
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <strong>Nama Produk:</strong><br>
                <?php echo htmlspecialchars($product['nama_produk']); ?>
            </div>
            <div>
                <strong>Kategori:</strong><br>
                <?php echo htmlspecialchars($product['nama_kategori']); ?>
            </div>
            <div>
                <strong>Barcode:</strong><br>
                <?php echo htmlspecialchars($product['barcode']) ?: '-'; ?>
            </div>
            <div>
                <strong>Stok Saat Ini:</strong><br>
                <span style="color: <?php echo ($product['stok'] <= $product['stok_minimum']) ? 'var(--danger-color)' : 'var(--success-color)'; ?>; font-weight: bold;">
                    <?php echo number_format($product['stok']); ?> <?php echo $product['satuan']; ?>
                </span>
                <?php if ($product['stok'] <= $product['stok_minimum']): ?>
                    <small style="color: var(--danger-color);"><br>⚠️ Stok menipis</small>
                <?php endif; ?>
            </div>
            <div>
                <strong>Stok Minimum:</strong><br>
                <?php echo number_format($product['stok_minimum']); ?> <?php echo $product['satuan']; ?>
            </div>
            <div>
                <strong>Status:</strong><br>
                <span class="status-badge <?php echo ($product['status'] === 'aktif') ? 'status-success' : 'status-cancelled'; ?>">
                    <?php echo ucfirst($product['status']); ?>
                </span>
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 15px;">
            <a href="edit-produk.php?id=<?php echo $product['id_produk']; ?>" class="btn btn-secondary">
                <i class="fas fa-edit"></i> Edit Produk
            </a>
            <a href="adjustment-stok.php?id=<?php echo $product['id_produk']; ?>" class="btn">
                <i class="fas fa-plus-minus"></i> Adjustment Stok
            </a>
        </div>
    </div>
</div>

<!-- Log Stok -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history"></i> Log Stok - Riwayat Perubahan Stok
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($logs)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal/Waktu</th>
                    <th>Jenis Transaksi</th>
                    <th>Jumlah</th>
                    <th>Stok Sebelum</th>
                    <th>Stok Sesudah</th>
                    <th>Referensi</th>
                    <th>Keterangan</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                        <br><small class="text-muted"><?php echo date('D', strtotime($log['created_at'])); ?></small>
                    </td>
                    <td>
                        <?php 
                        $jenis_class = '';
                        $jenis_icon = '';
                        switch($log['jenis_transaksi']) {
                            case 'masuk':
                                $jenis_class = 'status-success';
                                $jenis_icon = 'fas fa-arrow-up';
                                break;
                            case 'keluar':
                                $jenis_class = 'status-cancelled';
                                $jenis_icon = 'fas fa-arrow-down';
                                break;
                            case 'adjustment':
                                $jenis_class = 'status-pending';
                                $jenis_icon = 'fas fa-edit';
                                break;
                        }
                        ?>
                        <span class="status-badge <?php echo $jenis_class; ?>">
                            <i class="<?php echo $jenis_icon; ?>"></i> <?php echo ucfirst($log['jenis_transaksi']); ?>
                        </span>
                    </td>
                    <td>
                        <strong style="color: <?php echo ($log['jenis_transaksi'] == 'masuk') ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
                            <?php echo ($log['jenis_transaksi'] == 'masuk' ? '+' : '-'); ?><?php echo number_format($log['jumlah']); ?>
                        </strong>
                    </td>
                    <td><?php echo number_format($log['stok_sebelum']); ?></td>
                    <td>
                        <strong><?php echo number_format($log['stok_sesudah']); ?></strong>
                    </td>
                    <td>
                        <?php if ($log['referensi']): ?>
                            <code><?php echo htmlspecialchars($log['referensi']); ?></code>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($log['keterangan']); ?></td>
                    <td><?php echo htmlspecialchars($log['user_input']) ?: 'System'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <p>Belum ada riwayat perubahan stok</p>
            <small>Log akan muncul setelah ada transaksi atau adjustment stok</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($logs)): ?>
<!-- Summary -->
<div class="card mt-20">
    <div class="card-header">
        <i class="fas fa-chart-line"></i> Ringkasan Pergerakan Stok
    </div>
    <div class="card-body">
        <?php
        $total_masuk = 0;
        $total_keluar = 0;
        $total_adjustment = 0;
        
        foreach ($logs as $log) {
            switch($log['jenis_transaksi']) {
                case 'masuk':
                    $total_masuk += $log['jumlah'];
                    break;
                case 'keluar':
                    $total_keluar += $log['jumlah'];
                    break;
                case 'adjustment':
                    $total_adjustment += $log['jumlah'];
                    break;
            }
        }
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 15px; background: #d4edda; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #155724;">
                    +<?php echo number_format($total_masuk); ?>
                </div>
                <div style="color: #155724;">Total Stok Masuk</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f8d7da; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #721c24;">
                    -<?php echo number_format($total_keluar); ?>
                </div>
                <div style="color: #721c24;">Total Stok Keluar</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #856404;">
                    <?php echo number_format($total_adjustment); ?>
                </div>
                <div style="color: #856404;">Total Adjustment</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: var(--secondary-color); border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: var(--primary-color);">
                    <?php echo count($logs); ?>
                </div>
                <div style="color: var(--text-color);">Total Transaksi</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endLayout(); ?>
