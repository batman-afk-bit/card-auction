<?php
session_start();
require_once 'includes/db_connect.php'; 

// ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มการ์ดลงประมูล | Rare Card Auction</title>
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

        /* สไตล์สำหรับรูปพรีวิว */
        .preview-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--color-accent);
        }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php" style="color: var(--color-accent);">🃏 RareCard Auction</a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm fw-bold">กลับหน้าหลัก</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card theme-box shadow-lg">
                    <div class="card-header bg-dark border-bottom border-secondary py-3 text-center">
                        <h4 class="mb-0 fw-bold" style="color: var(--color-accent);">เพิ่มการ์ดลงประมูล</h4>
                    </div>
                    <div class="card-body p-4">
                        <!-- สำคัญ: ต้องมี enctype="multipart/form-data" เสมอเมื่อมีการอัปโหลดไฟล์ -->
                        <form action="actions/add_card_action.php" method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">ชื่อการ์ด (Card Title)</label>
                                <input type="text" class="form-control custom-input" id="title" name="title" placeholder="เช่น Blue-Eyes White Dragon" required>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label fw-bold">ประเภทการ์ดเกม</label>
                                <select class="form-select custom-input" id="category" name="category" required>
                                    <option value="" selected disabled>-- เลือกประเภทการ์ด --</option>
                                    <option value="Yu-Gi-Oh!">Yu-Gi-Oh!</option>
                                    <option value="Pokémon">Pokémon</option>
                                    <option value="One Piece">One Piece</option>
                                    <option value="Magic The Gathering">Magic The Gathering</option>
                                    <option value="อื่นๆ">อื่นๆ</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">รายละเอียด / สภาพการ์ด</label>
                                <textarea class="form-control custom-input" id="description" name="description" rows="4" placeholder="ระบุตำหนิ หรือเกรด PSA/BGS..." required></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="starting_price" class="form-label fw-bold">ราคาเริ่มต้น (บาท)</label>
                                    <input type="number" class="form-control custom-input" id="starting_price" name="starting_price" min="1" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="min_increment" class="form-label fw-bold">บิดขั้นต่ำครั้งละ (บาท)</label>
                                    <input type="number" class="form-control custom-input" id="min_increment" name="min_increment" min="1" value="100" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_time" class="form-label fw-bold">เวลาสิ้นสุดการประมูล</label>
                                    <input type="datetime-local" class="form-control custom-input" id="end_time" name="end_time" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="images" class="form-label fw-bold">รูปภาพการ์ด (อัปโหลดได้หลายรูป)</label>
                                <!-- อัปเดต: ใส่ name เป็น Array (images[]) และเติมคำว่า multiple -->
                                <input type="file" class="form-control custom-input" id="images" name="images[]" accept="image/jpeg, image/png, image/webp" multiple required>
                                <div class="form-text" style="color: #fb7185;">* รองรับเฉพาะไฟล์ .jpg, .png, .webp สามารถเลือกคลุมได้หลายไฟล์พร้อมกัน</div>
                                
                                <!-- พื้นที่สำหรับโชว์พรีวิวรูปภาพ -->
                                <div id="imagePreviewContainer" class="mt-3 d-flex flex-wrap gap-2"></div>
                            </div>

                            <button type="submit" class="btn btn-theme-primary w-100 fw-bold fs-5 py-2">ลงประมูลการ์ด</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- สคริปต์พรีวิวรูปภาพ -->
    <script>
        document.getElementById('images').addEventListener('change', function(event) {
            const previewContainer = document.getElementById('imagePreviewContainer');
            previewContainer.innerHTML = ''; // เคลียร์รูปเก่าทิ้งก่อน
            
            const files = event.target.files;
            
            if (files) {
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('preview-img', 'shadow-sm');
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
</body>
</html>