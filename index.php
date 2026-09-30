<?php
session_start();
// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'includes/db_connect.php'; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rare Card Auction | เว็บประมูลการ์ดหายาก</title>
    <!-- เชื่อมต่อ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 250px;
            object-fit: cover; /* ทำให้รูปภาพไม่เบี้ยวและเต็มกรอบ */
        }
    </style>
</head>
<body class="bg-light">
    
    <!-- แถบเมนูด้านบน (Navbar) -->
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

    <!-- เนื้อหาหลัก -->
    <div class="container text-center mt-5 mb-5">
        <h1 class="text-primary fw-bold">ระบบประมูลการ์ดหายาก</h1>
        <p class="lead text-muted">เตรียมพบกับการ์ดระดับแรร์ที่คุณไม่ควรพลาด!</p>
    </div>

    <!-- ส่วนแสดงรายการการ์ด -->
    <div class="container mb-5">
        <div class="row">
            <?php
            // อัปเดต: เพิ่ม c.owner_id เข้ามาในคำสั่ง SELECT เพื่อเช็กสิทธิ์เจ้าของการ์ด
            $sql = "SELECT c.id, c.title, c.image_url, c.starting_price, c.owner_id, a.end_time 
                    FROM cards c 
                    JOIN auctions a ON c.id = a.card_id 
                    WHERE a.status = 'active' 
                    ORDER BY a.end_time ASC";
            
            $result = $conn->query($sql);

            // ตรวจสอบว่ามีข้อมูลการ์ดหรือไม่
            if ($result->num_rows > 0) {
                // วนลูปดึงข้อมูลมาแสดงทีละใบ
                while($row = $result->fetch_assoc()) {
                    // แปลงรูปแบบวันที่ให้ดูง่ายขึ้น
                    $end_time_formatted = date('d/m/Y H:i', strtotime($row['end_time']));
            ?>
                    <!-- Bootstrap Card สำหรังแสดงข้อมูล 1 ใบ -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <!-- ดึงรูปภาพจากโฟลเดอร์ uploads -->
                            <img src="uploads/<?php echo $row['image_url']; ?>" class="card-img-top" alt="Card Image">
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-truncate"><?php echo $row['title']; ?></h5>
                                <p class="card-text text-danger fw-bold mb-1">
                                    ราคาเริ่มต้น: ฿<?php echo number_format($row['starting_price'], 2); ?>
                                </p>
                                <p class="card-text text-muted small">
                                    ปิดประมูล: <?php echo $end_time_formatted; ?>
                                </p>
                            </div>
                            <!-- อัปเดต: เพิ่มปุ่ม ลบ/แก้ไข สำหรับเจ้าของการ์ด -->
                            <div class="card-footer bg-white border-0 pb-3">
                                <a href="auction_room.php?id=<?php echo $row['id']; ?>" class="btn btn-primary w-100 fw-bold mb-2">เข้าร่วมประมูล</a>
                                
                                <?php 
                                // เช็กว่าคนที่ล็อกอินอยู่ คือเจ้าของการ์ดใบนี้หรือไม่
                                if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['owner_id']): 
                                ?>
                                    <!-- ปุ่มแก้ไขและลบ จะโผล่มาเฉพาะเจ้าของเท่านั้น -->
                                    <div class="d-flex justify-content-between">
                                        <!-- ปุ่ม Update (เดี๋ยวเราทำในสเต็ปถัดไป) -->
                                        <a href="edit_card.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning w-50 me-1">แก้ไข</a>
                                        
                                        <!-- ปุ่ม Delete -->
                                        <a href="actions/delete_card.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger w-50 ms-1" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบการ์ดใบนี้? (ข้อมูลการประมูลจะหายทั้งหมด)');">ลบทิ้ง</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                // ถ้ายังไม่มีการ์ดเลย ให้แสดงข้อความนี้
                echo '<div class="col-12 text-center text-muted mt-5">';
                echo '<h5>ยังไม่มีรายการการ์ดประมูลในขณะนี้</h5>';
                echo '<p>ลองกดปุ่ม "+ ลงประมูลการ์ด" ด้านบนเพื่อเริ่มรายการแรกเลย!</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>