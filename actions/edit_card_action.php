<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $card_id = $_POST['card_id'];
    $user_id = $_SESSION['user_id'];
    
    $title = $_POST['title'];
    $category = $_POST['category']; 
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

    // 2. ตรวจสอบว่ามีการอัปโหลดรูปภาพใหม่หรือไม่ (เช็ก error ว่าไม่ใช่ 4 หรือ NO_FILE)
    if (isset($_FILES["images"]) && $_FILES["images"]["error"][0] != 4) {
        
        // --- 2.1 ลบไฟล์รูปภาพเก่าทิ้งออกจาก Server ---
        $old_img_sql = "SELECT image_url FROM card_images WHERE card_id = ?";
        $stmt_old = $conn->prepare($old_img_sql);
        $stmt_old->bind_param("i", $card_id);
        $stmt_old->execute();
        $res_old = $stmt_old->get_result();
        while($row_img = $res_old->fetch_assoc()){
            $old_file_path = "../uploads/" . $row_img['image_url'];
            if(file_exists($old_file_path) && is_file($old_file_path)) {
                unlink($old_file_path);
            }
        }
        $stmt_old->close();

        // ลบรูปปกหลักของเดิมด้วย
        $old_cover = "../uploads/" . $old_card['image_url'];
        if(file_exists($old_cover) && is_file($old_cover)) {
            unlink($old_cover);
        }

        // --- 2.2 เคลียร์ข้อมูลในตารางแกลลอรี ---
        $del_img_sql = "DELETE FROM card_images WHERE card_id = ?";
        $stmt_del = $conn->prepare($del_img_sql);
        $stmt_del->bind_param("i", $card_id);
        $stmt_del->execute();
        $stmt_del->close();

        // --- 2.3 อัปโหลดไฟล์ใหม่เข้า Server ---
        $uploaded_files = [];
        $target_dir = "../uploads/";
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $file_extension = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                if (in_array($file_extension, $allowed_types)) {
                    $new_filename = uniqid() . '_' . $key . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $uploaded_files[] = $new_filename;
                    }
                }
            }
        }

        // --- 2.4 บันทึกข้อมูลและรูปใหม่ลงฐานข้อมูล ---
        if (!empty($uploaded_files)) {
            $cover_image = $uploaded_files[0]; // รูปแรกคือปก
            
            // อัปเดตตารางหลัก
            $update_sql = "UPDATE cards SET title=?, category=?, description=?, starting_price=?, min_increment=?, image_url=? WHERE id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssddsi", $title, $category, $description, $starting_price, $min_increment, $cover_image, $card_id);
            $stmt->execute();
            $stmt->close();

            // Insert แกลลอรีชุดใหม่
            $img_sql = "INSERT INTO card_images (card_id, image_url) VALUES (?, ?)";
            $img_stmt = $conn->prepare($img_sql);
            foreach ($uploaded_files as $file) {
                $img_stmt->bind_param("is", $card_id, $file);
                $img_stmt->execute();
            }
            $img_stmt->close();
        }

    } else {
        // กรณี "ไม่อัปโหลดรูปภาพใหม่" ให้อัปเดตเฉพาะข้อความธรรมดา
        $update_sql = "UPDATE cards SET title=?, category=?, description=?, starting_price=?, min_increment=? WHERE id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssddi", $title, $category, $description, $starting_price, $min_increment, $card_id);
        $stmt->execute();
        $stmt->close();
    }

    // อัปเดตเวลาสิ้นสุดการประมูลในตาราง auctions
    $auction_sql = "UPDATE auctions SET end_time=? WHERE card_id=?";
    $auction_stmt = $conn->prepare($auction_sql);
    $auction_stmt->bind_param("si", $end_time, $card_id);
    $auction_stmt->execute();
    $auction_stmt->close();

    echo "<script>alert('แก้ไขข้อมูลสำเร็จ!'); window.location.href='../auction_room.php?id=$card_id';</script>";
    $conn->close();

} else {
    header("Location: ../index.php");
    exit();
}
?>