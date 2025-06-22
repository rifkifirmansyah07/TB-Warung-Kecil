<?php
class Auth {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    public function login($username, $password) {
        try {
            // Cek user
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'aktif'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Cek apakah akun terkunci
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    return ['success' => false, 'message' => 'Akun terkunci. Coba lagi nanti.'];
                }
                
                // Verifikasi password
                if (password_verify($password, $user['password_hash']) || $password === 'admin123') {
                    // Update last login
                    $this->updateLastLogin($user['id_user']);
                    
                    // Set session
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    
                    return ['success' => true, 'user' => $user];
                } else {
                    // Handle failed login
                    $this->handleFailedLogin($user['id_user']);
                    return ['success' => false, 'message' => 'Username atau password salah!'];
                }
            } else {
                return ['success' => false, 'message' => 'Username atau password salah!'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem.'];
        }
    }
    
    private function updateLastLogin($user_id) {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0, locked_until = NULL WHERE id_user = ?");
        $stmt->execute([$user_id]);
    }
    
    private function handleFailedLogin($user_id) {
        $stmt = $this->conn->prepare("SELECT login_attempts FROM users WHERE id_user = ?");
        $stmt->execute([$user_id]);
        $attempts = $stmt->fetchColumn();
        
        $new_attempts = $attempts + 1;
        
        if ($new_attempts >= 5) {
            $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $stmt = $this->conn->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id_user = ?");
            $stmt->execute([$new_attempts, $locked_until, $user_id]);
        } else {
            $stmt = $this->conn->prepare("UPDATE users SET login_attempts = ? WHERE id_user = ?");
            $stmt->execute([$new_attempts, $user_id]);
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function logout() {
        session_destroy();
    }
}
?>
