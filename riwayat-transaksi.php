<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Riwayat Transaksi', 'riwayat-transaksi.php');

$success_message = '';
$error_message = '';

// Cek pesan dari URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$kasir = isset($_GET['kasir']) ? $_GET['kasir'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20; // Tampilkan 20 transaksi per halaman
$offset = ($page - 1) * $limit;

try {
    $conn = getConnection();
    
    // Build query with filters
    $where_conditions = ["DATE(tp.tanggal_transaksi) BETWEEN ? AND ?"];
    $params = [$date_from, $date_to];
    
    if ($kasir) {
        $where_conditions[] = "tp.kasir LIKE ?";
        $params[] = "%$kasir%";
    }
    
    if ($search) {
        $where_conditions[] = "(tp.no_transaksi LIKE ? OR p.nama_pelanggan LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Ambil data transaksi
    $stmt = $conn->prepare("
        SELECT 
            tp.*,
            p.nama_pelanggan,
            COUNT(dt.id_detail) as total_items_detail
        FROM transaksi_penjualan tp
        LEFT JOIN pelanggan p ON tp.id_pelanggan = p.id_pelanggan
        LEFT JOIN detail_transaksi dt ON tp.id_transaksi = dt.id_transaksi
        WHERE $where_clause
        GROUP BY tp.id_transaksi        ORDER BY tp.tanggal_transaksi DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Hitung total transaksi untuk pagination
    $count_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT tp.id_transaksi) as total_transactions
        FROM transaksi_penjualan tp
        LEFT JOIN pelanggan p ON tp.id_pelanggan = p.id_pelanggan
        WHERE $where_clause
    ");
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total_transactions'];
    $total_pages = ceil($total_records / $limit);
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data transaksi.';
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

<!-- Breadcrumb -->
<nav style="margin-bottom: 20px;">
    <a href="dashboard.php" style="color: var(--primary-color); text-decoration: none;">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <span style="margin: 0 10px; color: var(--text-light);">></span>
    <span style="color: var(--text-color);">Riwayat Transaksi</span>
</nav>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="transaksi.php" class="btn">
        <i class="fas fa-plus"></i> Transaksi Baru
    </a>
    <a href="laporan-penjualan.php" class="btn btn-secondary">
        <i class="fas fa-chart-bar"></i> Laporan Penjualan
    </a>
    <button onclick="window.print()" class="btn btn-info">
        <i class="fas fa-print"></i> Cetak
    </button>
</div>

<!-- Filter -->
<div class="card mb-25">
    <div class="card-header">
        <i class="fas fa-filter"></i> Filter & Pencarian Transaksi
    </div>
    <div class="card-body">
        <form method="GET" action="" id="filter-form">
            <!-- Quick Filter Buttons -->
            <div style="margin-bottom: 15px;">
                <label>Filter Cepat:</label>
                <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-top: 5px;">
                    <button type="button" class="btn btn-sm" onclick="setDateFilter(0)">Hari Ini</button>
                    <button type="button" class="btn btn-sm" onclick="setDateFilter(1)">Kemarin</button>
                    <button type="button" class="btn btn-sm" onclick="setDateFilter(7)">7 Hari Terakhir</button>
                    <button type="button" class="btn btn-sm" onclick="setDateFilter(30)">30 Hari Terakhir</button>
                    <button type="button" class="btn btn-sm" onclick="setThisMonth()">Bulan Ini</button>
                    <button type="button" class="btn btn-sm" onclick="setLastMonth()">Bulan Lalu</button>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="form-group">
                    <label for="date_from">Tanggal Dari:</label>
                    <input type="date" id="date_from" name="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_to">Tanggal Sampai:</label>
                    <input type="date" id="date_to" name="date_to" class="form-control" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                
                <div class="form-group">
                    <label for="kasir">Kasir:</label>
                    <input type="text" id="kasir" name="kasir" class="form-control" 
                           value="<?php echo htmlspecialchars($kasir); ?>" placeholder="Nama kasir...">
                </div>
                
                <div class="form-group">
                    <label for="search">Cari Transaksi:</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                           placeholder="No. transaksi atau pelanggan...">
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="riwayat-transaksi.php" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a>                <div style="margin-left: auto; color: var(--text-light); font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    Menampilkan <?php echo count($transactions); ?> dari <?php echo number_format($total_records ?: 0); ?> transaksi
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Transaksi -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <i class="fas fa-list"></i> 
            Riwayat Transaksi 
            <span style="color: var(--text-light); font-weight: normal;">
                (<?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?>)
            </span>
        </div>        <div style="font-size: 14px; color: var(--text-light);">
            Total: <?php echo number_format($total_records); ?> transaksi 
            | Halaman: <?php echo $page; ?> dari <?php echo $total_pages; ?>
        </div>
    </div>    <div class="card-body" style="padding: 0;">
        <?php if (!empty($transactions)): ?>
        <div class="table-responsive">
            <table class="table"><thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($transaction['no_transaksi']); ?></strong>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($transaction['tanggal_transaksi'])); ?></td>
                    <td><?php echo htmlspecialchars($transaction['nama_pelanggan'] ?: 'Pelanggan Umum'); ?></td>
                    <td><?php echo htmlspecialchars($transaction['kasir']); ?></td>
                    <td>
                        <span class="status-badge status-success">
                            <?php echo number_format($transaction['total_item']); ?> item
                        </span>
                    </td>
                    <td>
                        <strong style="color: var(--primary-color);">
                            Rp <?php echo number_format($transaction['total_bayar']); ?>
                        </strong>
                        <?php if ($transaction['diskon'] > 0): ?>
                        <br><small style="color: var(--danger-color);">
                            Diskon: Rp <?php echo number_format($transaction['diskon']); ?>
                        </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <a href="detail-transaksi.php?id=<?php echo $transaction['id_transaksi']; ?>" 
                               class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                               title="Detail Transaksi">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="cetak-struk.php?id=<?php echo $transaction['id_transaksi']; ?>" 
                               class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;" 
                               title="Cetak Struk" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-receipt"></i>
            <p>Tidak ada transaksi dalam periode yang dipilih</p>
            <a href="transaksi.php" class="btn">Buat Transaksi Pertama</a>
        </div>        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="pagination-wrapper" style="margin: 20px 0; text-align: center;">
    <div class="pagination" style="display: inline-flex; gap: 5px; align-items: center;">
        <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
               class="btn btn-sm" title="Halaman Pertama">
                <i class="fas fa-angle-double-left"></i>
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
               class="btn btn-sm" title="Halaman Sebelumnya">
                <i class="fas fa-angle-left"></i>
            </a>
        <?php endif; ?>

        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="btn btn-sm">1</a>
            <?php if ($start_page > 2): ?>
                <span style="padding: 0 10px;">...</span>
            <?php endif;
        endif;

        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
               class="btn btn-sm <?php echo ($i == $page) ? 'btn-primary' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor;

        if ($end_page < $total_pages): 
            if ($end_page < $total_pages - 1): ?>
                <span style="padding: 0 10px;">...</span>
            <?php endif; ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="btn btn-sm"><?php echo $total_pages; ?></a>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
               class="btn btn-sm" title="Halaman Selanjutnya">
                <i class="fas fa-angle-right"></i>
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
               class="btn btn-sm" title="Halaman Terakhir">
                <i class="fas fa-angle-double-right"></i>
            </a>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 10px; font-size: 14px; color: var(--text-light);">
        Menampilkan <?php echo (($page - 1) * $limit) + 1; ?> - <?php echo min($page * $limit, $total_records); ?> 
        dari <?php echo number_format($total_records); ?> transaksi
    </div>
</div>
<?php endif; ?>

<script>
// Quick date filters
function setDateFilter(days) {
    const today = new Date();
    const fromDate = new Date(today);
    fromDate.setDate(today.getDate() - days);
    
    document.getElementById('date_from').value = fromDate.toISOString().split('T')[0];
    document.getElementById('date_to').value = today.toISOString().split('T')[0];
}

// Set this month filter
function setThisMonth() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('date_from').value = firstDay.toISOString().split('T')[0];
    document.getElementById('date_to').value = today.toISOString().split('T')[0];
}

// Set last month filter
function setLastMonth() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
    
    document.getElementById('date_from').value = firstDay.toISOString().split('T')[0];
    document.getElementById('date_to').value = lastDay.toISOString().split('T')[0];
}

// Auto submit form on Enter key
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('filter-form').submit();
        }
    });
    
    // Add loading state to buttons
    const submitBtn = document.querySelector('button[type="submit"]');
    const form = document.getElementById('filter-form');
    
    form.addEventListener('submit', function() {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
        submitBtn.disabled = true;
    });
});
</script>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-primary {
    background: var(--primary-color) !important;
    color: white !important;
    border-color: var(--primary-color) !important;
}

.pagination .btn {
    min-width: 35px;
    text-align: center;
}

/* Enhanced table styling */
.table th {
    background: var(--secondary-color);
    font-weight: 600;
    border-bottom: 2px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 10;
}

.table tr:hover {
    background: rgba(var(--primary-color-rgb), 0.05);
}

/* Search input enhancement */
#search {
    position: relative;
}

#search:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.2);
}

/* Filter section enhancement */
.form-group label {
    font-weight: 600;
    color: var(--text-color);
}

/* Quick filter buttons */
.btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Loading state */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .table-responsive {
        overflow-x: auto;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
}

@media print {
    .action-buttons, 
    .card:first-of-type, 
    .pagination-wrapper,
    .no-print {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    .table {
        font-size: 12px;
    }
    
    .table th,
    .table td {
        padding: 4px 6px;
    }
}
</style>

<?php endLayout(); ?>
