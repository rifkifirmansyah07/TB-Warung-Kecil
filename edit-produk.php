<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Edit Produk', 'produk.php');

$success_message = '';
$error_message = '';
$product = null;

// Ambil ID produk dari URL
$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produk <= 0) {
    header('Location: produk.php');
    exit();
}

try {
    $conn = getConnection();
    
    // Ambil data produk
    $stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $error_message = 'Produk tidak ditemukan!';
    }
    
    // Ambil data kategori untuk dropdown
    $stmt = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data: ' . $e->getMessage();
}

// Proses form submission
if ($_POST && $product) {
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
            // Cek apakah barcode sudah ada di produk lain (jika diisi)
            if (!empty($barcode)) {
                $check_stmt = $conn->prepare("SELECT id_produk FROM produk WHERE barcode = ? AND id_produk != ?");
                $check_stmt->execute([$barcode, $id_produk]);
                if ($check_stmt->fetch()) {
                    $error_message = 'Barcode sudah digunakan oleh produk lain!';
                }
            }
            
            if (empty($error_message)) {
                // Cek perubahan stok untuk log
                $stok_lama = $product['stok'];
                $selisih_stok = $stok - $stok_lama;
                
                // Update produk
                $stmt = $conn->prepare("
                    UPDATE produk 
                    SET nama_produk = ?, id_kategori = ?, harga_beli = ?, harga_jual = ?, 
                        stok = ?, stok_minimum = ?, satuan = ?, barcode = ?, deskripsi = ?, status = ?
                    WHERE id_produk = ?
                ");
                $stmt->execute([
                    $nama_produk, $id_kategori, $harga_beli, $harga_jual, 
                    $stok, $stok_minimum, $satuan, $barcode, $deskripsi, $status, $id_produk
                ]);
                
                // Log perubahan stok jika ada
                if ($selisih_stok != 0) {
                    $jenis = $selisih_stok > 0 ? 'masuk' : 'keluar';
                    $jumlah = abs($selisih_stok);
                    
                    $log_stmt = $conn->prepare("
                        INSERT INTO log_stok (id_produk, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_input) 
                        VALUES (?, ?, ?, ?, ?, 'EDIT', 'Edit produk - perubahan stok manual', ?)
                    ");
                    $log_stmt->execute([$id_produk, $jenis, $jumlah, $stok_lama, $stok, $_SESSION['username']]);
                }
                
                $success_message = 'Produk berhasil diperbarui!';
                
                // Refresh data produk
                $stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
                $stmt->execute([$id_produk]);
                $product = $stmt->fetch();
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan dalam memperbarui produk: ' . $e->getMessage();
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

<?php if ($product): ?>
<!-- Form Edit Produk -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-edit"></i> Edit Produk: <?php echo htmlspecialchars($product['nama_produk']); ?>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <!-- Kolom Kiri -->
                <div>
                    <div class="form-group mb-20">
                        <label for="nama_produk">Nama Produk *</label>
                        <input type="text" id="nama_produk" name="nama_produk" class="form-control" 
                               value="<?php echo isset($_POST['nama_produk']) ? htmlspecialchars($_POST['nama_produk']) : htmlspecialchars($product['nama_produk']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group mb-20">
                        <label for="id_kategori">Kategori</label>
                        <select id="id_kategori" name="id_kategori" class="form-control">
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): 
                                $selected = (isset($_POST['id_kategori']) ? $_POST['id_kategori'] : $product['id_kategori']) == $category['id_kategori'];
                            ?>
                                <option value="<?php echo $category['id_kategori']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group mb-20">
                            <label for="harga_beli">Harga Beli (Rp) *</label>
                            <input type="number" id="harga_beli" name="harga_beli" class="form-control" 
                                   value="<?php echo isset($_POST['harga_beli']) ? $_POST['harga_beli'] : $product['harga_beli']; ?>" 
                                   min="0" step="1" required>
                        </div>
                        
                        <div class="form-group mb-20">
                            <label for="harga_jual">Harga Jual (Rp) *</label>
                            <input type="number" id="harga_jual" name="harga_jual" class="form-control" 
                                   value="<?php echo isset($_POST['harga_jual']) ? $_POST['harga_jual'] : $product['harga_jual']; ?>" 
                                   min="0" step="1" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group mb-20">
                            <label for="stok">Stok Saat Ini</label>
                            <input type="number" id="stok" name="stok" class="form-control" 
                                   value="<?php echo isset($_POST['stok']) ? $_POST['stok'] : $product['stok']; ?>" 
                                   min="0">
                            <small style="color: var(--text-light);">Stok lama: <?php echo number_format($product['stok']); ?></small>
                        </div>
                        
                        <div class="form-group mb-20">
                            <label for="stok_minimum">Stok Minimum</label>
                            <input type="number" id="stok_minimum" name="stok_minimum" class="form-control" 
                                   value="<?php echo isset($_POST['stok_minimum']) ? $_POST['stok_minimum'] : $product['stok_minimum']; ?>" 
                                   min="0">
                        </div>
                    </div>
                </div>
                
                <!-- Kolom Kanan -->
                <div>
                    <div class="form-group mb-20">
                        <label for="barcode">Barcode/SKU</label>
                        <input type="text" id="barcode" name="barcode" class="form-control" 
                               value="<?php echo isset($_POST['barcode']) ? htmlspecialchars($_POST['barcode']) : htmlspecialchars($product['barcode']); ?>" 
                               placeholder="Opsional">
                    </div>
                    
                    <div class="form-group mb-20">
                        <label for="satuan">Satuan</label>
                        <select id="satuan" name="satuan" class="form-control">
                            <?php 
                            $current_satuan = isset($_POST['satuan']) ? $_POST['satuan'] : $product['satuan'];
                            $satuan_options = ['pcs', 'kg', 'gram', 'liter', 'ml', 'btl', 'kotak', 'bks', 'strip'];
                            foreach ($satuan_options as $satuan): ?>
                                <option value="<?php echo $satuan; ?>" <?php echo ($current_satuan == $satuan) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($satuan); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-20">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <?php $current_status = isset($_POST['status']) ? $_POST['status'] : $product['status']; ?>
                            <option value="aktif" <?php echo ($current_status == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo ($current_status == 'nonaktif') ? 'selected' : ''; ?>>Non-aktif</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-20">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" 
                                  placeholder="Deskripsi produk (opsional)"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : htmlspecialchars($product['deskripsi']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Info Tambahan -->
            <div style="background: var(--secondary-color); padding: 15px; border-radius: 6px; margin: 20px 0;">
                <h4 style="margin-bottom: 10px; color: var(--text-color);">Informasi Produk</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px;">
                    <div>
                        <strong>Dibuat:</strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?>
                    </div>
                    <div>
                        <strong>Terakhir Diupdate:</strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($product['updated_at'])); ?>
                    </div>
                    <div>
                        <strong>Margin Keuntungan:</strong><br>
                        <?php 
                        $margin = (($product['harga_jual'] - $product['harga_beli']) / $product['harga_beli']) * 100;
                        echo number_format($margin, 2); 
                        ?>%
                    </div>
                    <div>
                        <strong>Keuntungan per Unit:</strong><br>
                        Rp <?php echo number_format($product['harga_jual'] - $product['harga_beli']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; gap: 15px;">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Update Produk
                </button>
                <a href="produk.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
                <a href="log-stok.php?id=<?php echo $product['id_produk']; ?>" class="btn btn-warning">
                    <i class="fas fa-history"></i> Log Stok
                </a>
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

// Show stock change warning
document.getElementById('stok').addEventListener('input', function() {
    const stokLama = <?php echo $product['stok']; ?>;
    const stokBaru = parseInt(this.value) || 0;
    const selisih = stokBaru - stokLama;
    
    let stockInfo = document.getElementById('stock-info');
    if (!stockInfo) {
        stockInfo = document.createElement('small');
        stockInfo.id = 'stock-info';
        this.parentNode.appendChild(stockInfo);
    }
    
    if (selisih !== 0) {
        const jenis = selisih > 0 ? 'penambahan' : 'pengurangan';
        const color = selisih > 0 ? 'var(--success-color)' : 'var(--danger-color)';
        stockInfo.innerHTML = `<span style="color: ${color};">📝 ${jenis.charAt(0).toUpperCase() + jenis.slice(1)} ${Math.abs(selisih)} unit akan dicatat dalam log</span>`;
    } else {
        stockInfo.innerHTML = '';
    }
});

// Calculate margin on load
calculateMargin();
</script>

<?php else: ?>
<div class="alert alert-danger">
    Produk tidak ditemukan atau sudah dihapus.
</div>
<?php endif; ?>

<?php endLayout(); ?>
