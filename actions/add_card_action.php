<?php
session_start();
require_once '../includes/db_connect.php';

// ป้องกันคนเข้าหน้านี้โดยตรงผ่าน URL และต้องล็อกอินแล้วเท่านั้น
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $owner_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $starting_price = $_POST['starting_price'];
    $end_time = $_POST['end_time'];

    // 1. จัดการอัปโหลดรูปภาพ
    $target_dir = "../uploads/"; // โฟลเดอร์ปลายทาง
    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION)); // ดึงนามสกุลไฟล์
    
    // สร้างชื่อไฟล์ใหม่แบบสุ่ม (เช่น 65f2a1b.jpg) เพื่อป้องกันชื่อรูปซ้ำกัน
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // ตรวจสอบนามสกุลไฟล์เบื้องต้น
    $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_extension, $allowed_types)) {
        die("<script>alert('ไม่อนุญาตให้อัปโหลดไฟล์ประเภทนี้'); window.history.back();</script>");
    }

    // ย้ายไฟล์จาก Temp ไปยังโฟลเดอร์ uploads ของเรา
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        
        // 2. บันทึกข้อมูลลงตาราง cards
        $sql_card = "INSERT INTO cards (owner_id, title, description, image_url, starting_price) VALUES (?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($sql_card);
        $stmt1->bind_param("isssd", $owner_id, $title, $description, $new_filename, $starting_price);
        
        if ($stmt1->execute()) {
            // ดึง ID ของการ์ดที่เพิ่งถูกสร้างขึ้นมา
            $card_id = $stmt1->insert_id; 
            $stmt1->close();

            // 3. บันทึกข้อมูลลงตาราง auctions ทันทีเพื่อเปิดรอบประมูล
            $sql_auction = "INSERT INTO auctions (card_id, end_time, status) VALUES (?, ?, 'active')";
            $stmt2 = $conn->prepare($sql_auction);
            $stmt2->bind_param("is", $card_id, $end_time);
            $stmt2->execute();
            $stmt2->close();

            echo "<script>alert('ลงการ์ดประมูลสำเร็จ!'); window.location.href='../index.php';</script>";
        } else {
            echo "Error Database: " . $conn->error;
        }

    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ ตรวจสอบว่ามีโฟลเดอร์ uploads หรือไม่'); window.history.back();</script>";
    }
    
    $conn->close();

} else {
    header("Location: ../index.php");
    exit();
}
?>