<?php
require_once '../includes/db_connect.php';

if (isset($_GET['auction_id'])) {
    $auction_id = (int)$_GET['auction_id'];

    // ดึงข้อมูลประวัติการบิด เรียงจากราคาสูงสุดไปต่ำสุด
    $sql = "SELECT u.username, b.bid_amount 
            FROM bids b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.auction_id = ? 
            ORDER BY b.bid_amount DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // สร้างตารางประวัติในรูปแบบ Dark Theme
        echo '<table class="table table-dark table-hover table-bordered text-center align-middle">';
        echo '<thead class="table-secondary text-dark"><tr><th>อันดับ</th><th>ผู้ประมูล</th><th>ราคาที่เสนอ (฿)</th></tr></thead>';
        echo '<tbody>';
        $rank = 1;
        
        while ($row = $result->fetch_assoc()) {
            // ไฮไลต์ผู้ที่บิดราคาสูงสุด (อันดับ 1)
            $highlight = ($rank === 1) ? 'text-warning fw-bold' : 'text-light';
            $crown = ($rank === 1) ? '👑 ' : '';
            
            echo "<tr>";
            echo "<td class='text-light'>{$rank}</td>";
            echo "<td class='{$highlight}'>{$crown}" . htmlspecialchars($row['username']) . "</td>";
            echo "<td class='{$highlight}'>" . number_format($row['bid_amount'], 2) . "</td>";
            echo "</tr>";
            $rank++;
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="text-center text-muted py-4">ยังไม่มีผู้เข้าร่วมประมูลในการ์ดใบนี้</div>';
    }

    $stmt->close();
    $conn->close();
}
?>