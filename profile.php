<?php
session_start();
require_once 'includes/db_connect.php';

// ตรวจสอบว่าล็อกอินหรือยัง ถ้ายังให้เด้งไปหน้า login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้ ยอดเงิน และคะแนนความน่าเชื่อถือ
$sql = "SELECT username, wallet_balance, trust_score FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('ไม่พบข้อมูลผู้ใช้'); window.location.href='index.php';</script>";
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

$trust_score = $user['trust_score'];

// 1. ดึงประวัติการซื้อ (ดึงจาก bids ว่าเราคือคนชนะ ในรายการที่ completed แล้ว)
$sql_buy = "SELECT c.title, c.image_url, a.end_time,
            (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id) as bought_price
            FROM auctions a
            JOIN cards c ON a.card_id = c.id
            JOIN bids b ON a.id = b.auction_id
            WHERE a.status = 'completed' AND b.user_id = ?
            GROUP BY a.id, c.title, c.image_url, a.end_time
            HAVING MAX(b.bid_amount) = bought_price
            ORDER BY a.end_time DESC";
$stmt_buy = $conn->prepare($sql_buy);
$stmt_buy->bind_param("i", $user_id);
$stmt_buy->execute();
$result_buy = $stmt_buy->get_result();

// 2. ดึงประวัติการขาย (ดึงจาก seller_id ในตาราง auctions)
// ถ้ายังไม่มี seller_id ในฐานข้อมูลเก่า ให้ fall back ไปดู c.owner_id ควบคู่ไปด้วย
$sql_sell = "SELECT c.title, c.image_url, a.end_time, a.status,
             COALESCE((SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id), c.starting_price) as current_price
             FROM auctions a
             JOIN cards c ON a.card_id = c.id
             WHERE (a.seller_id = ? OR (a.seller_id IS NULL AND c.owner_id = ?))
             ORDER BY a.end_time DESC";
$stmt_sell = $conn->prepare($sql_sell);
$stmt_sell->bind_param("ii", $user_id, $user_id);
$stmt_sell->execute();
$result_sell = $stmt_sell->get_result();

// 2. ดึงประวัติการ์ดที่คุณกำลังลงประมูลอยู่ (ยังไม่ได้ขายไป)
$sql_sell = "SELECT c.title, c.image_url, a.end_time, a.status,
             COALESCE((SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id), c.starting_price) as current_price
             FROM cards c
             JOIN auctions a ON c.id = a.card_id
             WHERE c.owner_id = ? AND a.status != 'completed'
             ORDER BY a.end_time DESC";
$stmt_sell = $conn->prepare($sql_sell);
$stmt_sell->bind_param("i", $user_id);
$stmt_sell->execute();
$result_sell = $stmt_sell->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน | Rare Card Auction</title>
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

        .btn-theme-primary {
            background-color: var(--color-accent);
            border-color: var(--color-accent);
            color: #fff;
        }
        .btn-theme-primary:hover {
            background-color: #e65f00;
            border-color: #e65f00;
            color: #fff;
        }

        /* วงกลมแสดงคะแนน Trust Score */
        .trust-score-circle {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 900;
            border: 6px solid;
            margin: 0 auto;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .score-high { border-color: #4ade80; color: #4ade80; } /* สีเขียว */
        .score-medium { border-color: #ffc107; color: #ffc107; } /* สีเหลือง */
        .score-low { border-color: #fb7185; color: #fb7185; } /* สีแดง */
        
        .history-img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .table-dark { background-color: transparent !important; }
        .nav-tabs .nav-link { color: var(--text-light); border-color: transparent; }
        .nav-tabs .nav-link.active { background-color: var(--color-nav-bg); color: var(--color-accent); border-color: rgba(255,255,255,0.1); border-bottom-color: transparent; }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php" style="color: var(--color-accent);">🃏 RareCard Auction</a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm me-3 fw-bold shadow-sm">🏠 กลับหน้าหลัก</a>
                <a href="actions/logout.php" class="btn btn-outline-danger btn-sm">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-md-10"> <!-- ขยายขนาดความกว้างขึ้นนิดหน่อยเพื่อให้ตารางดูไม่อึดอัด -->
                <div class="card theme-box shadow-lg border-0 mb-4">
                    <div class="card-header bg-dark border-bottom border-secondary py-3 text-center">
                        <h3 class="mb-0 fw-bold" style="color: var(--color-accent);">👤 โปรไฟล์ของฉัน</h3>
                    </div>
                    <div class="card-body p-5">
                        
                        <div class="text-center mb-5">
                            <h4 class="text-light">ยินดีต้อนรับ, <strong style="color: #0dcaf0;"><?php echo htmlspecialchars($user['username']); ?></strong></h4>
                        </div>

                        <div class="row text-center mb-4">
                            <!-- ฝั่งซ้าย: กระเป๋าเงิน -->
                            <div class="col-md-6 border-end border-secondary mb-4 mb-md-0">
                                <h5 class="text-muted mb-3">💰 ยอดเงินคงเหลือ (Wallet)</h5>
                                <h1 class="display-4 fw-bold mb-4" style="color: #4ade80;">
                                    ฿<?php echo number_format($user['wallet_balance'], 2); ?>
                                </h1>
                                <a href="topup.php" class="btn btn-outline-success fw-bold px-4 me-2">💳 เติมเงิน</a>
                                <a href="cart.php" class="btn btn-theme-primary fw-bold px-4">🛒 ตะกร้าของฉัน</a>
                            </div>
                            
                            <!-- ฝั่งขวา: คะแนนความน่าเชื่อถือ -->
                            <div class="col-md-6">
                                <h5 class="text-muted mb-3">🌟 คะแนนความน่าเชื่อถือ (Trust Score)</h5>
                                <?php 
                                    // คำนวณสีของวงกลมตามคะแนน
                                    $score_class = 'score-high';
                                    if($trust_score < 50) $score_class = 'score-low';
                                    elseif($trust_score < 80) $score_class = 'score-medium';
                                ?>
                                <div class="trust-score-circle <?php echo $score_class; ?> mb-3 shadow-lg bg-dark">
                                    <?php echo $trust_score; ?>
                                </div>
                                <p class="small text-light opacity-75">
                                    * หากคะแนนต่ำกว่า 50 จะถูกระงับการประมูล
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ส่วนแสดงประวัติการทำรายการ -->
                <div class="card theme-box shadow-lg border-0">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4" style="color: #0dcaf0;">📋 ประวัติการทำรายการ</h4>
                        
                        <ul class="nav nav-tabs mb-4 border-bottom border-secondary" id="historyTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold" id="buy-tab" data-bs-toggle="tab" data-bs-target="#buy" type="button" role="tab" aria-controls="buy" aria-selected="true">🛒 การ์ดที่ซื้อสำเร็จ</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" id="sell-tab" data-bs-toggle="tab" data-bs-target="#sell" type="button" role="tab" aria-controls="sell" aria-selected="false">📤 การ์ดที่ลงประมูล</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="historyTabsContent">
                            <!-- แท็บประวัติการซื้อ -->
                            <div class="tab-pane fade show active" id="buy" role="tabpanel" aria-labelledby="buy-tab">
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>รูปภาพ</th>
                                                <th>ชื่อการ์ด</th>
                                                <th>วันที่ซื้อสำเร็จ</th>
                                                <th class="text-end">ราคาที่ซื้อ (บาท)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result_buy->num_rows > 0): ?>
                                                <?php while($row = $result_buy->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><img src="uploads/<?php echo $row['image_url']; ?>" class="history-img border border-secondary"></td>
                                                        <td class="text-light fw-bold"><?php echo htmlspecialchars($row['title']); ?></td>
                                                        <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($row['end_time'])); ?></td>
                                                        <td class="text-end text-success fw-bold">฿<?php echo number_format($row['bought_price'], 2); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-center py-4 text-muted">ยังไม่มีประวัติการซื้อการ์ด</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- แท็บประวัติการ์ดที่ลงประมูล -->
                            <div class="tab-pane fade" id="sell" role="tabpanel" aria-labelledby="sell-tab">
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>รูปภาพ</th>
                                                <th>ชื่อการ์ด</th>
                                                <th>หมดเวลาประมูล</th>
                                                <th>สถานะ</th>
                                                <th class="text-end">ราคาปัจจุบัน (บาท)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result_sell->num_rows > 0): ?>
                                                <?php while($row = $result_sell->fetch_assoc()): ?>
                                                    <?php 
                                                        $status_badge = '<span class="badge bg-success">กำลังประมูล</span>';
                                                        if (strtotime($row['end_time']) < time()) {
                                                            if ($row['status'] == 'active') {
                                                                $status_badge = '<span class="badge bg-warning text-dark">รอผู้ชนะชำระเงิน</span>';
                                                            } else {
                                                                $status_badge = '<span class="badge bg-secondary">' . $row['status'] . '</span>';
                                                            }
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td><img src="uploads/<?php echo $row['image_url']; ?>" class="history-img border border-secondary"></td>
                                                        <td class="text-light fw-bold"><?php echo htmlspecialchars($row['title']); ?></td>
                                                        <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($row['end_time'])); ?></td>
                                                        <td><?php echo $status_badge; ?></td>
                                                        <td class="text-end text-info fw-bold">฿<?php echo number_format($row['current_price'], 2); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted">ยังไม่มีประวัติการลงประมูลการ์ด</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>