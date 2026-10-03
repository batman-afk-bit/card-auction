<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $owner_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $starting_price = $_POST['starting_price'];
    $min_increment = $_POST['min_increment'];
    $end_time = $_POST['end_time'];
    
    // 1. จัดการอัปโหลดไฟล์หลายไฟล์ (Multiple Uploads)
    $uploaded_files = [];
    $target_dir = "../uploads/";
    $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

    // วนลูปตามจำนวนไฟล์ที่ส่งมาผ่าน name="images[]"
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] == 0) {
            $file_extension = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed_types)) {
                // สร้างชื่อไฟล์ใหม่ไม่ให้ซ้ำกัน
                $new_filename = uniqid() . '_' . $key . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $uploaded_files[] = $new_filename; // เก็บชื่อไฟล์ที่อัปโหลดสำเร็จไว้ใน Array
                }
            }
        }
    }

    if (empty($uploaded_files)) {
        die("<script>alert('กรุณาอัปโหลดรูปภาพอย่างน้อย 1 รูป'); window.history.back();</script>");
    }

    // กำหนดให้รูปแรกใน Array เป็น "รูปหน้าปก"
    $cover_image = $uploaded_files[0]; 

    // 2. บันทึกข้อมูลลงตาราง cards หลัก (ใช้รูปหน้าปก)
    $sql = "INSERT INTO cards (title, category, description, starting_price, min_increment, image_url, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssddsi", $title, $category, $description, $starting_price, $min_increment, $cover_image, $owner_id);
    
    if ($stmt->execute()) {
        $card_id = $stmt->insert_id; // ดึง ID ของการ์ดที่เพิ่งถูกสร้างขึ้นมาใช้งานต่อ
        
        // 3. บันทึกข้อมูลลงตาราง auctions เพื่อตั้งเวลาประมูล (อัปเดต: เพิ่ม seller_id ลงไปบันทึกด้วย)
        $auction_sql = "INSERT INTO auctions (card_id, seller_id, end_time, status) VALUES (?, ?, ?, 'active')";
        $auction_stmt = $conn->prepare($auction_sql);
        $auction_stmt->bind_param("iis", $card_id, $owner_id, $end_time);
        $auction_stmt->execute();
        $auction_stmt->close();

        // 4. บันทึกรูปภาพ "ทั้งหมด" ลงตาราง card_images (แกลลอรี)
        $img_sql = "INSERT INTO card_images (card_id, image_url) VALUES (?, ?)";
        $img_stmt = $conn->prepare($img_sql);
        foreach ($uploaded_files as $file) {
            $img_stmt->bind_param("is", $card_id, $file);
            $img_stmt->execute();
        }
        $img_stmt->close();

        echo "<script>alert('เพิ่มการ์ดประมูลสำเร็จ!'); window.location.href='../index.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>