<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | Rare Card Auction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white text-center py-3">
                        <h4 class="mb-0">เข้าสู่ระบบ</h4>
                    </div>
                    <div class="card-body p-4">
                        <!-- กำหนด Action ไปที่ไฟล์ login_action.php -->
                        <form action="actions/login_action.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">อีเมล (Email)</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">รหัสผ่าน (Password)</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold">เข้าสู่ระบบ</button>
                        </form>
                        <div class="mt-3 text-center">
                            <p class="mb-0">ยังไม่มีบัญชี? <a href="register.php" class="text-decoration-none">สมัครสมาชิกที่นี่</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>