<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. ค้นหาผู้ใช้จากอีเมล
    $sql = "SELECT id, username, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // 2. ตรวจสอบว่าพบอีเมลนี้ในระบบหรือไม่
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // 3. ตรวจสอบความถูกต้องของรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            
            // รหัสผ่านถูกต้อง สร้าง Session จดจำผู้ใช้
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            echo "<script>alert('เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับคุณ " . $user['username'] . "'); window.location.href='../index.php';</script>";
        } else {
            // รหัสผ่านผิด
            echo "<script>alert('รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่'); window.history.back();</script>";
        }
    } else {
        // ไม่พบอีเมลในระบบ
        echo "<script>alert('ไม่พบบัญชีผู้ใช้นี้ในระบบ'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: ../login.php");
    exit();
}
?>