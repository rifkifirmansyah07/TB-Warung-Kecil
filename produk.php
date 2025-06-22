<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Kelola Produk', 'produk.php');

$success_message = '';
$error_message = '';

// Cek pesan dari URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

try {
    $conn = getConnection();
    
    // Ambil data produk dengan kategori
    $stmt = $conn->query("
        SELECT 
            p.id_produk,
            p.nama_produk,
            COALESCE(k.nama_kategori, 'Tanpa Kategori') as nama_kategori,
            p.harga_beli,
            p.harga_jual,
            p.stok,
            p.stok_minimum,
            p.satuan,
            p.barcode,
            p.status
        FROM produk p
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
        ORDER BY p.nama_produk ASC
    ");
    $products = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data produk.';
}
?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="tambah-produk.php" class="btn">
        <i class="fas fa-plus"></i> Tambah Produk Baru
    </a>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-box"></i> Kelola Produk - Daftar Semua Produk
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($products)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Kode/Barcode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($product['barcode']); ?></strong>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($product['nama_produk']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($product['satuan']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($product['nama_kategori']); ?></td>
                    <td>Rp <?php echo number_format($product['harga_beli']); ?></td>
                    <td>Rp <?php echo number_format($product['harga_jual']); ?></td>
                    <td>
                        <span style="color: <?php echo ($product['stok'] <= $product['stok_minimum']) ? 'var(--danger-color)' : 'var(--text-color)'; ?>">
                            <?php echo number_format($product['stok']); ?>
                        </span>
                        <?php if ($product['stok'] <= $product['stok_minimum']): ?>
                            <small style="color: var(--danger-color);">
                                <i class="fas fa-exclamation-triangle"></i> Stok Menipis
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo ($product['status'] === 'aktif') ? 'status-success' : 'status-cancelled'; ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                    </td>                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <a href="edit-produk.php?id=<?php echo $product['id_produk']; ?>" 
                               class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                               title="Edit Produk">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="log-stok.php?id=<?php echo $product['id_produk']; ?>" 
                               class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;" 
                               title="Log Stok">
                                <i class="fas fa-history"></i>
                            </a>
                            <a href="adjustment-stok.php?id=<?php echo $product['id_produk']; ?>" 
                               class="btn" style="padding: 5px 10px; font-size: 12px;" 
                               title="Adjustment Stok">
                                <i class="fas fa-plus-minus"></i>
                            </a>
                            <a href="hapus-produk.php?id=<?php echo $product['id_produk']; ?>" 
                               class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;"
                               onclick="return confirm('Yakin ingin menghapus produk ini?')" 
                               title="Hapus Produk">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>Belum ada produk yang terdaftar</p>
            <a href="tambah-produk.php" class="btn">Tambah Produk Pertama</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.text-muted {
    color: var(--text-light);
    font-size: 12px;
}
</style>

<?php endLayout(); ?>
