<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    die('Unauthorized');
}

$conn = getConnection();

// Kiểm tra cột loyalty_points có tồn tại không
$stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'loyalty_points'");
$columnExists = $stmt->fetch();

if (!$columnExists) {
    // Thêm cột nếu chưa có
    $conn->exec("ALTER TABLE users ADD COLUMN loyalty_points INT DEFAULT 0");
    echo "Đã thêm cột loyalty_points<br>";
}

// Cập nhật điểm từ lịch sử
$stmt = $conn->query("
    SELECT user_id, 
           SUM(CASE WHEN type = 'earn' THEN points ELSE -ABS(points) END) as total_points
    FROM loyalty_history 
    GROUP BY user_id
");
$results = $stmt->fetchAll();

foreach ($results as $row) {
    $points = max(0, (int)$row['total_points']);
    $conn->prepare("UPDATE users SET loyalty_points = ? WHERE id = ?")->execute([$points, $row['user_id']]);
    echo "User #{$row['user_id']}: {$points} điểm<br>";
}

echo "<br><strong>Hoàn tất! <a href='../frontend/profile.php'>Quay lại Profile</a></strong>";
