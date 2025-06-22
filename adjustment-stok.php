<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Adjustment Stok', 'produk.php');

$success_message = '';
$error_message = '';
$product = null;

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
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data: ' . $e->getMessage();
}

// Proses form submission
if ($_POST && $product) {
    $jenis_adjustment = $_POST['jenis_adjustment'];
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = trim($_POST['keterangan']);
    
    if ($jumlah <= 0) {
        $error_message = 'Jumlah harus lebih dari 0!';
    } elseif ($jenis_adjustment == 'keluar' && $jumlah > $product['stok']) {
        $error_message = 'Jumlah pengurangan tidak boleh lebih dari stok yang tersedia!';
    } elseif (empty($keterangan)) {
        $error_message = 'Keterangan harus diisi!';
    } else {
        try {
            $conn->beginTransaction();
            
            $stok_lama = $product['stok'];
            
            if ($jenis_adjustment == 'masuk') {
                $stok_baru = $stok_lama + $jumlah;
            } else {
                $stok_baru = $stok_lama - $jumlah;
            }
            
            // Update stok produk
            $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE id_produk = ?");
            $stmt->execute([$stok_baru, $id_produk]);
            
            // Insert log stok
            $stmt = $conn->prepare("
                INSERT INTO log_stok (id_produk, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_input) 
                VALUES (?, 'adjustment', ?, ?, ?, 'ADJ-MANUAL', ?, ?)
            ");
            $stmt->execute([$id_produk, $jumlah, $stok_lama, $stok_baru, $keterangan, $_SESSION['username']]);
            
            $conn->commit();
            
            $success_message = "Adjustment stok berhasil! Stok {$jenis_adjustment} sebanyak {$jumlah} unit.";
            
            // Refresh data produk
            $stmt = $conn->prepare("
                SELECT p.*, COALESCE(k.nama_kategori, 'Tanpa Kategori') as nama_kategori 
                FROM produk p 
                LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                WHERE p.id_produk = ?
            ");
            $stmt->execute([$id_produk]);
            $product = $stmt->fetch();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = 'Terjadi kesalahan dalam adjustment stok: ' . $e->getMessage();
        }
    }
}
?>

<!-- Breadcrumb -->
<nav style="margin-bottom: 20px;">
    <a href="produk.php" style="color: var(--primary-color); text-decoration: none;">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Produk
    </a>
</nav>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
        <div style="margin-top: 10px;">
            <a href="log-stok.php?id=<?php echo $product['id_produk']; ?>" style="color: #155724; text-decoration: underline;">Lihat Log Stok</a> |
            <a href="produk.php" style="color: #155724; text-decoration: underline;">Kembali ke Daftar Produk</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<?php if ($product): ?>
<!-- Info Produk -->
<div class="card mb-25">
    <div class="card-header">
        <i class="fas fa-info-circle"></i> Adjustment Stok - Informasi Produk
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
                <strong>Stok Saat Ini:</strong><br>
                <span style="color: <?php echo ($product['stok'] <= $product['stok_minimum']) ? 'var(--danger-color)' : 'var(--success-color)'; ?>; font-weight: bold; font-size: 18px;">
                    <?php echo number_format($product['stok']); ?> <?php echo $product['satuan']; ?>
                </span>
                <?php if ($product['stok'] <= $product['stok_minimum']): ?>
                    <br><small style="color: var(--danger-color);">⚠️ Stok menipis (minimum: <?php echo $product['stok_minimum']; ?>)</small>
                <?php endif; ?>
            </div>
            <div>
                <strong>Harga Jual:</strong><br>
                Rp <?php echo number_format($product['harga_jual']); ?>
            </div>
        </div>
    </div>
</div>

<!-- Form Adjustment -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
    <!-- Form Penambahan Stok -->
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, var(--success-color) 0%, #6bb96f 100%);">
            <i class="fas fa-plus"></i> Penambahan Stok
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="jenis_adjustment" value="masuk">
                
                <div class="form-group mb-20">
                    <label for="jumlah_masuk">Jumlah Penambahan</label>
                    <input type="number" id="jumlah_masuk" name="jumlah" class="form-control" 
                           min="1" required placeholder="Masukkan jumlah">
                    <small style="color: var(--text-light);">Satuan: <?php echo $product['satuan']; ?></small>
                </div>
                
                <div class="form-group mb-20">
                    <label for="keterangan_masuk">Keterangan</label>
                    <textarea id="keterangan_masuk" name="keterangan" class="form-control" rows="3" 
                              required placeholder="Alasan penambahan stok (misal: pembelian, retur dari customer, dll)"></textarea>
                </div>
                
                <div id="preview_masuk" style="background: #d4edda; padding: 15px; border-radius: 6px; margin-bottom: 15px; display: none;">
                    <strong>Preview:</strong><br>
                    <span id="preview_text_masuk"></span>
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-plus"></i> Tambah Stok
                </button>
            </form>
        </div>
    </div>
    
    <!-- Form Pengurangan Stok -->
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, var(--danger-color) 0%, #c13b1b 100%);">
            <i class="fas fa-minus"></i> Pengurangan Stok
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="jenis_adjustment" value="keluar">
                
                <div class="form-group mb-20">
                    <label for="jumlah_keluar">Jumlah Pengurangan</label>
                    <input type="number" id="jumlah_keluar" name="jumlah" class="form-control" 
                           min="1" max="<?php echo $product['stok']; ?>" required placeholder="Masukkan jumlah">
                    <small style="color: var(--text-light);">
                        Maksimal: <?php echo number_format($product['stok']); ?> <?php echo $product['satuan']; ?>
                    </small>
                </div>
                
                <div class="form-group mb-20">
                    <label for="keterangan_keluar">Keterangan</label>
                    <textarea id="keterangan_keluar" name="keterangan" class="form-control" rows="3" 
                              required placeholder="Alasan pengurangan stok (misal: barang rusak, expired, hilang, dll)"></textarea>
                </div>
                
                <div id="preview_keluar" style="background: #f8d7da; padding: 15px; border-radius: 6px; margin-bottom: 15px; display: none;">
                    <strong>Preview:</strong><br>
                    <span id="preview_text_keluar"></span>
                </div>
                
                <button type="submit" class="btn btn-danger" style="width: 100%;" 
                        <?php echo ($product['stok'] <= 0) ? 'disabled' : ''; ?>>
                    <i class="fas fa-minus"></i> Kurangi Stok
                </button>
                
                <?php if ($product['stok'] <= 0): ?>
                    <small style="color: var(--danger-color);">Stok sudah habis, tidak dapat dikurangi lagi.</small>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mt-25">
    <div class="card-header">
        <i class="fas fa-bolt"></i> Quick Actions
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <button class="btn btn-secondary" onclick="quickAdjustment('masuk', 10, 'Pembelian rutin')">
                <i class="fas fa-shopping-cart"></i> +10 (Pembelian)
            </button>
            <button class="btn btn-secondary" onclick="quickAdjustment('masuk', 50, 'Restok bulanan')">
                <i class="fas fa-truck"></i> +50 (Restok)
            </button>
            <button class="btn btn-warning" onclick="quickAdjustment('keluar', 1, 'Barang rusak')">
                <i class="fas fa-exclamation-triangle"></i> -1 (Rusak)
            </button>
            <a href="log-stok.php?id=<?php echo $product['id_produk']; ?>" class="btn btn-secondary">
                <i class="fas fa-history"></i> Lihat Log Stok
            </a>
        </div>
    </div>
</div>

<script>
// Preview calculations
document.getElementById('jumlah_masuk').addEventListener('input', function() {
    updatePreview('masuk');
});

document.getElementById('jumlah_keluar').addEventListener('input', function() {
    updatePreview('keluar');
});

function updatePreview(jenis) {
    const jumlah = parseInt(document.getElementById('jumlah_' + jenis).value) || 0;
    const stokSekarang = <?php echo $product['stok']; ?>;
    
    if (jumlah > 0) {
        const stokBaru = jenis === 'masuk' ? (stokSekarang + jumlah) : (stokSekarang - jumlah);
        const preview = document.getElementById('preview_' + jenis);
        const previewText = document.getElementById('preview_text_' + jenis);
        
        if (jenis === 'keluar' && jumlah > stokSekarang) {
            previewText.innerHTML = '❌ <span style="color: var(--danger-color);">Jumlah melebihi stok yang tersedia!</span>';
        } else {
            const arrow = jenis === 'masuk' ? '↗️' : '↘️';
            const color = jenis === 'masuk' ? 'var(--success-color)' : 'var(--danger-color)';
            
            previewText.innerHTML = `${arrow} Stok akan berubah dari <strong>${stokSekarang.toLocaleString()}</strong> menjadi <strong style="color: ${color}">${stokBaru.toLocaleString()}</strong> <?php echo $product['satuan']; ?>`;
        }
        
        preview.style.display = 'block';
    } else {
        document.getElementById('preview_' + jenis).style.display = 'none';
    }
}

// Quick adjustment function
function quickAdjustment(jenis, jumlah, keterangan) {
    if (confirm(`Yakin ingin ${jenis === 'masuk' ? 'menambah' : 'mengurangi'} stok sebanyak ${jumlah} dengan keterangan "${keterangan}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const inputs = [
            {name: 'jenis_adjustment', value: jenis},
            {name: 'jumlah', value: jumlah},
            {name: 'keterangan', value: keterangan}
        ];
        
        inputs.forEach(input => {
            const inputEl = document.createElement('input');
            inputEl.type = 'hidden';
            inputEl.name = input.name;
            inputEl.value = input.value;
            form.appendChild(inputEl);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php else: ?>
<div class="alert alert-danger">
    Produk tidak ditemukan.
</div>
<?php endif; ?>

<?php endLayout(); ?>
