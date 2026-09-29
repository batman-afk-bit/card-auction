<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | Rare Card Auction</title>
    <!-- เชื่อมต่อ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0">สมัครสมาชิก</h4>
                    </div>
                    <div class="card-body p-4">
                        <!-- กำหนด Action ไปที่ไฟล์ประมวลผลในโฟลเดอร์ actions -->
                        <form action="actions/register_action.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">ชื่อผู้ใช้ (Username)</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">อีเมล (Email)</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">รหัสผ่าน (Password)</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านอีกครั้ง</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">สร้างบัญชี</button>
                        </form>
                        <div class="mt-3 text-center">
                            <p class="mb-0">มีบัญชีอยู่แล้ว? <a href="login.php" class="text-decoration-none">เข้าสู่ระบบที่นี่</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>