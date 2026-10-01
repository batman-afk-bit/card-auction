<?php
require_once '../includes/db_connect.php';

if (isset($_GET['auction_id'])) {
    $auction_id = $_GET['auction_id'];

    // อัปเดต: เพิ่ม (SELECT COUNT...) เพื่อนับจำนวนบิดทั้งหมดของสินค้านั้น
    $sql = "SELECT COALESCE(MAX(b.bid_amount), c.starting_price) AS current_price,
                   (SELECT u.username FROM bids b2 JOIN users u ON b2.user_id = u.id WHERE b2.auction_id = a.id ORDER BY b2.bid_amount DESC LIMIT 1) AS highest_bidder_name,
                   (SELECT COUNT(b3.id) FROM bids b3 WHERE b3.auction_id = a.id) AS total_bids
            FROM auctions a
            JOIN cards c ON a.card_id = c.id
            LEFT JOIN bids b ON a.id = b.auction_id
            WHERE a.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'current_price' => $row['current_price'],
            'highest_bidder' => $row['highest_bidder_name'] ? $row['highest_bidder_name'] : 'ยังไม่มีผู้ประมูล',
            'total_bids' => $row['total_bids'] // ส่งจำนวนการบิดกลับไปด้วย
        ]);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
    
    $stmt->close();
    $conn->close();
}
?>