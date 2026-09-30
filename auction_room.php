<?php
session_start();
require_once 'includes/db_connect.php';

// ตรวจสอบว่ามีการส่ง id มาหรือไม่
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$card_id = $_GET['id'];

// คำสั่ง SQL ดึงข้อมูลการ์ด, เวลาประมูล และหาราคาที่ถูกบิดสูงสุด (ถ้ายังไม่มีใครบิด ให้ใช้ราคาเริ่มต้น)
$sql = "SELECT c.*, a.id as auction_id, a.end_time, a.status, 
        COALESCE(MAX(b.bid_amount), c.starting_price) as current_price 
        FROM cards c 
        JOIN auctions a ON c.id = a.card_id 
        LEFT JOIN bids b ON a.id = b.auction_id 
        WHERE c.id = ? GROUP BY c.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $card_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('ไม่พบข้อมูลการ์ด'); window.location.href='index.php';</script>";
    exit();
}

$card = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $card['title']; ?> | Rare Card Auction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .auction-image { max-height: 500px; object-fit: contain; }
        .countdown-box { background: #343a40; color: #ffc107; padding: 15px; border-radius: 8px; font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body class="bg-light">
    
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🃏 กลับหน้าหลัก</a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row bg-white p-4 shadow-sm rounded">
            <!-- ส่วนรูปภาพการ์ด -->
            <div class="col-md-6 text-center mb-4">
                <img src="uploads/<?php echo $card['image_url']; ?>" class="img-fluid rounded auction-image" alt="Card">
            </div>
            
            <!-- ส่วนรายละเอียดและการบิดราคา -->
            <div class="col-md-6">
                <h2 class="fw-bold text-primary"><?php echo $card['title']; ?></h2>
                <p class="text-muted"><?php echo nl2br($card['description']); ?></p>
                
                <hr>
                
                <h4 class="text-danger fw-bold" id="currentPriceDisplay">ราคาปัจจุบัน: ฿<?php echo number_format($card['current_price'], 2); ?></h4>
                
                <!-- กล่องนับเวลาถอยหลัง -->
                <div class="countdown-box text-center my-4" id="countdownTimer">
                    กำลังคำนวณเวลา...
                </div>

                <!-- ฟอร์มเสนอราคา -->
                <?php if ($card['status'] == 'active'): ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="actions/place_bid.php" method="POST" class="mt-4">
                            <input type="hidden" name="auction_id" value="<?php echo $card['auction_id']; ?>">
                            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                            
                            <?php 
                            // อัปเดต: ดึงค่าขั้นต่ำจากการ์ดใบนี้โดยตรง
                            $min_increment = isset($card['min_increment']) ? $card['min_increment'] : 100; 
                            $next_min_bid = $card['current_price'] + $min_increment;
                            ?>
                            
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-success text-white">฿</span>
                                <input type="number" class="form-control form-control-lg" name="bid_amount" id="bidAmountInput"
                                       min="<?php echo $next_min_bid; ?>" 
                                       step="<?php echo $min_increment; ?>"
                                       placeholder="ขั้นต่ำ ฿<?php echo number_format($next_min_bid, 2); ?>" required>
                                <button class="btn btn-success fw-bold px-4" type="submit">บิดราคา (Bid)</button>
                            </div>
                            <small class="text-muted">* บิดขั้นต่ำครั้งละ ฿<?php echo number_format($min_increment, 2); ?> และไม่สามารถยกเลิกได้</small>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4 text-center">
                            กรุณา <a href="login.php" class="alert-link">เข้าสู่ระบบ</a> เพื่อเข้าร่วมการประมูล
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger mt-4 text-center fs-5 fw-bold">
                        การประมูลสิ้นสุดลงแล้ว
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript สำหรับนับเวลาถอยหลัง & อัปเดตราคา Real-time -->
    <script>
        // --- 1. ระบบนับเวลาถอยหลัง ---
        var endTime = new Date("<?php echo str_replace('-', '/', $card['end_time']); ?>").getTime();

        var countdownFunction = setInterval(function() {
            var now = new Date().getTime();
            var distance = endTime - now;

            if (distance < 0) {
                clearInterval(countdownFunction);
                document.getElementById("countdownTimer").innerHTML = "หมดเวลาประมูล!";
                document.getElementById("countdownTimer").classList.replace("text-warning", "text-danger");
            } else {
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById("countdownTimer").innerHTML = 
                    "เหลือเวลา: " + days + " วัน " + hours + " ชม. " + minutes + " นาที " + seconds + " วินาที";
            }
        }, 1000);

        // --- 2. ระบบ Real-time Polling อัปเดตราคาทุกๆ 3 วินาที ---
        setInterval(function() {
            var auctionId = <?php echo $card['auction_id']; ?>;
            var minIncrement = <?php echo isset($min_increment) ? $min_increment : 100; ?>;

            fetch('actions/get_current_price.php?auction_id=' + auctionId)
                .then(response => response.json())
                .then(data => {
                    if (data.current_price) {
                        let currentPriceNum = parseFloat(data.current_price);
                        
                        // ฟอร์แมตตัวเลขให้มีลูกน้ำและทศนิยม
                        let formattedPrice = currentPriceNum.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // เปลี่ยนตัวเลขราคาปัจจุบันบนหน้าจอ
                        document.getElementById('currentPriceDisplay').innerHTML = 'ราคาปัจจุบัน: ฿' + formattedPrice;

                        // เปลี่ยนข้อจำกัดขั้นต่ำในช่องกรอกตัวเลข
                        let bidInput = document.getElementById('bidAmountInput');
                        if (bidInput) {
                            let nextMinBid = currentPriceNum + minIncrement;
                            bidInput.min = nextMinBid;
                            bidInput.placeholder = 'ขั้นต่ำ ฿' + nextMinBid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                })
                .catch(error => console.error('Error fetching price:', error));
        }, 3000); 
    </script>
</body>
</html>