<?php
require_once '../includes/db_connect.php';
header('Content-Type: application/json'); // บอกเบราว์เซอร์ว่าไฟล์นี้จะตอบกลับเป็น JSON

if (isset($_GET['auction_id'])) {
    $auction_id = $_GET['auction_id'];
    
    // ดึงราคาปัจจุบัน
    $sql = "SELECT COALESCE(MAX(b.bid_amount), c.starting_price) as current_price, a.status 
            FROM auctions a 
            JOIN cards c ON a.card_id = c.id 
            LEFT JOIN bids b ON a.id = b.auction_id 
            WHERE a.id = ? GROUP BY a.id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // แปลงข้อมูลเป็น JSON แล้วส่งกลับไป
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'ไม่พบข้อมูล']);
    }
    $stmt->close();
}
$conn->close();
?>