<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $card_id = $_POST['card_id'];
    $user_id = $_SESSION['user_id'];
    
    $title = $_POST['title'];
    $category = $_POST['category']; // รับค่าหมวดหมู่การ์ด
    $description = $_POST['description'];
    $starting_price = $_POST['starting_price'];
    $min_increment = $_POST['min_increment'];
    $end_time = $_POST['end_time'];

    // 1. ยืนยันสิทธิ์อีกครั้งเพื่อความปลอดภัย
    $check_sql = "SELECT image_url FROM cards WHERE id = ? AND owner_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $card_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows == 0) {
        die("<script>alert('ไม่มีสิทธิ์แก้ไขการ์ดใบนี้!'); window.location.href='../index.php';</script>");
    }
    
    $old_card = $result->fetch_assoc();
    $check_stmt->close();

    // 2. ตรวจสอบว่ามีการอัปโหลดรูปใหม่หรือไม่
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "../uploads/";
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_extension, $allowed_types)) {
            die("<script>alert('นามสกุลไฟล์รูปไม่ถูกต้อง'); window.history.back();</script>");
        }

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $old_file_path = "../uploads/" . $old_card['image_url'];
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
            
            // อัปเดต: เพิ่ม category (s) 
            $update_sql = "UPDATE cards SET title=?, category=?, description=?, starting_price=?, min_increment=?, image_url=? WHERE id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssddsi", $title, $category, $description, $starting_price, $min_increment, $new_filename, $card_id);
        } else {
            die("<script>alert('อัปโหลดรูปภาพผิดพลาด'); window.history.back();</script>");
        }
    } else {
        // กรณีไม่อัปโหลดรูปภาพใหม่ ให้อัปเดตเฉพาะข้อความรวมถึง category
        $update_sql = "UPDATE cards SET title=?, category=?, description=?, starting_price=?, min_increment=? WHERE id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssddi", $title, $category, $description, $starting_price, $min_increment, $card_id);
    }

    // 3. ทำการ Execute การอัปเดตลงตาราง cards
    if ($stmt->execute()) {
        $stmt->close();
        
        // 4. อัปเดตเวลาสิ้นสุดการประมูลในตาราง auctions
        $auction_sql = "UPDATE auctions SET end_time=? WHERE card_id=?";
        $auction_stmt = $conn->prepare($auction_sql);
        $auction_stmt->bind_param("si", $end_time, $card_id);
        $auction_stmt->execute();
        $auction_stmt->close();

        echo "<script>alert('แก้ไขข้อมูลสำเร็จ!'); window.location.href='../auction_room.php?id=$card_id';</script>";
    } else {
        echo "Error: " . $conn->error;
    }

    $conn->close();

} else {
    header("Location: ../index.php");
    exit();
}
?>