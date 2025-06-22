<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';

$error_message = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password harus diisi!';
    } else {
        try {
            $conn = getConnection();
            
            // Cek apakah user ada dan tidak terkunci
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'aktif'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Cek apakah akun terkunci
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $error_message = 'Akun terkunci. Coba lagi nanti.';
                } else {
                    // Verifikasi password (gunakan password_verify jika menggunakan password_hash)
                    // Untuk demo ini, kita gunakan perbandingan sederhana
                    if (password_verify($password, $user['password_hash']) || $password === 'admin123') {
                        // Login berhasil
                        $_SESSION['user_id'] = $user['id_user'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                        
                        // Update last login
                        $update_stmt = $conn->prepare("CALL update_last_login(?)");
                        $update_stmt->execute([$user['id_user']]);
                        
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        // Login gagal - handle failed login
                        $failed_stmt = $conn->prepare("CALL handle_failed_login(?)");
                        $failed_stmt->execute([$user['id_user']]);
                        
                        $error_message = 'Username atau password salah!';
                    }
                }
            } else {
                $error_message = 'Username atau password salah!';
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warung Cepripki</title>    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h2>🏪 Warung Cepripki</h2>
                <p>Sistem Manajemen Warung</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div class="login-footer">
                <p>Demo Login:</p>
                <small>Username: <strong>owner</strong> | Password: <strong>admin123</strong></small>
            </div>
        </div>
    </div>
</body>
</html>
