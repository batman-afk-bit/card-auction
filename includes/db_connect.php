<?php
$host = "localhost";
$username = "root"; // ชื่อผู้ใช้เริ่มต้นของ XAMPP
$password = ""; // รหัสผ่านเริ่มต้นของ XAMPP จะเป็นค่าว่าง
$dbname = "card_auction_db"; // ชื่อฐานข้อมูลที่เราเพิ่งสร้าง

// สร้างการเชื่อมต่อ (Connect to MySQL)
$conn = new mysqli($host, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// หากต้องการทดสอบว่าเชื่อมต่อสำเร็จหรือไม่ สามารถเอาคอมเมนต์บรรทัดล่างออกได้ชั่วคราว
// echo "Connected successfully"; 
?>