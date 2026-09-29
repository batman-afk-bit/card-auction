<?php
// เริ่มระบบ Session และเรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบว่ามีการส่งฟอร์มมาแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // รับค่าจากฟอร์ม
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. ตรวจสอบว่ารหัสผ่านและการยืนยันรหัสผ่านตรงกันหรือไม่
    if ($password !== $confirm_password) {
        die("<script>alert('รหัสผ่านไม่ตรงกัน กรุณาลองใหม่!'); window.history.back();</script>");
    }

    // 2. ตรวจสอบว่ามีอีเมลนี้ในระบบหรือยัง
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("<script>alert('อีเมลนี้ถูกใช้งานแล้ว!'); window.history.back();</script>");
    }
    $stmt->close();

    // 3. เข้ารหัสผ่าน (Password Hashing) ก่อนบันทึกลง Database
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. บันทึกข้อมูลลงฐานข้อมูล
    $insert_query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($insert_stmt->execute()) {
        // บันทึกสำเร็จ แจ้งเตือนและกลับไปหน้าแรก (เดี๋ยวเราค่อยเปลี่ยนไปหน้า login)
        echo "<script>alert('สมัครสมาชิกสำเร็จ!'); window.location.href='../index.php';</script>";
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }

    $insert_stmt->close();
    $conn->close();

} else {
    // ถ้าไม่ได้ส่งข้อมูลผ่านฟอร์ม ให้เด้งกลับไปหน้าสมัครสมาชิก
    header("Location: ../register.php");
    exit();
}
?>