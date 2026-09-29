<?php
// ต้องมี session_start() เสมอในหน้าที่ต้องการดึงข้อมูลผู้ใช้มาแสดง
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rare Card Auction | เว็บประมูลการ์ดหายาก</title>
    <!-- เชื่อมต่อ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <!-- แถบเมนูด้านบน (Navbar) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🃏 RareCard Auction</a>
            
            <div class="d-flex align-items-center">
                <?php 
                // เช็คว่ามีตัวแปร Session 'user_id' อยู่หรือไม่ (ล็อกอินหรือยัง)
                if (isset($_SESSION['user_id'])): 
                ?>
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
    <div class="container text-center mt-5">
        <h1 class="text-primary fw-bold">ระบบประมูลการ์ดหายาก</h1>
        <p class="lead text-muted">เตรียมพบกับการ์ดระดับแรร์ที่คุณไม่ควรพลาด!</p>
        <button class="btn btn-success btn-lg mt-3 shadow">ดูรายการการ์ดทั้งหมด</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>