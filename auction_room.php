<?php
session_start();
require_once 'includes/db_connect.php';

// ตรวจสอบว่ามีการส่ง id การ์ดมาหรือไม่
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$card_id = (int)$_GET['id'];

// 1. ดึงข้อมูลการ์ดและการประมูล
$sql = "SELECT c.*, a.id AS auction_id, a.end_time, a.status, u.username AS owner_name,
        COALESCE((SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id), c.starting_price) AS current_price,
        (SELECT u2.username FROM bids b2 JOIN users u2 ON b2.user_id = u2.id WHERE b2.auction_id = a.id ORDER BY b2.bid_amount DESC LIMIT 1) AS highest_bidder
        FROM cards c
        JOIN auctions a ON c.id = a.card_id
        JOIN users u ON c.owner_id = u.id
        WHERE c.id = ?";
$stmt =$conn->prepare($sql);$stmt->bind_param("i", $card_id);$stmt->execute();
$result =$stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('ไม่พบข้อมูลการประมูล!'); window.location.href='index.php';</script>";
    exit();
}
$card = $result->fetch_assoc();$stmt->close();

// 2. ดึงรูปภาพทั้งหมดของแกลลอรีจากการ์ดใบนี้
$img_sql = "SELECT image_url FROM card_images WHERE card_id = ?";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $card_id);
$img_stmt->execute();$img_res = $img_stmt->get_result();$images = [];
while($row =$img_res->fetch_assoc()){
    $images[] =$row['image_url'];
}
$img_stmt->close();

// ถ้าการ์ดใบนี้ไม่มีแกลลอรี (เช่น การ์ดเก่า) ให้ใช้รูปหน้าปกเป็นค่าเริ่มต้น
if(empty($images)){
    $images[] =$card['image_url'];
}

// คำนวณราคาขั้นต่ำที่สามารถบิดได้
$min_bid_allowed = $card['current_price'] +$card['min_increment'];
$is_ended = (strtotime($card['end_time']) <= time());
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($card['title']); ?> | Rare Card Auction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-accent: #FF6A00;       
            --color-card-bg: #284561;      
            --color-main-bg: #0F1D2C;      
            --color-nav-bg: #040608;       
            --text-light: #F8F9FA;
            --text-muted-dark: #b0b8c1;
        }

        body {
            background-color: var(--color-main-bg) !important;
            color: var(--text-light) !important;
        }

        .custom-navbar {
            background-color: var(--color-nav-bg) !important;
            border-bottom: 3px solid var(--color-accent);
        }

        .theme-box {
            background-color: var(--color-card-bg) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: var(--text-light) !important;
        }

        .custom-input {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: var(--text-light) !important;
        }
        .custom-input:focus {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: #198754 !important;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important;
        }

        /* ปรับแต่งสไลด์โชว์ (Carousel) */
        .carousel-inner img {
            height: 500px;
            object-fit: contain;
            background-color: var(--color-nav-bg); /* พื้นหลังของรูปภาพจะเป็นสีดำเพื่อดึงรูปให้เด่น */
        }
        .carousel-control-prev-icon, .carousel-control-next-icon {
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            padding: 1.5rem;
            border: 2px solid var(--color-accent);
        }
        .carousel-indicators [data-bs-target] {
            background-color: var(--color-accent);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php" style="color: var(--color-accent);">🃏 RareCard Auction</a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm me-3 fw-bold">กลับหน้าหลัก</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-light me-3">ยินดีต้อนรับ, <strong><?php echo $_SESSION['username']; ?></strong></span>
                    <a href="actions/logout.php" class="btn btn-outline-danger btn-sm">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row">
            
            <!-- ฝั่งซ้าย: ระบบสไลด์โชว์รูปภาพ (Carousel) -->
            <div class="col-md-6 mb-4">
                <div id="cardImageCarousel" class="carousel slide shadow-lg rounded border border-secondary" data-bs-ride="carousel">
                    <!-- ปุ่มจุดด้านล่าง (Indicators) -->
                    <div class="carousel-indicators">
                        <?php foreach($images as $index =>$img): ?>
                            <button type="button" data-bs-target="#cardImageCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- รูปภาพ -->
                    <div class="carousel-inner rounded">
                        <?php foreach($images as $index =>$img): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="uploads/<?php echo htmlspecialchars($img); ?>" class="d-block w-100" alt="Card Image">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- ปุ่มเลื่อนซ้าย/ขวา (แสดงเมื่อมีมากกว่า 1 รูป) -->
                    <?php if(count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#cardImageCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#cardImageCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ฝั่งขวา: ข้อมูลการประมูลและการเสนอราคา -->
            <div class="col-md-6">
                <div class="theme-box p-4 rounded shadow-lg h-100">
                    <h2 class="fw-bold" style="color: #0dcaf0;"><?php echo htmlspecialchars($card['title']); ?></h2>
                    <p class="fs-5 text-light border-bottom border-secondary pb-3">
                        <span class="badge" style="background-color: var(--color-accent);"><?php echo htmlspecialchars($card['category']); ?></span> 
                        | <?php echo nl2br(htmlspecialchars($card['description'])); ?>
                    </p>

                    <!-- สถานะราคา -->
                    <h3 class="fw-bold mt-4" style="color: #fb7185;">
                        <span id="priceLabel"><?php echo $is_ended ? 'ราคาปิดประมูล:' : 'ราคาปัจจุบัน:'; ?></span> 
                        <span id="currentPriceDisplay">฿<?php echo number_format($card['current_price'], 2); ?></span>
                    </h3>
                    
                    <p class="fs-6 text-light mb-4">
                        👑 ผู้นำประมูล: <strong style="color: #ffc107;" id="highestBidderDisplay"><?php echo $card['highest_bidder'] ? htmlspecialchars($card['highest_bidder']) : 'ยังไม่มีผู้ประมูล'; ?></strong>
                    </p>

                    <!-- กล่องนับเวลาถอยหลัง -->
                    <div class="bg-dark text-center p-3 rounded mb-4 border border-secondary">
                        <h4 class="fw-bold mb-0" style="color: #ffc107;" id="countdownTimer" data-endtime="<?php echo str_replace('-', '/', $card['end_time']); ?>">
                            <?php echo $is_ended ? 'หมดเวลาประมูลแล้ว!' : 'กำลังคำนวณเวลา...'; ?>
                        </h4>
                    </div>

                    <!-- ฟอร์มเสนอราคา (ซ่อนถ้าหมดเวลา) -->
                    <?php if(!$is_ended): ?>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <form action="actions/place_bid.php" method="POST" class="mt-4">
                                <input type="hidden" name="auction_id" value="<?php echo $card['auction_id']; ?>">
                                <div class="input-group input-group-lg mb-2">
                                    <span class="input-group-text bg-dark text-success border-success">฿</span>
                                    <input type="number" name="bid_amount" class="form-control custom-input fs-5" 
                                           min="<?php echo $min_bid_allowed; ?>" 
                                           placeholder="ขั้นต่ำ ฿<?php echo number_format($min_bid_allowed, 2); ?>" required>
                                    <button type="submit" class="btn btn-success fw-bold px-4">บิดราคา (Bid)</button>
                                </div>
                                <div class="form-text text-light opacity-75">
                                    * บิดขั้นต่ำครั้งละ ฿<?php echo number_format($card['min_increment'], 2); ?> และไม่สามารถยกเลิกได้
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning text-center mt-4">
                                <strong>กรุณา <a href="login.php" class="text-danger">เข้าสู่ระบบ</a> เพื่อเข้าร่วมการประมูล</strong>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center mt-4 border-secondary text-light bg-dark">
                            <strong>การประมูลรายการนี้สิ้นสุดลงแล้ว</strong>
                        </div>
                    <?php endif; ?>

                    <div class="mt-5 text-end">
                        <small class="text-muted">👤 เจ้าของโพสต์: <?php echo htmlspecialchars($card['owner_name']); ?></small>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- สคริปต์เวลานับถอยหลัง -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const timerElement = document.getElementById('countdownTimer');
            if (timerElement && timerElement.innerText !== 'หมดเวลาประมูลแล้ว!') {
                setInterval(function() {
                    const now = new Date().getTime();
                    const endTimeStr = timerElement.getAttribute('data-endtime');
                    const endTime = new Date(endTimeStr).getTime();
                    const distance = endTime - now;

                    if (distance < 0) {
                        timerElement.innerHTML = "หมดเวลาประมูลแล้ว!";
                        timerElement.style.color = '#dc3545'; // เปลี่ยนเป็นสีแดงเมื่อหมดเวลา
                        setTimeout(() => location.reload(), 2000); // รีเฟรชหน้าเพื่อให้ซ่อนช่องบิด
                    } else {
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        let timeText = `เหลือเวลา: `;
                        if (days > 0) timeText += `${days} วัน `;
                        timeText += `${hours} ชม. ${minutes} นาที ${seconds} วินาที`;
                        timerElement.innerHTML = timeText;
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>