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
        .card-img-top { height: 250px; object-fit: cover; }
        /* เอฟเฟกต์ตอนที่ราคาถูกอัปเดตแบบ Real-time */
        .price-update-flash { color: #ffc107 !important; transition: 0.3s; } 
        
        /* สไตล์ใหม่สำหรับชื่อร้าน (Gradient Text) */
        .shop-title {
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #0d6efd, #0dcaf0, #6f42c1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            letter-spacing: 1px;
        }
        .shop-subtitle {
            font-size: 1.2rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="bg-light">
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🃏 RareCard Auction</a>
            <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="add_card.php" class="btn btn-warning btn-sm me-3 fw-bold shadow-sm">+ ลงประมูลการ์ด</a>
                    <span class="text-light me-3">
                        ยินดีต้อนรับ, <strong><?php echo $_SESSION['username']; ?></strong>
                    </span>
                    <a href="actions/logout.php" class="btn btn-outline-danger btn-sm">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm me-2">เข้าสู่ระบบ</a>
                    <a href="register.php" class="btn btn-primary btn-sm">สมัครสมาชิก</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- อัปเดต: เปลี่ยนหัวข้อเป็นป้ายชื่อร้านที่ใช้คลาส CSS ใหม่ -->
    <div class="container text-center mt-5 mb-4">
        <h1 class="shop-title">💎 RareCard Auction 💎</h1>
        <p class="text-muted shop-subtitle">อาณาจักรประมูลการ์ดเกมระดับแรร์สำหรับนักสะสม</p>
    </div>

    <!-- ส่วนตัวกรองข้อมูล (Filter Form) -->
    <div class="container mb-4">
        <?php 
            $search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
            $filter_category = isset($_GET['category']) ? $_GET['category'] : '';
            $min_price = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? $_GET['min_price'] : '';
            $max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? $_GET['max_price'] : '';
        ?>
        <form action="index.php" method="GET" class="p-3 bg-white shadow-sm rounded border">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="🔍 พิมพ์ชื่อการ์ด..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="category">
                        <option value="">-- ทุกประเภทการ์ด --</option>
                        <option value="Yu-Gi-Oh!" <?php if($filter_category == 'Yu-Gi-Oh!') echo 'selected'; ?>>Yu-Gi-Oh!</option>
                        <option value="Pokémon" <?php if($filter_category == 'Pokémon') echo 'selected'; ?>>Pokémon</option>
                        <option value="One Piece" <?php if($filter_category == 'One Piece') echo 'selected'; ?>>One Piece</option>
                        <option value="Magic The Gathering" <?php if($filter_category == 'Magic The Gathering') echo 'selected'; ?>>Magic The Gathering</option>
                        <option value="อื่นๆ" <?php if($filter_category == 'อื่นๆ') echo 'selected'; ?>>อื่นๆ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="min_price" placeholder="ราคาต่ำสุด" min="0" value="<?php echo htmlspecialchars($min_price); ?>">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="max_price" placeholder="ราคาสูงสุด" min="0" value="<?php echo htmlspecialchars($max_price); ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary fw-bold" type="submit">ค้นหา & กรอง</button>
                </div>
            </div>
            
            <?php if(!empty($search_keyword) || !empty($filter_category) || $min_price !== '' || $max_price !== ''): ?>
                <div class="text-end mt-2">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary fw-bold">ล้างค่าตัวกรองทั้งหมด</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- ส่วนแสดงรายการการ์ด -->
    <div class="container mb-5">
        <div class="row">
            <?php
            $sql = "SELECT c.id, c.title, c.image_url, c.starting_price, c.owner_id, c.category, a.id AS auction_id, a.end_time,
                    COALESCE(MAX(b.bid_amount), c.starting_price) AS current_price 
                    FROM cards c 
                    JOIN auctions a ON c.id = a.card_id 
                    LEFT JOIN bids b ON a.id = b.auction_id 
                    WHERE a.status = 'active'";
            
            $types = "";
            $params = [];

            if (!empty($search_keyword)) {
                $sql .= " AND c.title LIKE ?";
                $types .= "s";
                $params[] = "%" . $search_keyword . "%";
            }
            if (!empty($filter_category)) {
                $sql .= " AND c.category = ?";
                $types .= "s";
                $params[] = $filter_category;
            }
            if ($min_price !== '') {
                $sql .= " AND c.starting_price >= ?";
                $types .= "d";
                $params[] = $min_price;
            }
            if ($max_price !== '') {
                $sql .= " AND c.starting_price <= ?";
                $types .= "d";
                $params[] = $max_price;
            }

            $sql .= " GROUP BY c.id ORDER BY a.end_time ASC";

            $stmt = $conn->prepare($sql);

            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $end_time_formatted = date('d/m/Y H:i', strtotime($row['end_time']));
            ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <img src="uploads/<?php echo $row['image_url']; ?>" class="card-img-top" alt="Card Image">
                            <div class="card-body">
                                <span class="badge bg-info text-dark mb-2"><?php echo htmlspecialchars($row['category']); ?></span>
                                <h5 class="card-title fw-bold text-truncate"><?php echo $row['title']; ?></h5>
                                
                                <p class="card-text text-muted mb-0" style="font-size: 0.9rem;">
                                    ราคาเริ่มต้น: ฿<span class="text-decoration-line-through"><?php echo number_format($row['starting_price'], 2); ?></span>
                                </p>
                                <p class="card-text text-success fw-bold fs-5 mb-2">
                                    ราคาปัจจุบัน: <span id="currentPrice_<?php echo $row['auction_id']; ?>">฿<?php echo number_format($row['current_price'], 2); ?></span>
                                </p>
                                
                                <p class="card-text text-muted small">
                                    ปิดประมูล: <?php echo $end_time_formatted; ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-0 pb-3">
                                <a href="auction_room.php?id=<?php echo $row['id']; ?>" class="btn btn-primary w-100 fw-bold mb-2">เข้าร่วมประมูล</a>
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['owner_id']): ?>
                                    <div class="d-flex justify-content-between">
                                        <a href="edit_card.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning w-50 me-1">แก้ไข</a>
                                        <a href="actions/delete_card.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger w-50 ms-1" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบการ์ดใบนี้?');">ลบทิ้ง</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="col-12 text-center text-muted mt-5">';
                echo '<h5>ไม่พบการ์ดประมูลที่ตรงกับเงื่อนไขของคุณ</h5>';
                echo '<p>ลองปรับเปลี่ยนช่วงราคา, ประเภท หรือ <a href="index.php" class="text-decoration-none">ล้างค่าตัวกรอง</a> ดูอีกครั้ง</p>';
                echo '</div>';
            }
            $stmt->close();
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
                                    
                                    if(displayElement.innerText !== formattedPrice) {
                                        displayElement.innerHTML = formattedPrice;
                                        displayElement.classList.add('price-update-flash');
                                        setTimeout(() => displayElement.classList.remove('price-update-flash'), 500);
                                    }
                                }
                            })
                            .catch(error => console.error('Error fetching price:', error));
                    });
                }, 3000);
            }
        });
    </script>
</body>
</html>