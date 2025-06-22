<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

try {
    $conn = getConnection();
    
    // Ambil statistik dashboard
    $stats = [];
    
    // Total Produk
    $stmt = $conn->query("SELECT COUNT(*) as total FROM produk WHERE status = 'aktif'");
    $stats['total_produk'] = $stmt->fetch()['total'];
    
    // Total Kategori
    $stmt = $conn->query("SELECT COUNT(*) as total FROM kategori");
    $stats['total_kategori'] = $stmt->fetch()['total'];
    
    // Penjualan Hari Ini
    $stmt = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(total_bayar), 0) as pendapatan FROM transaksi_penjualan WHERE DATE(tanggal_transaksi) = CURDATE()");
    $today_sales = $stmt->fetch();
    $stats['penjualan_hari_ini'] = $today_sales['total'];
    $stats['pendapatan_hari_ini'] = $today_sales['pendapatan'];
    
    // Produk Stok Menipis
    $stmt = $conn->query("SELECT COUNT(*) as total FROM produk WHERE stok <= stok_minimum AND status = 'aktif'");
    $stats['stok_menipis'] = $stmt->fetch()['total'];
    
    // Riwayat Transaksi Terbaru (10 terakhir)
    $stmt = $conn->query("
        SELECT 
            tp.no_transaksi,
            tp.tanggal_transaksi,
            p.nama_pelanggan,
            tp.total_item,
            tp.total_bayar,
            tp.kasir
        FROM transaksi_penjualan tp
        LEFT JOIN pelanggan p ON tp.id_pelanggan = p.id_pelanggan
        ORDER BY tp.tanggal_transaksi DESC
        LIMIT 10
    ");
    $recent_transactions = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data dashboard.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Warung Cepripki</title>
    <link rel="stylesheet" href="assets/css/style.css">    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-page">
    <div class="main-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>🏪 Warung Cepripki</h3>
                <p>Sistem Manajemen</p>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="produk.php" class="menu-item">
                    <i class="fas fa-box"></i>
                    Kelola Produk
                </a>
                <a href="kategori.php" class="menu-item">
                    <i class="fas fa-tags"></i>
                    Kelola Kategori
                </a>                <a href="transaksi.php" class="menu-item">
                    <i class="fas fa-cash-register"></i>
                    Transaksi Penjualan
                </a>
                <a href="riwayat-transaksi.php" class="menu-item">
                    <i class="fas fa-receipt"></i>
                    Riwayat Transaksi
                </a>
                <a href="laporan-penjualan.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    Laporan Penjualan
                </a>
                <a href="logout.php" class="menu-item" style="color: var(--danger-color); margin-top: 20px;">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Top Bar -->
            <div class="topbar">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong></span>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Produk</h3>
                    <div class="number"><?php echo number_format($stats['total_produk']); ?></div>
                    <div class="label">Produk Aktif</div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Kategori</h3>
                    <div class="number"><?php echo number_format($stats['total_kategori']); ?></div>
                    <div class="label">Kategori Produk</div>
                </div>
                
                <div class="stat-card">
                    <h3>Penjualan Hari Ini</h3>
                    <div class="number"><?php echo number_format($stats['penjualan_hari_ini']); ?></div>
                    <div class="label">Transaksi</div>
                </div>
                
                <div class="stat-card">
                    <h3>Pendapatan Hari Ini</h3>
                    <div class="number">Rp <?php echo number_format($stats['pendapatan_hari_ini']); ?></div>
                    <div class="label">Total Pendapatan</div>
                </div>
                
                <div class="stat-card">
                    <h3>Stok Menipis</h3>
                    <div class="number" style="color: var(--danger-color);"><?php echo number_format($stats['stok_menipis']); ?></div>
                    <div class="label">Produk Perlu Restock</div>
                </div>            </div>

            <!-- Quick Actions -->
            <div class="quick-actions" style="margin: 25px 0;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <a href="transaksi.php" class="action-card" style="background: linear-gradient(135deg, var(--primary-color), #4CAF50); color: white; text-decoration: none; padding: 20px; border-radius: 10px; display: block; transition: transform 0.2s;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-cash-register" style="font-size: 24px;"></i>
                            <div>
                                <h4 style="margin: 0; font-size: 16px;">Transaksi Baru</h4>
                                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Mulai penjualan</p>
                            </div>
                        </div>
                    </a>
                    
                    <a href="riwayat-transaksi.php" class="action-card" style="background: linear-gradient(135deg, #2196F3, #03A9F4); color: white; text-decoration: none; padding: 20px; border-radius: 10px; display: block; transition: transform 0.2s;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-receipt" style="font-size: 24px;"></i>
                            <div>
                                <h4 style="margin: 0; font-size: 16px;">Riwayat Transaksi</h4>
                                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Lihat semua transaksi</p>
                            </div>
                        </div>
                    </a>
                    
                    <a href="laporan-penjualan.php" class="action-card" style="background: linear-gradient(135deg, #FF9800, #FF5722); color: white; text-decoration: none; padding: 20px; border-radius: 10px; display: block; transition: transform 0.2s;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-chart-bar" style="font-size: 24px;"></i>
                            <div>
                                <h4 style="margin: 0; font-size: 16px;">Laporan Penjualan</h4>
                                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Analisis penjualan</p>
                            </div>
                        </div>
                    </a>
                    
                    <a href="produk.php" class="action-card" style="background: linear-gradient(135deg, #9C27B0, #E91E63); color: white; text-decoration: none; padding: 20px; border-radius: 10px; display: block; transition: transform 0.2s;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-box" style="font-size: 24px;"></i>
                            <div>
                                <h4 style="margin: 0; font-size: 16px;">Kelola Produk</h4>
                                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Manajemen produk</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>            <!-- Recent Transactions Table -->
            <div class="transaction-table">
                <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fas fa-history"></i> Riwayat Transaksi Terbaru</span>
                    <a href="riwayat-transaksi.php" class="btn btn-sm" style="padding: 8px 15px; font-size: 12px;">
                        <i class="fas fa-external-link-alt"></i> Lihat Semua
                    </a>
                </div>
                <?php if (!empty($recent_transactions)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Total Item</th>
                            <th>Total Bayar</th>
                            <th>Kasir</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($transaction['no_transaksi']); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['tanggal_transaksi'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['nama_pelanggan']); ?></td>
                            <td><?php echo number_format($transaction['total_item']); ?> item</td>
                            <td>Rp <?php echo number_format($transaction['total_bayar']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['kasir'] ?: 'System'); ?></td>
                            <td><span class="status-badge status-success">Selesai</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Belum ada transaksi yang tercatat</p>
                    <a href="transaksi.php" class="btn">Mulai Transaksi Pertama</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Action card hover effects
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
                this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
            });
        });

        // Auto refresh dashboard setiap 30 detik untuk statistik real-time
        setInterval(function() {
            // Hanya refresh statistik tanpa reload full page
            const currentHour = new Date().getHours();
            if (currentHour >= 8 && currentHour <= 22) { // Hanya refresh saat jam kerja
                fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.text())
                .then(html => {
                    // Update hanya bagian statistik
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newStats = doc.querySelector('.stats-grid');
                    if (newStats) {
                        document.querySelector('.stats-grid').innerHTML = newStats.innerHTML;
                    }
                })
                .catch(error => console.log('Auto refresh error:', error));
            }
        }, 30000);
    </script>
</body>
</html>
