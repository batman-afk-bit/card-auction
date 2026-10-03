<?php
session_start();
require_once 'includes/db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงยอดเงินปัจจุบันของผู้ใช้ (เพื่อมาโชว์เทียบกับราคาสินค้า)
$user_sql = "SELECT wallet_balance FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();
$current_balance = $user_data['wallet_balance'];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าของฉัน | Rare Card Auction</title>
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
        
        .table-dark { background-color: transparent !important; }
        .cart-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php" style="color: var(--color-accent);">🃏 RareCard Auction</a>
            <div class="d-flex align-items-center">
                <a href="profile.php" class="btn btn-outline-info btn-sm me-3 fw-bold">👤 กลับหน้าโปรไฟล์</a>
                <a href="index.php" class="btn btn-outline-light btn-sm fw-bold">🏠 หน้าหลัก</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row">
            <div class="col-md-12 mb-4 text-center">
                <h2 class="fw-bold" style="color: var(--color-accent);">🛒 ตะกร้าสินค้าของคุณ</h2>
                <p class="text-light">รายการการ์ดที่คุณชนะการประมูลและรอการชำระเงิน</p>
            </div>

            <div class="col-md-8">
                <div class="theme-box p-4 rounded shadow-lg">
                    <h5 class="border-bottom border-secondary pb-3 mb-4">รายการสินค้า</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle text-center">
                            <thead class="text-muted">
                                <tr>
                                    <th>รูปภาพ</th>
                                    <th class="text-start">ชื่อการ์ด</th>
                                    <th>ผู้ขาย (ชำระให้)</th> <!-- อัปเดตเพิ่มคอลัมน์ผู้ขาย -->
                                    <th>ราคาที่ชนะ (บาท)</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // อัปเดต SQL: JOIN กับตาราง users เพื่อดึงชื่อผู้ขายมาด้วย
                                $sql = "SELECT a.id AS auction_id, c.title, c.image_url, a.end_time,
                                        u_owner.username AS owner_name, 
                                        (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id) AS winning_price,
                                        (SELECT user_id FROM bids WHERE auction_id = a.id ORDER BY bid_amount DESC LIMIT 1) AS winner_id
                                        FROM auctions a
                                        JOIN cards c ON a.card_id = c.id
                                        JOIN users u_owner ON c.owner_id = u_owner.id
                                        WHERE a.end_time < NOW() AND a.status = 'active'
                                        HAVING winner_id = ?";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                $total_price = 0;

                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $total_price += $row['winning_price'];
                                ?>
                                        <tr>
                                            <td><img src="uploads/<?php echo $row['image_url']; ?>" class="cart-img border border-secondary"></td>
                                            <td class="text-start fw-bold text-info">
                                                <?php echo htmlspecialchars($row['title']); ?>
                                                <!-- อัปเดต: เพิ่มชื่อผู้ขายแสดงตัวเล็กๆ ใต้ชื่อการ์ด กรณีไม่อยากให้ตารางแคบไป แต่ผมแยกคอลัมน์ให้ตามโครงสร้างตาราง HTML นะครับ -->
                                            </td>
                                            <!-- อัปเดต: คอลัมน์แสดงชื่อผู้ขาย (เงินจะเข้าคนนี้) -->
                                            <td><span class="badge bg-secondary">👤 <?php echo htmlspecialchars($row['owner_name']); ?></span></td>
                                            
                                            <td class="text-success fw-bold">฿<?php echo number_format($row['winning_price'], 2); ?></td>
                                            <td>
                                                <form action="actions/checkout_action.php" method="POST">
                                                    <input type="hidden" name="auction_id" value="<?php echo $row['auction_id']; ?>">
                                                    <input type="hidden" name="amount" value="<?php echo $row['winning_price']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success fw-bold" onclick="return confirm('ยืนยันชำระเงิน ฿<?php echo number_format($row['winning_price'], 2); ?> ให้กับ <?php echo htmlspecialchars($row['owner_name']); ?> ?');">ชำระเงิน</button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="py-4 text-muted">ยังไม่มีรายการที่คุณชนะการประมูลและรอชำระเงิน</td></tr>';
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- สรุปยอดรวม -->
            <div class="col-md-4 mt-4 mt-md-0">
                <div class="theme-box p-4 rounded shadow-lg">
                    <h5 class="border-bottom border-secondary pb-3 mb-4">สรุปยอดที่ต้องชำระทั้งหมด</h5>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-light">ยอดเงินในกระเป๋าของคุณ:</span>
                        <strong style="color: #0dcaf0;">฿<?php echo number_format($current_balance, 2); ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4 fs-5">
                        <span class="text-light fw-bold">ยอดรวมที่ต้องจ่าย:</span>
                        <strong style="color: #fb7185;">฿<?php echo number_format($total_price, 2); ?></strong>
                    </div>

                    <?php if ($total_price > 0 && $current_balance < $total_price): ?>
                        <div class="alert alert-danger text-center small fw-bold mb-3 border-danger bg-dark text-danger">
                            ยอดเงินของคุณไม่เพียงพอ! กรุณาเติมเงินก่อนชำระ
                        </div>
                        <a href="topup.php" class="btn btn-warning w-100 fw-bold shadow-sm">💳 เติมเงินเข้า Wallet</a>
                    <?php elseif ($total_price > 0): ?>
                        <div class="alert alert-success text-center small fw-bold mb-3 border-success bg-dark text-success">
                            ยอดเงินเพียงพอ สามารถกดชำระเงินทีละรายการได้เลย
                        </div>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 fw-bold shadow-sm" disabled>ไม่มีรายการต้องชำระ</button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>