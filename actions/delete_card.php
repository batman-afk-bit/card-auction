<?php
session_start();
require_once '../includes/db_connect.php';

// เช็กว่าล็อกอินและมีการส่งค่า id มาหรือไม่
if (isset($_SESSION['user_id']) && isset($_GET['id'])) {
    
    $card_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // 1. ตรวจสอบก่อนว่าคนที่สั่งลบ เป็นเจ้าของการ์ดใบนี้จริงๆ หรือไม่ (ดักคนแฮ็กผ่าน URL)
    $check_sql = "SELECT image_url FROM cards WHERE id = ? AND owner_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $card_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // 2. ลบรูปภาพออกจากโฟลเดอร์ uploads (เพื่อคืนพื้นที่ให้เซิร์ฟเวอร์)
        $file_path = "../uploads/" . $row['image_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // 3. ลบข้อมูลจากฐานข้อมูล (เนื่องจากเราตั้ง ON DELETE CASCADE ไว้ในตอนออกแบบ 
        // พอเราลบการ์ด ข้อมูล auctions และ bids ที่เชื่อมอยู่จะถูกลบตามไปด้วยอัตโนมัติ)
        $delete_sql = "DELETE FROM cards WHERE id = ?";
        $del_stmt = $conn->prepare($delete_sql);
        $del_stmt->bind_param("i", $card_id);
        
        if ($del_stmt->execute()) {
            echo "<script>alert('ลบการ์ดสำเร็จ!'); window.location.href='../index.php';</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการลบ'); window.history.back();</script>";
        }
        $del_stmt->close();
    } else {
        // ไม่พบการ์ด หรือไม่ได้เป็นเจ้าของการ์ด
        echo "<script>alert('คุณไม่มีสิทธิ์ลบการ์ดใบนี้!'); window.location.href='../index.php';</script>";
    }
    
    $check_stmt->close();
    $conn->close();

} else {
    header("Location: ../index.php");
    exit();
}
?>