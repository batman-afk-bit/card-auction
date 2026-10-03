<?php
session_start();
require_once 'includes/db_connect.php'; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rare Card Auction | เว็บประมูลการ์ดหายาก</title>
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

        .card-img-top { height: 250px; object-fit: cover; }
        .price-update-flash { color: #ffc107 !important; transition: 0.3s; } 
        
        .shop-title {
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, var(--color-accent), #ff9933);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            letter-spacing: 1px;
        }
        .shop-subtitle {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--text-muted-dark) !important;
            letter-spacing: 0.5px;
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

        .custom-input {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: var(--text-light) !important;
        }
        .custom-input::placeholder { color: #9ca3af !important; }
        .custom-input:focus {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: var(--color-accent) !important;
            box-shadow: 0 0 0 0.25rem rgba(255, 106, 0, 0.25) !important;
        }

        select.custom-input option {
            background-color: var(--color-card-bg);
            color: var(--text-light);
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

        .theme-text-muted { color: var(--text-muted-dark) !important; }
        .theme-badge { background-color: var(--color-accent) !important; color: #fff !important; }
        .table-dark { background-color: transparent !important; }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php" style="color: var(--color-accent);">🃏 RareCard Auction</a>
            <div class="d-flex align-items-center">
                <a href="history.php" class="btn btn-outline-info btn-sm me-3 fw-bold shadow-sm">📜 ประวัติการประมูล</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="add_card.php" class="btn btn-outline-light btn-sm me-3 fw-bold shadow-sm">+ ลงประมูลการ์ด</a>
                    
                    <!-- อัปเดต: เปลี่ยนเป็นปุ่มลิงก์ไปหน้าโปรไฟล์ -->
                    <a href="profile.php" class="text-decoration-none text-light me-3 px-3 py-1 rounded bg-dark border border-secondary" style="transition: 0.3s;">
                        👤 <strong><?php echo $_SESSION['username']; ?></strong>
                    </a>
                    
                    <a href="actions/logout.php" class="btn btn-outline-danger btn-sm">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm me-2">เข้าสู่ระบบ</a>
                    <a href="register.php" class="btn btn-theme-primary btn-sm">สมัครสมาชิก</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container text-center mt-5 mb-4">
        <h1 class="shop-title">💎 RareCard Auction 💎</h1>
        <p class="shop-subtitle">อาณาจักรประมูลการ์ดเกมระดับแรร์สำหรับนักสะสม</p>
    </div>

    <div class="container mb-4">
        <?php 
            $search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
            $filter_category = isset($_GET['category']) ? $_GET['category'] : '';
            $min_price = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? $_GET['min_price'] : '';
            $max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? $_GET['max_price'] : '';
        ?>
        <form action="index.php" method="GET" class="p-3 theme-box shadow-sm rounded">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" class="form-control custom-input" name="search" placeholder="🔍 พิมพ์ชื่อการ์ด..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select custom-input" name="category">
                        <option value="">-- ทุกประเภทการ์ด --</option>
                        <option value="Yu-Gi-Oh!" <?php if($filter_category == 'Yu-Gi-Oh!') echo 'selected'; ?>>Yu-Gi-Oh!</option>
                        <option value="Pokémon" <?php if($filter_category == 'Pokémon') echo 'selected'; ?>>Pokémon</option>
                        <option value="One Piece" <?php if($filter_category == 'One Piece') echo 'selected'; ?>>One Piece</option>
                        <option value="Magic The Gathering" <?php if($filter_category == 'Magic The Gathering') echo 'selected'; ?>>Magic The Gathering</option>
                        <option value="อื่นๆ" <?php if($filter_category == 'อื่นๆ') echo 'selected'; ?>>อื่นๆ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control custom-input" name="min_price" placeholder="ราคาต่ำสุด" min="0" value="<?php echo htmlspecialchars($min_price); ?>">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control custom-input" name="max_price" placeholder="ราคาสูงสุด" min="0" value="<?php echo htmlspecialchars($max_price); ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-theme-primary fw-bold" type="submit">ค้นหา & กรอง</button>
                </div>
            </div>
            
            <?php if(!empty($search_keyword) OR !empty($filter_category) OR $min_price !== '' OR $max_price !== ''): ?>
                <div class="text-end mt-2">
                    <a href="index.php" class="btn btn-sm btn-outline-light fw-bold">ล้างค่าตัวกรองทั้งหมด</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

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
                    WHERE a.status = 'active' AND a.end_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY CASE WHEN a.end_time < NOW() THEN 1 ELSE 0 END ASC, a.end_time ASC";
            
            $types = "";
            $params = [];
            
            // อัปเดต: แก้ไข \% เป็น % และเว้นวรรคตัวแปรให้ถูกต้อง
            if (!empty($search_keyword)) { 
                $sql = str_replace("ORDER BY", "AND c.title LIKE ? ORDER BY", $sql);
                $types .= "s"; 
                $params[] = "%" . $search_keyword . "%"; 
            }
            if (!empty($filter_category)) { 
                $sql = str_replace("ORDER BY", "AND c.category = ? ORDER BY", $sql);
                $types .= "s"; 
                $params[] = $filter_category; 
            }
            if ($min_price !== '') { 
                $sql = str_replace("ORDER BY", "AND c.starting_price >= ? ORDER BY", $sql);
                $types .= "d"; 
                $params[] = $min_price; 
            }
            if ($max_price !== '') { 
                $sql = str_replace("ORDER BY", "AND c.starting_price <= ? ORDER BY", $sql); 
                $types .= "d"; 
                $params[] = $max_price; 
            }
            
            $stmt = $conn->prepare($sql);
            if (!empty($types)) { 
                $stmt->bind_param($types, ...$params); 
            }
            
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $end_time_formatted = date('d/m/Y H:i', strtotime($row['end_time']));
                    $is_ended = (strtotime($row['end_time']) <= time());
                    
                    $price_label = $is_ended ? 'ราคาปิดประมูล:' : 'ราคาปัจจุบัน:';
                    $bidder_label = $is_ended ? '🏆 ผู้ชนะการประมูล:' : '👑 ผู้นำประมูล:';
                    $btn_class = $is_ended ? 'btn-secondary text-light border-secondary' : 'btn-theme-primary';
                    $btn_text = $is_ended ? 'ดูสรุปผลประมูล' : 'เข้าร่วมประมูล';
            ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 theme-box shadow-sm border-0">
                            <img src="uploads/<?php echo $row['image_url']; ?>" class="card-img-top <?php echo $is_ended ? 'opacity-75' : ''; ?>" alt="Card Image">
                            <div class="card-body">
                                <span class="badge theme-badge mb-2"><?php echo htmlspecialchars($row['category']); ?></span>
                                <h5 class="card-title fw-bold text-truncate"><?php echo $row['title']; ?></h5>
                                
                                <p class="card-text text-light small border-bottom border-secondary pb-2 mb-2">
                                    👤 ลงประมูลโดย: <strong><?php echo htmlspecialchars($row['owner_name']); ?></strong>
                                </p>
                                
                                <p class="card-text theme-text-muted mb-0" style="font-size: 0.9rem;">
                                    ราคาเริ่มต้น: ฿<span class="text-decoration-line-through"><?php echo number_format($row['starting_price'], 2); ?></span>
                                </p>
                                
                                <p class="card-text text-success fw-bold fs-5 mb-0" style="color: #4ade80 !important;">
                                    <span id="priceLabel_<?php echo $row['auction_id']; ?>"><?php echo $price_label; ?></span> 
                                    <span id="currentPrice_<?php echo $row['auction_id']; ?>">฿<?php echo number_format($row['current_price'], 2); ?></span>
                                </p>
                                
                                <p class="card-text small fw-bold mb-2" style="color: var(--color-accent);">
                                    <span id="bidderLabel_<?php echo $row['auction_id']; ?>"><?php echo $bidder_label; ?></span> 
                                    <span id="highestBidder_<?php echo $row['auction_id']; ?>"><?php echo $row['highest_bidder_name'] ? htmlspecialchars($row['highest_bidder_name']) : 'ไม่มีผู้ประมูล'; ?></span>
                                </p>
                                
                                <p class="card-text text-danger fw-bold small mb-2" style="color: <?php echo $is_ended ? 'var(--text-muted-dark)' : '#fb7185'; ?> !important;">
                                    ⏳ <span id="countdown_<?php echo $row['auction_id']; ?>" data-endtime="<?php echo str_replace('-', '/', $row['end_time']); ?>">
                                        <?php echo $is_ended ? 'หมดเวลาประมูล!' : 'กำลังคำนวณเวลา...'; ?>
                                    </span>
                                </p>

                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary">
                                    <small class="text-light">👥 จำนวนการบิด: <span id="totalBids_<?php echo $row['auction_id']; ?>"><?php echo $row['total_bids']; ?></span> ครั้ง</small>
                                    <button class="btn btn-sm btn-outline-info text-light border-light" onclick="viewHistory(<?php echo $row['auction_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['title'])); ?>')">📜 ดูประวัติ</button>
                                </div>
                            </div>
                            <div class="card-footer theme-card-footer border-0 pb-3">
                                <a href="auction_room.php?id=<?php echo $row['id']; ?>" id="actionBtn_<?php echo $row['auction_id']; ?>" class="btn <?php echo $btn_class; ?> w-100 fw-bold mb-2">
                                    <?php echo $btn_text; ?>
                                </a>
                                
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] ==$row['owner_id']): ?>
                                    <div class="d-flex justify-content-between">
                                        <a href="edit_card.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-light w-50 me-1">แก้ไข</a>
                                        <a href="actions/delete_card.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger w-50 ms-1" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบการ์ดใบนี้?');">ลบทิ้ง</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="col-12 text-center theme-text-muted mt-5"><h5>ไม่พบการ์ดประมูล</h5></div>';
            }
            $stmt->close();
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
            document.getElementById('historyModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
            var historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
            historyModal.show();

            fetch('actions/get_bid_history.php?auction_id=' + auctionId)
                .then(response => response.text())
                .then(html => { document.getElementById('historyModalBody').innerHTML = html; });
        }

        document.addEventListener("DOMContentLoaded", function() {
            const priceElements = document.querySelectorAll('[id^="currentPrice_"]');
            const auctionIds = Array.from(priceElements).map(el => el.id.split('_')[1]);

            if (auctionIds.length > 0) {
                setInterval(function() {
                    auctionIds.forEach(auctionId => {
                        fetch('actions/get_current_price.php?auction_id=' + auctionId)
                            .then(response => response.json())
                            .then(data => {
                                if (data.current_price) {
                                    let currentPriceNum = parseFloat(data.current_price);
                                    let formattedPrice = '฿' + currentPriceNum.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    let displayElement = document.getElementById('currentPrice_' + auctionId);
                                    let bidderElement = document.getElementById('highestBidder_' + auctionId);
                                    let bidsElement = document.getElementById('totalBids_' + auctionId);
                                    
                                    if(displayElement.innerText !== formattedPrice) {
                                        displayElement.innerHTML = formattedPrice;
                                        if (bidderElement && data.highest_bidder) bidderElement.innerHTML = data.highest_bidder;
                                    }
                                    if (bidsElement && data.total_bids !== undefined) {
                                        bidsElement.innerText = data.total_bids;
                                    }
                                }
                            });
                    });
                }, 3000);
            }

            const countdownElements = document.querySelectorAll('[id^="countdown_"]');
            if (countdownElements.length > 0) {
                setInterval(function() {
                    const now = new Date().getTime();
                    
                    countdownElements.forEach(el => {
                        const endTimeStr = el.getAttribute('data-endtime');
                        const endTime = new Date(endTimeStr).getTime();
                        const distance = endTime - now;
                        
                        const auctionId = el.id.split('_')[1];
                        const btnEl = document.getElementById('actionBtn_' + auctionId);
                        const priceLabel = document.getElementById('priceLabel_' + auctionId);
                        const bidderLabel = document.getElementById('bidderLabel_' + auctionId);

                        if (distance < 0) {
                            if(el.innerText !== "หมดเวลาประมูล!") {
                                el.innerHTML = "หมดเวลาประมูล!";
                                el.style.color = 'var(--text-muted-dark)';
                                
                                if(btnEl) {
                                    btnEl.innerText = "ดูสรุปผลประมูล";
                                    btnEl.className = "btn btn-secondary text-light border-secondary w-100 fw-bold mb-2";
                                }
                                if(priceLabel) priceLabel.innerText = "ราคาปิดประมูล:";
                                if(bidderLabel) bidderLabel.innerText = "🏆 ผู้ชนะการประมูล:";
                            }
                        } else {
                            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                            
                            let timeText = `เหลือเวลา: `;
                            if (days > 0) timeText += `${days} วัน `;
                            timeText += `${hours} ชม. ${minutes} นาที ${seconds} วิ`;
                            el.innerHTML = timeText;
                        }
                    });
                }, 1000);
            }
        });
    </script>
</body>
</html>