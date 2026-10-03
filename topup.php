<?php
session_start();
require_once 'includes/db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลยอดเงินปัจจุบัน
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
    <title>เติมเงินเข้าระบบ | Rare Card Auction</title>
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

        .custom-input {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: var(--text-light) !important;
        }
        .custom-input:focus {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: #4ade80 !important;
            box-shadow: 0 0 0 0.25rem rgba(74, 222, 128, 0.25) !important;
        }
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
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="theme-box p-4 rounded shadow-lg text-center">
                    <h3 class="fw-bold mb-3" style="color: #4ade80;">💳 เติมเงินเข้า Wallet</h3>
                    <p class="text-light mb-4">ยอดเงินคงเหลือปัจจุบัน: <strong class="fs-4">฿<?php echo number_format($current_balance, 2); ?></strong></p>

                    <div class="bg-dark p-3 rounded border border-secondary mb-4">
                        <p class="mb-1 text-muted">โอนเงินเข้าบัญชี (จำลอง)</p>
                        <h4 class="text-info fw-bold mb-1">ธนาคาร กสิกรไทย</h4>
                        <h3 class="fw-bold text-light tracking-wide mb-1">012-3-45678-9</h3>
                        <p class="mb-0 text-muted">ชื่อบัญชี: บจก. แรร์การ์ด ออคชั่น</p>
                    </div>

                    <!-- สำคัญ: enctype="multipart/form-data" สำหรับอัปโหลดไฟล์ -->
                    <form action="actions/topup_action.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3 text-start">
                            <label for="amount" class="form-label fw-bold text-light">จำนวนเงินที่โอน (บาท)</label>
                            <input type="number" step="0.01" class="form-control custom-input fs-5" id="amount" name="amount" required min="1">
                        </div>
                        <div class="mb-4 text-start">
                            <label for="slip_image" class="form-label fw-bold text-light">แนบหลักฐานการโอนเงิน (สลิป)</label>
                            <input type="file" class="form-control custom-input" id="slip_image" name="slip_image" accept="image/jpeg, image/png, image/webp" required>
                            
                            <!-- พื้นที่สำหรับโชว์พรีวิวรูปสลิป -->
                            <div id="slipPreviewContainer" class="mt-3 text-center"></div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold fs-5 shadow-sm">ส่งหลักฐานการโอนเงิน</button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        // สคริปต์พรีวิวรูปสลิป
        document.getElementById('slip_image').addEventListener('change', function(event) {
            const previewContainer = document.getElementById('slipPreviewContainer');
            previewContainer.innerHTML = ''; 
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('img-thumbnail', 'border-success', 'shadow-sm');
                    img.style.maxWidth = '250px';
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>