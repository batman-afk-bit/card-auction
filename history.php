<?php
session_start();
// บังคับเปิดแสดง Error เพื่อให้รู้สาเหตุแทนที่จะเป็นหน้าขาวโพลน
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php'; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการประมูล | Rare Card Auction</title>
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
            border-bottom: 3px solid #0dcaf0; 
        }

        .card-img-top { height: 250px; object-fit: cover; }
        
        .shop-title {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(45deg, #0dcaf0, #6f42c1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            letter-spacing: 1px;
        }

        .theme-box {
            background-color: var(--color-card-bg) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: var(--text-light) !important;
        }

        .theme-card-footer {
            background-color: rgba(0, 0, 0, 0.2) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
        }

        .theme-text-muted { color: var(--text-muted-dark) !important; }
        .theme-badge { background-color: #6c757d !important; color: #fff !important; }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php" style="color: var(--color-accent);">🃏 RareCard Auction</a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-info btn-sm me-3 fw-bold shadow-sm">🏠 กลับหน้าหลัก</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-light me-3">
                        ยินดีต้อนรับ, <strong><?php echo $_SESSION['username']; ?></strong>
                    </span>
                    <a href="actions/logout.php" class="btn btn-outline-danger btn-sm">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm me-2">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container text-center mt-5 mb-5">
        <h1 class="shop-title">🏆 Hall of Fame 🏆</h1>
        <p class="text-light fs-5">คลังประวัติการประมูลที่จบลงแล้วทั้งหมด</p>
    </div>

    <!-- ส่วนแสดงรายการการ์ดที่ประมูลจบแล้ว -->
    <div class="container mb-5">
        <div class="row">
            <?php
            $sql = "SELECT c.id, c.title, c.image_url, c.starting_price, c.owner_id, c.category, a.id AS auction_id, a.end_time,
                    u_owner.username AS owner_name,
                    COALESCE((SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id), c.starting_price) AS current_price,
                    (SELECT u.username FROM bids b2 JOIN users u ON b2.user_id = u.id WHERE b2.auction_id = a.id ORDER BY b2.bid_amount DESC LIMIT 1) AS highest_bidder_name,
                    (SELECT COUNT(id) FROM bids WHERE auction_id = a.id) AS total_bids
                    FROM cards c 
                    JOIN auctions a ON c.id = a.card_id 
                    JOIN users u_owner ON c.owner_id = u_owner.id
                    WHERE a.end_time < NOW()
                    ORDER BY a.end_time DESC"; 
            
            $result = $conn->query($sql);

            // ดักจับ Error หากรันคำสั่ง SQL ไม่ผ่าน
            if ($result) {
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $end_time_formatted = date('d/m/Y H:i', strtotime($row['end_time']));
            ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 theme-box shadow-sm border-0 opacity-75">
                                <img src="uploads/<?php echo $row['image_url']; ?>" class="card-img-top" alt="Card Image">
                                <div class="card-body">
                                    <span class="badge theme-badge mb-2"><?php echo htmlspecialchars($row['category']); ?></span>
                                    <h5 class="card-title fw-bold text-truncate text-light"><?php echo $row['title']; ?></h5>
                                    
                                    <p class="card-text text-light small border-bottom border-secondary pb-2 mb-2">
                                        👤 ลงประมูลโดย: <strong><?php echo htmlspecialchars($row['owner_name']); ?></strong>
                                    </p>
                                    
                                    <p class="card-text text-success fw-bold fs-5 mb-0" style="color: #4ade80 !important;">
                                        ราคาปิดประมูล: ฿<?php echo number_format($row['current_price'], 2); ?>
                                    </p>
                                    
                                    <p class="card-text small fw-bold mb-2" style="color: #ffc107;">
                                        🏆 ผู้ชนะ: <?php echo $row['highest_bidder_name'] ? htmlspecialchars($row['highest_bidder_name']) : 'ไม่มีผู้เสนอราคา'; ?>
                                    </p>
                                    
                                    <p class="card-text theme-text-muted small mb-0">
                                        🔒 ปิดเมื่อ: <?php echo $end_time_formatted; ?>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary">
                                        <small class="text-light">👥 บิดทั้งหมด: <?php echo $row['total_bids']; ?> ครั้ง</small>
                                        <button class="btn btn-sm btn-outline-info text-light border-light" onclick="viewHistory(<?php echo $row['auction_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['title'])); ?>')">📜 ดูประวัติ</button>
                                    </div>
                                </div>
                                <div class="card-footer theme-card-footer border-0 pb-3">
                                    <a href="auction_room.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary text-light border-secondary w-100 fw-bold">
                                        ดูสรุปผลประมูล
                                    </a>
                                </div>
                            </div>
                        </div>
            <?php
                    }
                } else {
                    echo '<div class="col-12 text-center theme-text-muted mt-5"><h5>ยังไม่มีประวัติการประมูลที่จบลง</h5></div>';
                }
            } else {
                // ถ้า Query ผิดพลาด ให้โชว์ Error ออกมา
                echo '<div class="col-12 text-center text-danger mt-5"><h5>พบปัญหา Database: ' . $conn->error . '</h5></div>';
            }
            ?>
        </div>
    </div>

    <!-- Modal ดูประวัติ -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content theme-box border-secondary" style="background-color: var(--color-main-bg) !important;">
          <div class="modal-header border-secondary">
            <h5 class="modal-title fw-bold text-light" id="historyModalLabel">ประวัติการประมูล</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="historyModalBody"></div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewHistory(auctionId, cardTitle) {
            document.getElementById('historyModalLabel').innerText = 'ประวัติ: ' + cardTitle;
            document.getElementById('historyModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-info" role="status"></div></div>';
            var historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
            historyModal.show();

            fetch('actions/get_bid_history.php?auction_id=' + auctionId)
                .then(response => response.text())
                .then(html => { document.getElementById('historyModalBody').innerHTML = html; })
                .catch(error => { document.getElementById('historyModalBody').innerHTML = '<p class="text-danger text-center mt-3">เกิดข้อผิดพลาดในการดึงข้อมูล</p>'; });
        }
    </script>
</body>
</html>