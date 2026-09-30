<?php
session_start();
require_once 'includes/db_connect.php';

// 1. ตรวจสอบว่าล็อกอินแล้วและมีการส่ง id มาหรือไม่
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$card_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 2. ดึงข้อมูลการ์ดเดิมขึ้นมาแสดง และต้องเช็กด้วยว่าเป็นเจ้าของการ์ดหรือไม่
$sql = "SELECT c.*, a.end_time 
        FROM cards c 
        JOIN auctions a ON c.id = a.card_id 
        WHERE c.id = ? AND c.owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $card_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('ไม่พบข้อมูล หรือคุณไม่มีสิทธิ์แก้ไขการ์ดใบนี้!'); window.location.href='index.php';</script>";
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
    <title>แก้ไขข้อมูลการ์ด | Rare Card Auction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🃏 กลับหน้าหลัก</a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark py-3">
                        <h4 class="mb-0 text-center fw-bold">แก้ไขข้อมูลการประมูล</h4>
                    </div>
                    <div class="card-body p-4">
                        <form action="actions/edit_card_action.php" method="POST" enctype="multipart/form-data">
                            
                            <!-- ส่ง id ไปแบบซ่อน เพื่อให้รู้ว่าจะแก้ไขการ์ดใบไหน -->
                            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                            
                            <div class="mb-3 text-center">
                                <p class="mb-2 fw-bold">รูปภาพปัจจุบัน:</p>
                                <img src="uploads/<?php echo $card['image_url']; ?>" alt="Current Card" class="img-thumbnail" style="max-height: 200px;">
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">ชื่อการ์ด</label>
                                <!-- ดึงค่าเดิมมาใส่ใน value -->
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($card['title']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">รายละเอียด / สภาพการ์ด</label>
                                <!-- Textarea จะเอาค่าเดิมมาไว้ตรงกลางแท็ก -->
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($card['description']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="starting_price" class="form-label fw-bold">ราคาเริ่มต้น (บาท)</label>
                                    <input type="number" class="form-control" id="starting_price" name="starting_price" min="1" value="<?php echo $card['starting_price']; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="min_increment" class="form-label fw-bold">บิดขั้นต่ำครั้งละ</label>
                                    <input type="number" class="form-control" id="min_increment" name="min_increment" min="1" value="<?php echo isset($card['min_increment']) ? $card['min_increment'] : 100; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_time" class="form-label fw-bold">เวลาสิ้นสุดการประมูล</label>
                                    <!-- แปลงวันที่ให้อยู่ในฟอร์แมตที่ input type datetime-local เข้าใจ (YYYY-MM-DDThh:mm) -->
                                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($card['end_time'])); ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="image" class="form-label fw-bold">อัปโหลดรูปภาพใหม่ (ไม่บังคับ)</label>
                                <!-- เอา required ออก เพราะผู้ใช้อาจจะไม่อยากเปลี่ยนรูป -->
                                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg, image/png, image/webp">
                                <div class="form-text text-muted">* หากไม่ต้องการเปลี่ยนรูปภาพ ให้เว้นช่องนี้ไว้</div>
                            </div>

                            <button type="submit" class="btn btn-warning w-100 fw-bold fs-5 shadow-sm">บันทึกการแก้ไข</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>