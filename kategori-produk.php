<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Produk per Kategori', 'kategori.php');

$category = null;
$products = [];
$error_message = '';

$id_kategori = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kategori <= 0) {
    header('Location: kategori.php');
    exit();
}

try {
    $conn = getConnection();
    
    // Ambil data kategori
    $stmt = $conn->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
    $stmt->execute([$id_kategori]);
    $category = $stmt->fetch();
    
    if (!$category) {
        $error_message = 'Kategori tidak ditemukan!';
    } else {
        // Ambil produk dalam kategori ini
        $stmt = $conn->prepare("
            SELECT * FROM produk 
            WHERE id_kategori = ? 
            ORDER BY nama_produk ASC
        ");
        $stmt->execute([$id_kategori]);
        $products = $stmt->fetchAll();
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
    <a href="kategori.php" style="color: var(--primary-color); text-decoration: none;">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Kategori
    </a>
</nav>

<?php if ($category): ?>
<!-- Info Kategori -->
<div class="card mb-25">
    <div class="card-header">
        <i class="fas fa-tag"></i> Kategori: <?php echo htmlspecialchars($category['nama_kategori']); ?>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <strong>Nama Kategori:</strong><br>
                <?php echo htmlspecialchars($category['nama_kategori']); ?>
            </div>
            <div>
                <strong>Deskripsi:</strong><br>
                <?php echo htmlspecialchars($category['deskripsi'] ?: 'Tidak ada deskripsi'); ?>
            </div>
            <div>
                <strong>Jumlah Produk:</strong><br>
                <span style="color: var(--primary-color); font-weight: bold; font-size: 18px;">
                    <?php echo count($products); ?> produk
                </span>
            </div>
            <div>
                <strong>Dibuat:</strong><br>
                <?php echo date('d/m/Y H:i', strtotime($category['created_at'])); ?>
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 15px;">
            <a href="edit-kategori.php?id=<?php echo $category['id_kategori']; ?>" class="btn btn-secondary">
                <i class="fas fa-edit"></i> Edit Kategori
            </a>
            <a href="tambah-produk.php?kategori=<?php echo $category['id_kategori']; ?>" class="btn">
                <i class="fas fa-plus"></i> Tambah Produk ke Kategori
            </a>
        </div>
    </div>
</div>

<!-- Daftar Produk -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-box"></i> Produk dalam Kategori "<?php echo htmlspecialchars($category['nama_kategori']); ?>"
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($products)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th>Barcode</th>
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
                        <strong><?php echo htmlspecialchars($product['nama_produk']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($product['satuan']); ?></small>
                    </td>
                    <td>
                        <?php if ($product['barcode']): ?>
                            <code><?php echo htmlspecialchars($product['barcode']); ?></code>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>Rp <?php echo number_format($product['harga_beli']); ?></td>
                    <td>Rp <?php echo number_format($product['harga_jual']); ?></td>
                    <td>
                        <span style="color: <?php echo ($product['stok'] <= $product['stok_minimum']) ? 'var(--danger-color)' : 'var(--text-color)'; ?>">
                            <?php echo number_format($product['stok']); ?>
                        </span>
                        <?php if ($product['stok'] <= $product['stok_minimum']): ?>
                            <br><small style="color: var(--danger-color);">
                                <i class="fas fa-exclamation-triangle"></i> Stok Menipis
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo ($product['status'] === 'aktif') ? 'status-success' : 'status-cancelled'; ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                    </td>
                    <td>
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
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>Belum ada produk dalam kategori "<?php echo htmlspecialchars($category['nama_kategori']); ?>"</p>
            <a href="tambah-produk.php?kategori=<?php echo $category['id_kategori']; ?>" class="btn">
                Tambah Produk Pertama
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistik Kategori -->
<?php if (!empty($products)): ?>
<div class="card mt-25">
    <div class="card-header">
        <i class="fas fa-chart-bar"></i> Statistik Kategori
    </div>
    <div class="card-body">
        <?php
        $total_produk = count($products);
        $total_stok = 0;
        $produk_aktif = 0;
        $produk_stok_menipis = 0;
        $total_nilai_beli = 0;
        $total_nilai_jual = 0;
        
        foreach ($products as $product) {
            $total_stok += $product['stok'];
            if ($product['status'] === 'aktif') $produk_aktif++;
            if ($product['stok'] <= $product['stok_minimum']) $produk_stok_menipis++;
            $total_nilai_beli += ($product['harga_beli'] * $product['stok']);
            $total_nilai_jual += ($product['harga_jual'] * $product['stok']);
        }
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 15px; background: #e3f2fd; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #1976d2;">
                    <?php echo number_format($total_produk); ?>
                </div>
                <div style="color: #1976d2;">Total Produk</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f3e5f5; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #7b1fa2;">
                    <?php echo number_format($total_stok); ?>
                </div>
                <div style="color: #7b1fa2;">Total Stok</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #d4edda; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #155724;">
                    <?php echo number_format($produk_aktif); ?>
                </div>
                <div style="color: #155724;">Produk Aktif</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f8d7da; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #721c24;">
                    <?php echo number_format($produk_stok_menipis); ?>
                </div>
                <div style="color: #721c24;">Stok Menipis</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 6px;">
                <div style="font-size: 18px; font-weight: bold; color: #856404;">
                    Rp <?php echo number_format($total_nilai_beli); ?>
                </div>
                <div style="color: #856404;">Nilai Stok (Beli)</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: var(--secondary-color); border-radius: 6px;">
                <div style="font-size: 18px; font-weight: bold; color: var(--primary-color);">
                    Rp <?php echo number_format($total_nilai_jual); ?>
                </div>
                <div style="color: var(--text-color);">Nilai Stok (Jual)</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="alert alert-danger">
    Kategori tidak ditemukan.
</div>
<?php endif; ?>

<?php endLayout(); ?>
