<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Edit Kategori', 'kategori.php');

$success_message = '';
$error_message = '';
$category = null;

// Ambil ID kategori dari URL
$id_kategori = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kategori <= 0) {
    header('Location: kategori.php');
    exit();
}

try {
    $conn = getConnection();
    
    // Ambil data kategori
    $stmt = $conn->prepare("
        SELECT k.*, COUNT(p.id_produk) as jumlah_produk 
        FROM kategori k 
        LEFT JOIN produk p ON k.id_kategori = p.id_kategori 
        WHERE k.id_kategori = ?
        GROUP BY k.id_kategori
    ");
    $stmt->execute([$id_kategori]);
    $category = $stmt->fetch();
    
    if (!$category) {
        $error_message = 'Kategori tidak ditemukan!';
    }
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data: ' . $e->getMessage();
}

// Proses form submission
if ($_POST && $category) {
    $nama_kategori = trim($_POST['nama_kategori']);
    $deskripsi = trim($_POST['deskripsi']);
    
    // Validasi
    if (empty($nama_kategori)) {
        $error_message = 'Nama kategori harus diisi!';
    } else {
        try {
            // Cek apakah nama kategori sudah ada di kategori lain
            $check_stmt = $conn->prepare("SELECT id_kategori FROM kategori WHERE nama_kategori = ? AND id_kategori != ?");
            $check_stmt->execute([$nama_kategori, $id_kategori]);
            if ($check_stmt->fetch()) {
                $error_message = 'Nama kategori sudah digunakan oleh kategori lain!';
            } else {
                // Update kategori
                $stmt = $conn->prepare("
                    UPDATE kategori 
                    SET nama_kategori = ?, deskripsi = ? 
                    WHERE id_kategori = ?
                ");
                $stmt->execute([$nama_kategori, $deskripsi, $id_kategori]);
                
                $success_message = 'Kategori berhasil diperbarui!';
                
                // Refresh data kategori
                $stmt = $conn->prepare("
                    SELECT k.*, COUNT(p.id_produk) as jumlah_produk 
                    FROM kategori k 
                    LEFT JOIN produk p ON k.id_kategori = p.id_kategori 
                    WHERE k.id_kategori = ?
                    GROUP BY k.id_kategori
                ");
                $stmt->execute([$id_kategori]);
                $category = $stmt->fetch();
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan dalam memperbarui kategori: ' . $e->getMessage();
        }
    }
}
?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
        <a href="kategori.php" style="float: right; color: #155724; text-decoration: underline;">Kembali ke Daftar Kategori</a>
    </div>
<?php endif; ?>

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
<!-- Form Edit Kategori -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-edit"></i> Edit Kategori: <?php echo htmlspecialchars($category['nama_kategori']); ?>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="form-group mb-20">
                    <label for="nama_kategori">Nama Kategori *</label>
                    <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" 
                           value="<?php echo isset($_POST['nama_kategori']) ? htmlspecialchars($_POST['nama_kategori']) : htmlspecialchars($category['nama_kategori']); ?>" 
                           required>
                </div>
                
                <div class="form-group mb-20">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" 
                              placeholder="Deskripsi kategori (opsional)"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : htmlspecialchars($category['deskripsi']); ?></textarea>
                </div>
                
                <!-- Preview -->
                <div id="preview-card" style="background: var(--secondary-color); padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4 style="margin-bottom: 10px; color: var(--text-color);">Preview Kategori</h4>
                    <div style="background: white; padding: 15px; border-radius: 4px; border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong id="preview-nama" style="color: var(--primary-color);"><?php echo htmlspecialchars($category['nama_kategori']); ?></strong>
                            <span class="status-badge status-success"><?php echo number_format($category['jumlah_produk']); ?> Produk</span>
                        </div>
                        <p id="preview-deskripsi" style="color: var(--text-light); margin: 0;"><?php echo htmlspecialchars($category['deskripsi'] ?: 'Tidak ada deskripsi'); ?></p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Update Kategori
                    </button>
                    <a href="kategori.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <?php if ($category['jumlah_produk'] > 0): ?>
                        <a href="produk.php" class="btn btn-warning">
                            <i class="fas fa-box"></i> Lihat Produk (<?php echo $category['jumlah_produk']; ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Info Kategori -->
<div class="card mt-25">
    <div class="card-header">
        <i class="fas fa-info-circle"></i> Informasi Kategori
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div>
                <strong>Dibuat:</strong><br>
                <?php echo date('d/m/Y H:i', strtotime($category['created_at'])); ?>
            </div>
            <div>
                <strong>Terakhir Diupdate:</strong><br>
                <?php echo date('d/m/Y H:i', strtotime($category['updated_at'])); ?>
            </div>
            <div>
                <strong>Jumlah Produk:</strong><br>
                <span style="color: <?php echo ($category['jumlah_produk'] > 0) ? 'var(--success-color)' : 'var(--text-light)'; ?>; font-weight: bold;">
                    <?php echo number_format($category['jumlah_produk']); ?> produk
                </span>
            </div>
            <div>
                <strong>Status:</strong><br>
                <span class="status-badge <?php echo ($category['jumlah_produk'] > 0) ? 'status-success' : 'status-pending'; ?>">
                    <?php echo ($category['jumlah_produk'] > 0) ? 'Aktif Digunakan' : 'Belum Digunakan'; ?>
                </span>
            </div>
        </div>
        
        <?php if ($category['jumlah_produk'] > 0): ?>
        <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 6px; border: 1px solid #c3e6cb;">
            <h4 style="color: #155724; margin-bottom: 10px;">💡 Informasi Penting</h4>
            <p style="color: #155724; margin: 0;">
                Kategori ini sedang digunakan oleh <strong><?php echo number_format($category['jumlah_produk']); ?> produk</strong>. 
                Jika Anda mengubah nama kategori, perubahan akan otomatis diterapkan ke semua produk yang menggunakan kategori ini.
            </p>
        </div>
        <?php else: ?>
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px; border: 1px solid #ffeaa7;">
            <h4 style="color: #856404; margin-bottom: 10px;">⚠️ Kategori Belum Digunakan</h4>
            <p style="color: #856404; margin: 0;">
                Kategori ini belum memiliki produk. Anda dapat menambahkan produk ke kategori ini atau menghapus kategori jika tidak diperlukan.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Real-time preview
document.getElementById('nama_kategori').addEventListener('input', updatePreview);
document.getElementById('deskripsi').addEventListener('input', updatePreview);

function updatePreview() {
    const nama = document.getElementById('nama_kategori').value.trim();
    const deskripsi = document.getElementById('deskripsi').value.trim();
    
    document.getElementById('preview-nama').textContent = nama || 'Nama Kategori';
    document.getElementById('preview-deskripsi').textContent = deskripsi || 'Tidak ada deskripsi';
}

// Auto capitalize first letter
document.getElementById('nama_kategori').addEventListener('input', function() {
    let value = this.value;
    if (value.length > 0) {
        this.value = value.charAt(0).toUpperCase() + value.slice(1);
    }
});
</script>

<?php else: ?>
<div class="alert alert-danger">
    Kategori tidak ditemukan atau sudah dihapus.
</div>
<?php endif; ?>

<?php endLayout(); ?>
