<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Kelola Kategori', 'kategori.php');

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
    
    // Ambil data kategori dengan jumlah produk
    $stmt = $conn->query("
        SELECT 
            k.id_kategori,
            k.nama_kategori,
            k.deskripsi,
            k.created_at,
            COUNT(p.id_produk) as jumlah_produk
        FROM kategori k
        LEFT JOIN produk p ON k.id_kategori = p.id_kategori AND p.status = 'aktif'
        GROUP BY k.id_kategori, k.nama_kategori, k.deskripsi, k.created_at
        ORDER BY k.nama_kategori ASC
    ");
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data kategori.';
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
    <a href="tambah-kategori.php" class="btn">
        <i class="fas fa-plus"></i> Tambah Kategori Baru
    </a>
</div>

<!-- Categories Grid -->
<div class="card-grid">
    <?php foreach ($categories as $category): ?>
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($category['nama_kategori']); ?></span>
            <span class="status-badge status-success"><?php echo number_format($category['jumlah_produk']); ?> Produk</span>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 15px; color: var(--text-light);">
                <?php echo htmlspecialchars($category['deskripsi'] ?: 'Tidak ada deskripsi'); ?>
            </p>
            <small style="color: var(--text-light);">
                Dibuat: <?php echo date('d/m/Y', strtotime($category['created_at'])); ?>
            </small>            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <a href="edit-kategori.php?id=<?php echo $category['id_kategori']; ?>" class="btn btn-secondary" style="flex: 1; text-align: center;">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php if ($category['jumlah_produk'] > 0): ?>
                <a href="kategori-produk.php?id=<?php echo $category['id_kategori']; ?>" class="btn btn-warning" style="flex: 1; text-align: center;">
                    <i class="fas fa-eye"></i> Lihat Produk
                </a>
                <?php endif; ?>
                <?php if ($category['jumlah_produk'] == 0): ?>
                <a href="hapus-kategori.php?id=<?php echo $category['id_kategori']; ?>" 
                   class="btn btn-danger" style="flex: 1; text-align: center;"
                   onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                    <i class="fas fa-trash"></i> Hapus
                </a>
                <?php else: ?>
                <button class="btn btn-danger" style="flex: 1; opacity: 0.5;" disabled title="Tidak dapat dihapus karena masih ada produk">
                    <i class="fas fa-trash"></i> Hapus
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Categories Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Kelola Kategori - Daftar Semua Kategori
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($categories)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Produk</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($category['nama_kategori']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($category['deskripsi'] ?: '-'); ?></td>
                    <td>
                        <span class="status-badge status-success">
                            <?php echo number_format($category['jumlah_produk']); ?> produk
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($category['created_at'])); ?></td>                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <a href="edit-kategori.php?id=<?php echo $category['id_kategori']; ?>" 
                               class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                               title="Edit Kategori">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($category['jumlah_produk'] > 0): ?>
                            <a href="kategori-produk.php?id=<?php echo $category['id_kategori']; ?>" 
                               class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;" 
                               title="Lihat Produk">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($category['jumlah_produk'] == 0): ?>
                            <a href="hapus-kategori.php?id=<?php echo $category['id_kategori']; ?>" 
                               class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;"
                               onclick="return confirm('Yakin ingin menghapus kategori ini?')" 
                               title="Hapus Kategori">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tags"></i>
            <p>Belum ada kategori yang terdaftar</p>
            <a href="tambah-kategori.php" class="btn">Tambah Kategori Pertama</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>
