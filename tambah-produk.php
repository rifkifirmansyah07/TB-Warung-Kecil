<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Tambah Produk', 'produk.php');

$success_message = '';
$error_message = '';

// Ambil kategori yang dipilih dari URL (jika ada)
$selected_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

// Ambil data kategori untuk dropdown
try {
    $conn = getConnection();
    $stmt = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data kategori.';
}

// Proses form submission
if ($_POST) {
    $nama_produk = trim($_POST['nama_produk']);
    $id_kategori = $_POST['id_kategori'] ?: null;
    $harga_beli = (float)$_POST['harga_beli'];
    $harga_jual = (float)$_POST['harga_jual'];
    $stok = (int)$_POST['stok'];
    $stok_minimum = (int)$_POST['stok_minimum'];
    $satuan = trim($_POST['satuan']);
    $barcode = trim($_POST['barcode']);
    $deskripsi = trim($_POST['deskripsi']);
    $status = $_POST['status'];
    
    // Validasi
    if (empty($nama_produk) || $harga_beli <= 0 || $harga_jual <= 0) {
        $error_message = 'Nama produk, harga beli, dan harga jual harus diisi dengan benar!';
    } elseif ($harga_jual <= $harga_beli) {
        $error_message = 'Harga jual harus lebih besar dari harga beli!';
    } else {
        try {
            // Cek apakah barcode sudah ada (jika diisi)
            if (!empty($barcode)) {
                $check_stmt = $conn->prepare("SELECT id_produk FROM produk WHERE barcode = ?");
                $check_stmt->execute([$barcode]);
                if ($check_stmt->fetch()) {
                    $error_message = 'Barcode sudah digunakan oleh produk lain!';
                }
            }
            
            if (empty($error_message)) {
                // Insert produk baru
                $stmt = $conn->prepare("
                    INSERT INTO produk (nama_produk, id_kategori, harga_beli, harga_jual, stok, stok_minimum, satuan, barcode, deskripsi, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $nama_produk, $id_kategori, $harga_beli, $harga_jual, 
                    $stok, $stok_minimum, $satuan, $barcode, $deskripsi, $status
                ]);
                
                // Log stok awal jika ada
                if ($stok > 0) {
                    $produk_id = $conn->lastInsertId();
                    $log_stmt = $conn->prepare("
                        INSERT INTO log_stok (id_produk, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan) 
                        VALUES (?, 'masuk', ?, 0, ?, 'INIT', 'Stok awal produk')
                    ");
                    $log_stmt->execute([$produk_id, $stok, $stok]);
                }
                
                $success_message = 'Produk berhasil ditambahkan!';
                
                // Reset form
                $_POST = [];
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan dalam menyimpan produk: ' . $e->getMessage();
        }
    }
}
?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
        <a href="produk.php" style="float: right; color: #155724; text-decoration: underline;">Kembali ke Daftar Produk</a>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav style="margin-bottom: 20px;">
    <a href="produk.php" style="color: var(--primary-color); text-decoration: none;">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Produk
    </a>
</nav>

<!-- Form Tambah Produk -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus"></i> Tambah Produk Baru - Form Input Data
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <!-- Kolom Kiri -->
                <div>
                    <div class="form-group mb-20">
                        <label for="nama_produk">Nama Produk *</label>
                        <input type="text" id="nama_produk" name="nama_produk" class="form-control" 
                               value="<?php echo isset($_POST['nama_produk']) ? htmlspecialchars($_POST['nama_produk']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group mb-20">
                        <label for="id_kategori">Kategori</label>
                        <select id="id_kategori" name="id_kategori" class="form-control">
                            <option value="">Pilih Kategori</option>                            <?php foreach ($categories as $category): 
                                $is_selected = false;
                                if (isset($_POST['id_kategori'])) {
                                    $is_selected = $_POST['id_kategori'] == $category['id_kategori'];
                                } elseif ($selected_kategori > 0) {
                                    $is_selected = $selected_kategori == $category['id_kategori'];
                                }
                            ?>
                                <option value="<?php echo $category['id_kategori']; ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group mb-20">
                            <label for="harga_beli">Harga Beli (Rp) *</label>
                            <input type="number" id="harga_beli" name="harga_beli" class="form-control" 
                                   value="<?php echo isset($_POST['harga_beli']) ? $_POST['harga_beli'] : ''; ?>" 
                                   min="0" step="1" required>
                        </div>
                        
                        <div class="form-group mb-20">
                            <label for="harga_jual">Harga Jual (Rp) *</label>
                            <input type="number" id="harga_jual" name="harga_jual" class="form-control" 
                                   value="<?php echo isset($_POST['harga_jual']) ? $_POST['harga_jual'] : ''; ?>" 
                                   min="0" step="1" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group mb-20">
                            <label for="stok">Stok Awal</label>
                            <input type="number" id="stok" name="stok" class="form-control" 
                                   value="<?php echo isset($_POST['stok']) ? $_POST['stok'] : '0'; ?>" 
                                   min="0">
                        </div>
                        
                        <div class="form-group mb-20">
                            <label for="stok_minimum">Stok Minimum</label>
                            <input type="number" id="stok_minimum" name="stok_minimum" class="form-control" 
                                   value="<?php echo isset($_POST['stok_minimum']) ? $_POST['stok_minimum'] : '5'; ?>" 
                                   min="0">
                        </div>
                    </div>
                </div>
                
                <!-- Kolom Kanan -->
                <div>
                    <div class="form-group mb-20">
                        <label for="barcode">Barcode/SKU</label>
                        <input type="text" id="barcode" name="barcode" class="form-control" 
                               value="<?php echo isset($_POST['barcode']) ? htmlspecialchars($_POST['barcode']) : ''; ?>" 
                               placeholder="Opsional">
                    </div>
                    
                    <div class="form-group mb-20">
                        <label for="satuan">Satuan</label>
                        <select id="satuan" name="satuan" class="form-control">
                            <option value="pcs" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'pcs') ? 'selected' : ''; ?>>Pcs</option>
                            <option value="kg" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'kg') ? 'selected' : ''; ?>>Kg</option>
                            <option value="gram" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'gram') ? 'selected' : ''; ?>>Gram</option>
                            <option value="liter" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'liter') ? 'selected' : ''; ?>>Liter</option>
                            <option value="ml" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'ml') ? 'selected' : ''; ?>>ML</option>
                            <option value="btl" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'btl') ? 'selected' : ''; ?>>Botol</option>
                            <option value="kotak" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'kotak') ? 'selected' : ''; ?>>Kotak</option>
                            <option value="bks" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'bks') ? 'selected' : ''; ?>>Bungkus</option>
                            <option value="strip" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'strip') ? 'selected' : ''; ?>>Strip</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-20">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="aktif" <?php echo (isset($_POST['status']) && $_POST['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo (isset($_POST['status']) && $_POST['status'] == 'nonaktif') ? 'selected' : ''; ?>>Non-aktif</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-20">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" 
                                  placeholder="Deskripsi produk (opsional)"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; gap: 15px;">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Simpan Produk
                </button>
                <a href="produk.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
                <button type="reset" class="btn btn-warning" onclick="return confirm('Yakin ingin reset form?')">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto calculate margin
document.getElementById('harga_beli').addEventListener('input', calculateMargin);
document.getElementById('harga_jual').addEventListener('input', calculateMargin);

function calculateMargin() {
    const hargaBeli = parseFloat(document.getElementById('harga_beli').value) || 0;
    const hargaJual = parseFloat(document.getElementById('harga_jual').value) || 0;
    
    if (hargaBeli > 0 && hargaJual > 0) {
        const margin = ((hargaJual - hargaBeli) / hargaBeli * 100).toFixed(2);
        const profit = hargaJual - hargaBeli;
        
        // Show margin info
        let marginInfo = document.getElementById('margin-info');
        if (!marginInfo) {
            marginInfo = document.createElement('small');
            marginInfo.id = 'margin-info';
            marginInfo.style.color = 'var(--text-light)';
            document.getElementById('harga_jual').parentNode.appendChild(marginInfo);
        }
        
        if (hargaJual <= hargaBeli) {
            marginInfo.innerHTML = '<span style="color: var(--danger-color);">⚠️ Harga jual harus lebih besar dari harga beli!</span>';
        } else {
            marginInfo.innerHTML = `💰 Keuntungan: Rp ${profit.toLocaleString()} (${margin}%)`;
        }
    }
}

// Generate barcode suggestion
document.getElementById('nama_produk').addEventListener('blur', function() {
    const barcode = document.getElementById('barcode');
    if (!barcode.value) {
        const timestamp = Date.now().toString().slice(-8);
        const random = Math.floor(Math.random() * 100).toString().padStart(2, '0');
        barcode.value = timestamp + random;
    }
});
</script>

<?php endLayout(); ?>
