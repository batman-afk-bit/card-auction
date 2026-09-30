<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $owner_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $category = $_POST['category']; // อัปเดต: รับค่าหมวดหมู่การ์ด
    $description = $_POST['description'];
    $starting_price = $_POST['starting_price'];
    $min_increment = $_POST['min_increment'];
    $end_time = $_POST['end_time'];

    $target_dir = "../uploads/";
    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_extension, $allowed_types)) {
        die("<script>alert('ไม่อนุญาตให้อัปโหลดไฟล์ประเภทนี้'); window.history.back();</script>");
    }

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        
        // อัปเดต: เพิ่ม category ลงในคำสั่ง SQL และเปลี่ยน bind_param เป็น issssdd
        $sql_card = "INSERT INTO cards (owner_id, title, category, description, image_url, starting_price, min_increment) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($sql_card);
        // issssdd = integer(1), string(4), decimal/double(2)
        $stmt1->bind_param("issssdd", $owner_id, $title, $category, $description, $new_filename, $starting_price, $min_increment);
        
        if ($stmt1->execute()) {
            $card_id = $stmt1->insert_id; 
            $stmt1->close();

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
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ'); window.history.back();</script>";
    }
    $conn->close();
} else {
    header("Location: ../index.php");
    exit();
}
?>