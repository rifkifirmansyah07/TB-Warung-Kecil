<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Laporan Penjualan', 'laporan-penjualan.php');

$success_message = '';
$error_message = '';

// Filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';

try {
    $conn = getConnection();
    
    // Statistik Umum
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(total_bayar) as total_revenue,
            SUM(total_item) as total_items_sold,
            SUM(diskon) as total_discount,
            AVG(total_bayar) as avg_transaction,
            MAX(total_bayar) as max_transaction,
            MIN(total_bayar) as min_transaction
        FROM transaksi_penjualan
        WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
    ");
    $stats_stmt->execute([$date_from, $date_to]);
    $stats = $stats_stmt->fetch();
    
    // Laporan berdasarkan jenis
    if ($report_type === 'daily') {
        // Laporan Harian
        $stmt = $conn->prepare("
            SELECT * FROM laporan_penjualan_harian
            WHERE tanggal BETWEEN ? AND ?
            ORDER BY tanggal DESC
        ");
        $stmt->execute([$date_from, $date_to]);
        $daily_reports = $stmt->fetchAll();
    } elseif ($report_type === 'products') {
        // Laporan Produk Terlaris
        $stmt = $conn->prepare("
            SELECT 
                p.nama_produk,
                COALESCE(k.nama_kategori, 'Tanpa Kategori') as kategori,
                SUM(dt.jumlah) as total_terjual,
                SUM(dt.subtotal - dt.diskon_item) as total_pendapatan,
                COUNT(DISTINCT dt.id_transaksi) as frekuensi_transaksi,
                ROUND(AVG(dt.harga_satuan), 2) as rata_rata_harga
            FROM detail_transaksi dt
            JOIN produk p ON dt.id_produk = p.id_produk
            LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
            JOIN transaksi_penjualan tp ON dt.id_transaksi = tp.id_transaksi
            WHERE DATE(tp.tanggal_transaksi) BETWEEN ? AND ?
            GROUP BY p.id_produk, p.nama_produk, k.nama_kategori
            ORDER BY total_terjual DESC
            LIMIT 50
        ");
        $stmt->execute([$date_from, $date_to]);
        $product_reports = $stmt->fetchAll();
    } elseif ($report_type === 'categories') {
        // Laporan Per Kategori
        $stmt = $conn->prepare("
            SELECT 
                COALESCE(k.nama_kategori, 'Tanpa Kategori') as kategori,
                COUNT(DISTINCT dt.id_produk) as jumlah_produk,
                SUM(dt.jumlah) as total_terjual,
                SUM(dt.subtotal - dt.diskon_item) as total_pendapatan,
                COUNT(DISTINCT dt.id_transaksi) as frekuensi_transaksi
            FROM detail_transaksi dt
            JOIN produk p ON dt.id_produk = p.id_produk
            LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
            JOIN transaksi_penjualan tp ON dt.id_transaksi = tp.id_transaksi
            WHERE DATE(tp.tanggal_transaksi) BETWEEN ? AND ?
            GROUP BY k.id_kategori, k.nama_kategori
            ORDER BY total_pendapatan DESC
        ");
        $stmt->execute([$date_from, $date_to]);
        $category_reports = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat laporan: ' . $e->getMessage();
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
    <span style="color: var(--text-color);">Laporan Penjualan</span>
</nav>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="riwayat-transaksi.php" class="btn">
        <i class="fas fa-list"></i> Riwayat Transaksi
    </a>
    <button onclick="window.print()" class="btn btn-warning">
        <i class="fas fa-print"></i> Cetak Laporan
    </button>
</div>

<!-- Filter Laporan -->
<div class="card mb-25">
    <div class="card-header">
        <i class="fas fa-filter"></i> Filter Laporan
    </div>    <div class="card-body">
        <form method="GET" action="" id="report-form">
            <!-- Quick Filter Buttons -->
            <div style="margin-bottom: 15px;">
                <label>Filter Cepat:</label>
                <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-top: 5px;">
                    <button type="button" class="btn btn-sm" onclick="setDateFilter(0)">Hari Ini</button>
                    <button type="button" class="btn btn-sm" onclick="setDateFilter(1)">Kemarin</button>
                    <button type="button" class="btn btn-sm" onclick="setDateFilter(7)">7 Hari</button>
                    <button type="button" class="btn btn-sm" onclick="setDateFilter(30)">30 Hari</button>
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
                    <label for="report_type">Jenis Laporan:</label>
                    <select id="report_type" name="report_type" class="form-control">
                        <option value="daily" <?php echo ($report_type === 'daily') ? 'selected' : ''; ?>>Laporan Harian</option>
                        <option value="products" <?php echo ($report_type === 'products') ? 'selected' : ''; ?>>Produk Terlaris</option>
                        <option value="categories" <?php echo ($report_type === 'categories') ? 'selected' : ''; ?>>Per Kategori</option>
                    </select>
                </div>
            </div>
              <div style="display: flex; gap: 10px; align-items: center;">
                <button type="submit" class="btn" id="generate-btn">
                    <i class="fas fa-search"></i> Generate Laporan
                </button>
                <a href="laporan-penjualan.php" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a>
                <div style="margin-left: auto; color: var(--text-light); font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    Periode: <?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Statistik Umum -->
<?php if ($stats): ?>
<div class="stats-grid mb-25">
    <div class="stat-card">
        <h3>Total Transaksi</h3>
        <div class="number"><?php echo number_format($stats['total_transactions']); ?></div>
        <div class="label">Transaksi</div>
    </div>
    
    <div class="stat-card">
        <h3>Total Pendapatan</h3>
        <div class="number">Rp <?php echo number_format($stats['total_revenue'] ?: 0); ?></div>
        <div class="label">Rupiah</div>
    </div>
    
    <div class="stat-card">
        <h3>Item Terjual</h3>
        <div class="number"><?php echo number_format($stats['total_items_sold'] ?: 0); ?></div>
        <div class="label">Item</div>
    </div>
    
    <div class="stat-card">
        <h3>Rata-rata Transaksi</h3>
        <div class="number">Rp <?php echo number_format($stats['avg_transaction'] ?: 0); ?></div>
        <div class="label">Per Transaksi</div>
    </div>
    
    <div class="stat-card">
        <h3>Transaksi Tertinggi</h3>
        <div class="number">Rp <?php echo number_format($stats['max_transaction'] ?: 0); ?></div>
        <div class="label">Maksimal</div>
    </div>
    
    <div class="stat-card">
        <h3>Total Diskon</h3>
        <div class="number">Rp <?php echo number_format($stats['total_discount'] ?: 0); ?></div>
        <div class="label">Rupiah</div>
    </div>
</div>
<?php endif; ?>

<!-- Laporan Harian -->
<?php if ($report_type === 'daily' && isset($daily_reports)): ?>
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <i class="fas fa-calendar-day"></i> 
            Laporan Penjualan Harian 
            <span style="color: var(--text-light); font-weight: normal;">
                (<?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?>)
            </span>
        </div>
        <div style="font-size: 14px; color: var(--text-light);">
            <?php echo count($daily_reports); ?> hari data
        </div>
    </div><div class="card-body" style="padding: 0;">
        <?php if (!empty($daily_reports)): ?>
        <div class="table-responsive">
            <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Total Transaksi</th>
                    <th>Item Terjual</th>
                    <th>Subtotal</th>
                    <th>Diskon</th>
                    <th>Pajak</th>
                    <th>Total Penjualan</th>
                    <th>Rata-rata</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daily_reports as $report): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($report['tanggal'])); ?></td>
                    <td><?php echo number_format($report['total_transaksi']); ?></td>
                    <td><?php echo number_format($report['total_item_terjual']); ?></td>
                    <td>Rp <?php echo number_format($report['total_subtotal']); ?></td>
                    <td>Rp <?php echo number_format($report['total_diskon']); ?></td>
                    <td>Rp <?php echo number_format($report['total_pajak']); ?></td>
                    <td><strong style="color: var(--primary-color);">Rp <?php echo number_format($report['total_penjualan']); ?></strong></td>
                    <td>Rp <?php echo number_format($report['rata_rata_transaksi']); ?></td>
                </tr>                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-line"></i>
            <p>Tidak ada data penjualan dalam periode yang dipilih</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Summary Analytics -->
<?php if (!empty($daily_reports) && $report_type === 'daily'): ?>
<div class="card mt-25">
    <div class="card-header">
        <i class="fas fa-chart-pie"></i> Analisis Periode
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php 
            $bestDay = array_reduce($daily_reports, function($best, $day) {
                return ($day['total_penjualan'] > ($best['total_penjualan'] ?? 0)) ? $day : $best;
            }, []);
            $avgDaily = array_sum(array_column($daily_reports, 'total_penjualan')) / count($daily_reports);
            ?>
            <div class="analysis-card">
                <h4><i class="fas fa-crown" style="color: #FFD700;"></i> Hari Terbaik</h4>
                <p><strong><?php echo $bestDay ? date('d/m/Y', strtotime($bestDay['tanggal'])) : 'N/A'; ?></strong></p>
                <p>Rp <?php echo number_format($bestDay['total_penjualan'] ?? 0); ?></p>
            </div>
            <div class="analysis-card">
                <h4><i class="fas fa-chart-line" style="color: #28a745;"></i> Rata-rata Harian</h4>
                <p>Rp <?php echo number_format($avgDaily); ?></p>
                <small><?php echo count($daily_reports); ?> hari aktif</small>
            </div>
            <div class="analysis-card">
                <h4><i class="fas fa-percentage" style="color: #007bff;"></i> Konsistensi</h4>
                <?php 
                $consistency = count($daily_reports) > 1 ? 
                    (1 - (max(array_column($daily_reports, 'total_penjualan')) - min(array_column($daily_reports, 'total_penjualan'))) / max(array_column($daily_reports, 'total_penjualan'))) * 100 : 100;
                ?>
                <p><?php echo number_format($consistency, 1); ?>%</p>
                <small>Tingkat konsistensi penjualan</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($product_reports) && $report_type === 'products'): ?>
<div class="card mt-25">
    <div class="card-header">
        <i class="fas fa-trophy"></i> Top Performers
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php 
            $topProduct = $product_reports[0] ?? null;
            $totalRevenue = array_sum(array_column($product_reports, 'total_pendapatan'));
            $totalSold = array_sum(array_column($product_reports, 'total_terjual'));
            ?>
            <div class="analysis-card">
                <h4><i class="fas fa-star" style="color: #FFD700;"></i> Produk #1</h4>
                <p><strong><?php echo htmlspecialchars($topProduct['nama_produk'] ?? 'N/A'); ?></strong></p>
                <p><?php echo number_format($topProduct['total_terjual'] ?? 0); ?> unit terjual</p>
            </div>
            <div class="analysis-card">
                <h4><i class="fas fa-coins" style="color: #28a745;"></i> Total Revenue</h4>
                <p>Rp <?php echo number_format($totalRevenue); ?></p>
                <small>Dari <?php echo count($product_reports); ?> produk</small>
            </div>
            <div class="analysis-card">
                <h4><i class="fas fa-boxes" style="color: #007bff;"></i> Total Unit</h4>
                <p><?php echo number_format($totalSold); ?> unit</p>
                <small>Rata-rata <?php echo number_format($totalSold / max(count($product_reports), 1)); ?> per produk</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($category_reports) && $report_type === 'categories'): ?>
<div class="card mt-25">
    <div class="card-header">
        <i class="fas fa-tags"></i> Analisis Kategori
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php 
            $topCategory = $category_reports[0] ?? null;
            $totalCategoryRevenue = array_sum(array_column($category_reports, 'total_pendapatan'));
            $avgProductsPerCategory = array_sum(array_column($category_reports, 'jumlah_produk')) / max(count($category_reports), 1);
            ?>
            <div class="analysis-card">
                <h4><i class="fas fa-award" style="color: #FFD700;"></i> Kategori Terbaik</h4>
                <p><strong><?php echo htmlspecialchars($topCategory['kategori'] ?? 'N/A'); ?></strong></p>
                <p>Rp <?php echo number_format($topCategory['total_pendapatan'] ?? 0); ?></p>
            </div>
            <div class="analysis-card">
                <h4><i class="fas fa-chart-bar" style="color: #28a745;"></i> Total Kategori</h4>
                <p><?php echo count($category_reports); ?> kategori</p>
                <small>Rata-rata <?php echo number_format($avgProductsPerCategory, 1); ?> produk per kategori</small>
            </div>
            <div class="analysis-card">
                <h4><i class="fas fa-percentage" style="color: #007bff;"></i> Dominasi</h4>
                <?php 
                $dominance = $totalCategoryRevenue > 0 ? (($topCategory['total_pendapatan'] ?? 0) / $totalCategoryRevenue) * 100 : 0;
                ?>
                <p><?php echo number_format($dominance, 1); ?>%</p>
                <small>Kontribusi kategori teratas</small>
            </div>
        </div>
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

// Enhanced form handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('report-form');
    const generateBtn = document.getElementById('generate-btn');
    
    // Add loading state
    form.addEventListener('submit', function() {
        generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        generateBtn.disabled = true;
    });
    
    // Auto-submit when report type changes
    document.getElementById('report_type').addEventListener('change', function() {
        form.submit();
    });
    
    // Add chart visualization (basic)
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
});

// Initialize basic charts if Chart.js is available
function initializeCharts() {
    const reportType = '<?php echo $report_type; ?>';
    
    if (reportType === 'daily') {
        // Daily chart would go here
        console.log('Daily chart would be initialized here');
    } else if (reportType === 'products') {
        // Product chart would go here
        console.log('Product chart would be initialized here');
    }
}

// Print optimization
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});
</script>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
    transition: all 0.2s ease;
}

.btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Enhanced table styling */
.table {
    margin-bottom: 0;
}

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

.table td, .table th {
    vertical-align: middle;
    padding: 12px 8px;
}

/* Report type specific styling */
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-success {
    background: #d4edda;
    color: #155724;
}

/* Stats grid enhancement */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: var(--text-light);
    font-weight: 600;
}

.stat-card .number {
    font-size: 24px;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 5px;
}

.stat-card .label {
    font-size: 12px;
    color: var(--text-light);
}

/* Form enhancements */
.form-group label {
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 5px;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.2);
}

/* Loading state */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Responsive design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .card-body {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card .number {
        font-size: 20px;
    }
}

/* Print optimizations */
@media print {
    .action-buttons, 
    .card:first-of-type, 
    .no-print {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
    
    .table {
        font-size: 11px;
    }
    
    .table th,
    .table td {
        padding: 4px 6px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .stat-card {
        padding: 10px;
        box-shadow: none !important;
        border: 1px solid #ddd;
    }
    
    /* Page breaks */
    .card + .card {
        page-break-before: auto;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .stat-card {
        background: #2d3748;
        color: #e2e8f0;
    }
    
    .stat-card h3 {
        color: #a0aec0;
    }
}

/* Animation for content loading */
.card {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Enhanced empty state */
.empty-state {
    padding: 40px 20px;
    text-align: center;
    color: var(--text-light);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 16px;
}

.analysis-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e9ecef;
}

.analysis-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: var(--text-color);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.analysis-card p {
    margin: 5px 0;
    font-size: 18px;
    font-weight: bold;
    color: var(--primary-color);
}

.analysis-card small {
    color: var(--text-light);
    font-size: 12px;
}
</style>

<?php endLayout(); ?>
