<?php
// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Render sidebar navigation
function renderSidebar($currentPage = '') {    $menuItems = [
        'dashboard.php' => ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard'],
        'produk.php' => ['icon' => 'fas fa-box', 'label' => 'Kelola Produk'],
        'kategori.php' => ['icon' => 'fas fa-tags', 'label' => 'Kelola Kategori'],
        'transaksi.php' => ['icon' => 'fas fa-cash-register', 'label' => 'Transaksi Penjualan'],
        'riwayat-transaksi.php' => ['icon' => 'fas fa-receipt', 'label' => 'Riwayat Transaksi'],
        'laporan-penjualan.php' => ['icon' => 'fas fa-chart-bar', 'label' => 'Laporan Penjualan']
    ];
    
    echo '<div class="sidebar">';
    echo '<div class="sidebar-header">';
    echo '<h3>🏪 Warung Cepripki</h3>';
    echo '<p>Sistem Manajemen</p>';
    echo '</div>';
    echo '<div class="sidebar-menu">';
    
    foreach ($menuItems as $page => $item) {
        $activeClass = ($currentPage === $page) ? 'active' : '';
        echo '<a href="' . $page . '" class="menu-item ' . $activeClass . '">';
        echo '<i class="' . $item['icon'] . '"></i>';
        echo $item['label'];
        echo '</a>';
    }
    
    echo '<a href="logout.php" class="menu-item" style="color: var(--danger-color); margin-top: 20px;">';
    echo '<i class="fas fa-sign-out-alt"></i>';
    echo 'Logout';
    echo '</a>';
    echo '</div>';
    echo '</div>';
}

// Render top bar
function renderTopbar($pageTitle, $userName) {
    echo '<div class="topbar">';
    echo '<button class="mobile-menu-toggle">';
    echo '<i class="fas fa-bars"></i>';
    echo '</button>';
    echo '<h1>' . htmlspecialchars($pageTitle) . '</h1>';
    echo '<div class="user-info">';
    echo '<span>Selamat datang, <strong>' . htmlspecialchars($userName) . '</strong></span>';
    echo '</div>';
    echo '</div>';
}

// Render page header
function renderPageHeader($title, $description = '') {
    echo '<div class="page-header">';
    echo '<h2>' . htmlspecialchars($title) . '</h2>';
    if ($description) {
        echo '<p>' . htmlspecialchars($description) . '</p>';
    }
    echo '</div>';
}

// Start layout
function startLayout($pageTitle, $currentPage = '') {
    checkLogin();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($pageTitle); ?> - Warung Cepripki</title>
        <link rel="stylesheet" href="assets/css/style.css">        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="dashboard-page">
        <div class="main-layout">
            <?php renderSidebar($currentPage); ?>
            <div class="content-area">
                <?php renderTopbar($pageTitle, $_SESSION['nama_lengkap']); ?>
    <?php
}

// Start layout tanpa topbar (untuk halaman produk)
function startLayoutNoTopbar($pageTitle, $currentPage = '') {
    checkLogin();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($pageTitle); ?> - Warung Cepripki</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="dashboard-page">
        <div class="main-layout">
            <?php renderSidebar($currentPage); ?>
            <div class="content-area" style="padding-top: 25px;">
                <!-- Mobile menu button for pages without topbar -->
                <button class="mobile-menu-toggle" style="position: absolute; top: 15px; left: 15px; z-index: 999; display: none;">
                    <i class="fas fa-bars"></i>
                </button>
    <?php
}

// End layout
function endLayout() {
    ?>
            </div>
        </div>        <script>
            // Mobile menu toggle
            const toggleButton = document.querySelector('.mobile-menu-toggle');
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    document.querySelector('.sidebar').classList.toggle('active');
                });
            }
            
            // Prevent double submission
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.classList.contains('btn-submitted')) {
                        submitBtn.classList.add('btn-loading');
                        submitBtn.disabled = true;
                        
                        setTimeout(() => {
                            submitBtn.classList.remove('btn-loading');
                            submitBtn.classList.add('btn-submitted');
                        }, 500);
                    }
                });
            });
            
            // Auto-hide alerts after 5 seconds
            document.querySelectorAll('.alert').forEach(alert => {
                if (!alert.querySelector('a')) { // Only auto-hide if no links
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => {
                            alert.remove();
                        }, 500);
                    }, 5000);
                }
            });
        </script>
    </body>
    </html>
    <?php
}
?>
