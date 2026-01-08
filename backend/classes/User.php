<?php
class User {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Đăng ký
    public function register($fullname, $email, $phone, $password) {
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Email đã được sử dụng!'];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $sql = "INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$fullname, $email, $phone, $hashedPassword]);
            
            return ['success' => true, 'message' => 'Đăng ký thành công!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    // Đăng nhập
    public function login($email, $password) {
        try {
            $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                return ['success' => true, 'message' => 'Đăng nhập thành công!', 'user' => $user];
            }
            
            return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    // Đăng xuất
    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Đăng xuất thành công!'];
    }
    
    // Kiểm tra email tồn tại
    public function emailExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch() ? true : false;
    }
    
    // Lấy thông tin user theo ID
    public function getUserById($id) {
        $sql = "SELECT id, fullname, email, phone, avatar, address, role, created_at FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Cập nhật thông tin user
    public function updateProfile($id, $fullname, $phone, $address) {
        try {
            $sql = "UPDATE users SET fullname = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$fullname, $phone, $address, $id]);
            
            $_SESSION['user_name'] = $fullname;
            
            return ['success' => true, 'message' => 'Cập nhật thành công!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    // Đổi mật khẩu
    public function changePassword($id, $oldPassword, $newPassword) {
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if (!password_verify($oldPassword, $result['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu cũ không đúng!'];
        }
        
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$hashedPassword, $id]);
            
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    // Kiểm tra đăng nhập
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Kiểm tra admin
    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    // Đăng nhập bằng Google
    public function loginWithGoogle($googleUser) {
        try {
            // Kiểm tra user đã tồn tại với google_id
            $sql = "SELECT * FROM users WHERE google_id = ? AND status = 'active'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$googleUser['id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Kiểm tra email đã tồn tại chưa
                $sql = "SELECT * FROM users WHERE email = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$googleUser['email']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Cập nhật google_id cho user đã có
                    $sql = "UPDATE users SET google_id = ?, avatar = COALESCE(avatar, ?) WHERE id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$googleUser['id'], $googleUser['picture'] ?? null, $user['id']]);
                } else {
                    // Tạo user mới
                    $sql = "INSERT INTO users (fullname, email, google_id, avatar, password) VALUES (?, ?, ?, ?, '')";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([
                        $googleUser['name'],
                        $googleUser['email'],
                        $googleUser['id'],
                        $googleUser['picture'] ?? null
                    ]);
                    $user = $this->getUserById($this->conn->lastInsertId());
                }
                
                // Lấy lại thông tin user
                if (!$user || !isset($user['id'])) {
                    $sql = "SELECT * FROM users WHERE google_id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$googleUser['id']]);
                    $user = $stmt->fetch();
                }
            }
            
            if ($user && $user['status'] === 'active') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                return ['success' => true, 'message' => 'Đăng nhập thành công!', 'user' => $user];
            }
            
            return ['success' => false, 'message' => 'Tài khoản không hoạt động!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}
