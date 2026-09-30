<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $auction_id = $_POST['auction_id'];
    $card_id = $_POST['card_id'];
    $bid_amount = $_POST['bid_amount'];
    $user_id = $_SESSION['user_id'];

    // ดึงราคาปัจจุบัน และ ค่า min_increment ของการ์ดใบนี้จากฐานข้อมูล
    $sql_check = "SELECT COALESCE(MAX(b.bid_amount), c.starting_price) as current_price, a.status, c.min_increment 
                  FROM auctions a 
                  JOIN cards c ON a.card_id = c.id 
                  LEFT JOIN bids b ON a.id = b.auction_id 
                  WHERE a.id = ? GROUP BY a.id";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $auction_id);
    $stmt_check->execute();
    $res = $stmt_check->get_result()->fetch_assoc();
    
    if ($res['status'] != 'active') {
        die("<script>alert('การประมูลนี้ปิดไปแล้ว'); window.location.href='../auction_room.php?id=$card_id';</script>");
    }

    // คำนวณราคาขั้นต่ำที่ต้องบิด โดยดึง min_increment มาบวกกับราคาปัจจุบัน
    $required_min_bid = $res['current_price'] + $res['min_increment'];

    if ($bid_amount < $required_min_bid) {
        die("<script>alert('จำนวนเงินต้องมากกว่าหรือเท่ากับราคาขั้นต่ำ (฿" . number_format($required_min_bid, 2) . ")'); window.history.back();</script>");
    }

    $sql_bid = "INSERT INTO bids (auction_id, user_id, bid_amount) VALUES (?, ?, ?)";
    $stmt_bid = $conn->prepare($sql_bid);
    $stmt_bid->bind_param("iid", $auction_id, $user_id, $bid_amount);
    
    if ($stmt_bid->execute()) {
        echo "<script>alert('เสนอราคาสำเร็จ!'); window.location.href='../auction_room.php?id=$card_id';</script>";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt_check->close();
    $stmt_bid->close();
    $conn->close();
} else {
    header("Location: ../index.php");
    exit();
}
?>