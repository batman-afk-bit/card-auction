<?php
session_start();

// ล้างค่า Session ทั้งหมด
session_unset();

// ทำลาย Session
session_destroy();

// เด้งกลับไปหน้าแรก
header("Location: ../index.php");
exit();
?>