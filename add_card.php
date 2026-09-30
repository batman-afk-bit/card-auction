<?php
session_start();
// ตรวจสอบว่าล็อกอินหรือยัง ถ้ายังให้เด้งไปหน้า login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อนลงประมูลการ์ด'); window.location.href='login.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงประมูลการ์ด | Rare Card Auction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- นำ Navbar แบบหน้า index.php มาใส่แบบย่อเพื่อให้กลับหน้าแรกได้ง่าย -->
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🃏 กลับหน้าหลัก</a>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0 text-center">เพิ่มการ์ดลงประมูล</h4>
                    </div>
                    <div class="card-body p-4">
                        <!-- ข้อควรระวัง: ต้องมี enctype ถึงจะอัปโหลดรูปภาพได้ -->
                        <form action="actions/add_card_action.php" method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">ชื่อการ์ด (Card Title)</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="เช่น Blue-Eyes White Dragon" required>
                            </div>

                            <!-- อัปเดต: เพิ่มช่อง Select สำหรับเลือกประเภทการ์ด -->
                            <div class="mb-3">
                                <label for="category" class="form-label fw-bold">ประเภทการ์ดเกม</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" disabled selected>-- เลือกประเภทการ์ด --</option>
                                    <option value="Yu-Gi-Oh!">Yu-Gi-Oh!</option>
                                    <option value="Pokémon">Pokémon</option>
                                    <option value="One Piece">One Piece</option>
                                    <option value="Magic The Gathering">Magic The Gathering</option>
                                    <option value="อื่นๆ">อื่นๆ</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">รายละเอียด / สภาพการ์ด</label>
                                <textarea class="form-control" id="description" name="description" rows="4" placeholder="ระบุตำหนิ หรือเกรด PSA/BGS..." required></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="starting_price" class="form-label fw-bold">ราคาเริ่มต้น (บาท)</label>
                                    <input type="number" class="form-control" id="starting_price" name="starting_price" min="1" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="min_increment" class="form-label fw-bold">บิดขั้นต่ำครั้งละ (บาท)</label>
                                    <input type="number" class="form-control" id="min_increment" name="min_increment" min="1" value="100" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_time" class="form-label fw-bold">เวลาสิ้นสุดการประมูล</label>
                                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="image" class="form-label fw-bold">รูปภาพการ์ด</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg, image/png, image/webp" required>
                                <div class="form-text text-danger">* รองรับเฉพาะไฟล์ .jpg, .png, .webp เท่านั้น</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold fs-5">ลงประมูลการ์ด</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>