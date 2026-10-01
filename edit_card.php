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

// 2. ดึงข้อมูลการ์ดหลัก
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

// 3. ดึงรูปภาพทั้งหมดจากตาราง card_images
$img_sql = "SELECT image_url FROM card_images WHERE card_id = ?";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $card_id);
$img_stmt->execute();
$images_result = $img_stmt->get_result();
$card_images = [];
while($img_row = $images_result->fetch_assoc()) {
    $card_images[] = $img_row['image_url'];
}
$img_stmt->close();
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
                        <!-- สำคัญ: enctype="multipart/form-data" -->
                        <form action="actions/edit_card_action.php" method="POST" enctype="multipart/form-data">
                            
                            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                            
                            <!-- โชว์รูปภาพปัจจุบันทั้งหมด -->
                            <div class="mb-4 text-center p-3 border rounded bg-light">
                                <p class="mb-3 fw-bold text-secondary">รูปภาพปัจจุบัน (ทั้งหมด):</p>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <?php if(!empty($card_images)): ?>
                                        <?php foreach($card_images as $img): ?>
                                            <img src="uploads/<?php echo $img; ?>" alt="Card Image" class="img-thumbnail shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- กรณีการ์ดเก่าที่ลงไว้ก่อนทำระบบหลายรูป จะดึงหน้าปกมาโชว์ -->
                                        <img src="uploads/<?php echo $card['image_url']; ?>" alt="Current Card" class="img-thumbnail shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">ชื่อการ์ด</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($card['title']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label fw-bold">ประเภทการ์ดเกม</label>
                                <?php $current_category = isset($card['category']) ? $card['category'] : 'อื่นๆ'; ?>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" disabled>-- เลือกประเภทการ์ด --</option>
                                    <option value="Yu-Gi-Oh!" <?php if($current_category == 'Yu-Gi-Oh!') echo 'selected'; ?>>Yu-Gi-Oh!</option>
                                    <option value="Pokémon" <?php if($current_category == 'Pokémon') echo 'selected'; ?>>Pokémon</option>
                                    <option value="One Piece" <?php if($current_category == 'One Piece') echo 'selected'; ?>>One Piece</option>
                                    <option value="Magic The Gathering" <?php if($current_category == 'Magic The Gathering') echo 'selected'; ?>>Magic The Gathering</option>
                                    <option value="อื่นๆ" <?php if($current_category == 'อื่นๆ') echo 'selected'; ?>>อื่นๆ</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">รายละเอียด / สภาพการ์ด</label>
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
                                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($card['end_time'])); ?>" required>
                                </div>
                            </div>

                            <!-- อัปเดต: เปลี่ยนเป็น name="images[]" และใส่ multiple -->
                            <div class="mb-4 p-3 border rounded">
                                <label for="images" class="form-label fw-bold text-primary">อัปโหลดรูปภาพใหม่ (เลือกได้หลายรูป / ไม่บังคับ)</label>
                                <input type="file" class="form-control" id="images" name="images[]" accept="image/jpeg, image/png, image/webp" multiple>
                                <div class="form-text text-danger">* หากอัปโหลดรูปใหม่ รูปเก่าทั้งหมดจะถูกแทนที่ / หากไม่ต้องการเปลี่ยนให้เว้นว่างไว้</div>
                                
                                <div id="imagePreviewContainer" class="mt-3 d-flex flex-wrap gap-2 justify-content-center"></div>
                            </div>

                            <button type="submit" class="btn btn-warning w-100 fw-bold fs-5 shadow-sm">บันทึกการแก้ไข</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- สคริปต์สำหรับพรีวิวรูปภาพเวลาอัปโหลดใหม่ -->
    <script>
        document.getElementById('images').addEventListener('change', function(event) {
            const previewContainer = document.getElementById('imagePreviewContainer');
            previewContainer.innerHTML = ''; 
            
            const files = event.target.files;
            
            if (files) {
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('img-thumbnail', 'shadow-sm', 'border-primary');
                        img.style.width = '100px';
                        img.style.height = '100px';
                        img.style.objectFit = 'cover';
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
</body>
</html>