<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'];
    
    // ตรวจสอบการอัปโหลดไฟล์สลิป
    if (isset($_FILES["slip_image"]) && $_FILES["slip_image"]["error"] == 0) {
        
        $target_dir = "../uploads/slips/";
        
        // สร้างโฟลเดอร์ slips ถ้ายังไม่มี (สำคัญมาก)
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_types)) {
            // สร้างชื่อไฟล์ใหม่ไม่ให้ซ้ำ
            $new_filename = 'slip_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['slip_image']['tmp_name'], $target_file)) {
                
                // --- สำหรับโปรเจกต์นี้: เพื่อความรวดเร็วในการทดสอบ เราจะ "อนุมัติอัตโนมัติ" และเพิ่มเงินให้ทันที ---
                // (ถ้าเป็นระบบจริง สถานะตรงนี้ต้องเป็น 'pending' แล้วรอแอดมินมากด approve อีกที)
                
                // 1. บันทึกประวัติลงตาราง topups สถานะ approved เลย
                $sql = "INSERT INTO topups (user_id, amount, slip_image, status) VALUES (?, ?, ?, 'approved')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ids", $user_id, $amount, $new_filename);
                
                if ($stmt->execute()) {
                    // 2. อัปเดตยอดเงินในกระเป๋าผู้ใช้
                    $update_wallet_sql = "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_wallet_sql);
                    $update_stmt->bind_param("di", $amount, $user_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    echo "<script>alert('เติมเงิน ฿" . number_format($amount, 2) . " สำเร็จ ยอดเงินเข้ากระเป๋าแล้ว!'); window.location.href='../profile.php';</script>";
                } else {
                    echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล'); window.history.back();</script>";
                }
                $stmt->close();

            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('รองรับเฉพาะไฟล์รูปภาพ .jpg, .png, .webp เท่านั้น'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('กรุณาอัปโหลดไฟล์สลิป'); window.history.back();</script>";
    }

} else {
    header("Location: ../index.php");
    exit();
}
?>