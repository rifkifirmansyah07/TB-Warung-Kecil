<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Tambah Kategori', 'kategori.php');

$success_message = '';
$error_message = '';

// Proses form submission
if ($_POST) {
    $nama_kategori = trim($_POST['nama_kategori']);
    $deskripsi = trim($_POST['deskripsi']);
    
    // Validasi
    if (empty($nama_kategori)) {
        $error_message = 'Nama kategori harus diisi!';
    } else {
        try {
            $conn = getConnection();
            
            // Cek apakah nama kategori sudah ada
            $check_stmt = $conn->prepare("SELECT id_kategori FROM kategori WHERE nama_kategori = ?");
            $check_stmt->execute([$nama_kategori]);
            if ($check_stmt->fetch()) {
                $error_message = 'Nama kategori sudah ada!';
            } else {
                // Insert kategori baru
                $stmt = $conn->prepare("
                    INSERT INTO kategori (nama_kategori, deskripsi) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$nama_kategori, $deskripsi]);
                
                $success_message = 'Kategori berhasil ditambahkan!';
                
                // Reset form
                $_POST = [];
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan dalam menyimpan kategori: ' . $e->getMessage();
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

<!-- Form Tambah Kategori -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus"></i> Tambah Kategori Baru - Form Input Data
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="form-group mb-20">
                    <label for="nama_kategori">Nama Kategori *</label>
                    <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" 
                           value="<?php echo isset($_POST['nama_kategori']) ? htmlspecialchars($_POST['nama_kategori']) : ''; ?>" 
                           required placeholder="Contoh: Makanan, Minuman, ATK, dll">
                </div>
                
                <div class="form-group mb-20">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" 
                              placeholder="Deskripsi kategori (opsional)"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                    <small class="form-help">Deskripsi akan membantu mengidentifikasi jenis produk dalam kategori ini</small>
                </div>
                
                <!-- Preview -->
                <div id="preview-card" style="background: var(--secondary-color); padding: 15px; border-radius: 6px; margin-bottom: 20px; display: none;">
                    <h4 style="margin-bottom: 10px; color: var(--text-color);">Preview Kategori</h4>
                    <div style="background: white; padding: 15px; border-radius: 4px; border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong id="preview-nama" style="color: var(--primary-color);">Nama Kategori</strong>
                            <span class="status-badge status-success">0 Produk</span>
                        </div>
                        <p id="preview-deskripsi" style="color: var(--text-light); margin: 0;">Deskripsi kategori</p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Simpan Kategori
                    </button>
                    <a href="kategori.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <button type="reset" class="btn btn-warning" onclick="return confirm('Yakin ingin reset form?')">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tips -->
<div class="card mt-25">
    <div class="card-header">
        <i class="fas fa-lightbulb"></i> Tips Membuat Kategori
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <h4 style="color: var(--primary-color); margin-bottom: 10px;">📝 Penamaan yang Baik</h4>
                <ul style="color: var(--text-color); line-height: 1.6;">
                    <li>Gunakan nama yang jelas dan mudah dipahami</li>
                    <li>Hindari nama yang terlalu panjang</li>
                    <li>Konsisten dengan kategori yang sudah ada</li>
                </ul>
            </div>
            
            <div>
                <h4 style="color: var(--primary-color); margin-bottom: 10px;">🎯 Organisasi Produk</h4>
                <ul style="color: var(--text-color); line-height: 1.6;">
                    <li>Kelompokkan produk yang sejenis</li>
                    <li>Pertimbangkan kemudahan pencarian</li>
                    <li>Buat kategori berdasarkan kebutuhan bisnis</li>
                </ul>
            </div>
            
            <div>
                <h4 style="color: var(--primary-color); margin-bottom: 10px;">💡 Contoh Kategori</h4>
                <ul style="color: var(--text-color); line-height: 1.6;">
                    <li><strong>Makanan:</strong> Mie, Snack, Roti</li>
                    <li><strong>Minuman:</strong> Air mineral, Teh, Kopi</li>
                    <li><strong>ATK:</strong> Pulpen, Pensil, Kertas</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time preview
document.getElementById('nama_kategori').addEventListener('input', updatePreview);
document.getElementById('deskripsi').addEventListener('input', updatePreview);

function updatePreview() {
    const nama = document.getElementById('nama_kategori').value.trim();
    const deskripsi = document.getElementById('deskripsi').value.trim();
    const previewCard = document.getElementById('preview-card');
    
    if (nama) {
        document.getElementById('preview-nama').textContent = nama;
        document.getElementById('preview-deskripsi').textContent = deskripsi || 'Tidak ada deskripsi';
        previewCard.style.display = 'block';
    } else {
        previewCard.style.display = 'none';
    }
}

// Auto capitalize first letter
document.getElementById('nama_kategori').addEventListener('input', function() {
    let value = this.value;
    if (value.length > 0) {
        this.value = value.charAt(0).toUpperCase() + value.slice(1);
    }
});
</script>

<?php endLayout(); ?>
