<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$buyer_id = $_SESSION['user_id'];
$auction_id = $_POST['auction_id'];
$amount_to_pay = (float)$_POST['amount'];

// 1. ตรวจสอบข้อมูลการประมูลว่าถูกต้องหรือไม่ (เป็นสถานะ active และหมดเวลาแล้วจริงๆ)
$check_auction_sql = "SELECT a.card_id, c.owner_id AS seller_id, a.status 
                      FROM auctions a
                      JOIN cards c ON a.card_id = c.id
                      WHERE a.id = ? AND a.status = 'active' AND a.end_time < NOW()";
$check_stmt = $conn->prepare($check_auction_sql);
$check_stmt->bind_param("i", $auction_id);
$check_stmt->execute();
$auction_result = $check_stmt->get_result();

if ($auction_result->num_rows === 0) {
    die("<script>alert('ไม่พบรายการนี้ หรือรายการนี้ถูกจัดการไปแล้ว'); window.location.href='../cart.php';</script>");
}
$auction_data = $auction_result->fetch_assoc();
$check_stmt->close();

$card_id = $auction_data['card_id'];
$seller_id = $auction_data['seller_id'];

// ป้องกันกรณีผู้ขายซื้อการ์ดตัวเอง (แม้ตามระบบไม่ควรเกิดขึ้น)
if ($buyer_id === $seller_id) {
    die("<script>alert('ไม่สามารถชำระเงินให้การ์ดของตัวเองได้'); window.location.href='../cart.php';</script>");
}

// 2. ตรวจสอบยอดเงินในกระเป๋าของผู้ซื้อ
$wallet_sql = "SELECT wallet_balance FROM users WHERE id = ?";
$wallet_stmt = $conn->prepare($wallet_sql);
$wallet_stmt->bind_param("i", $buyer_id);
$wallet_stmt->execute();
$buyer_wallet = $wallet_stmt->get_result()->fetch_assoc()['wallet_balance'];
$wallet_stmt->close();

if ($buyer_wallet < $amount_to_pay) {
    die("<script>alert('ยอดเงินใน Wallet ไม่เพียงพอ กรุณาเติมเงิน!'); window.location.href='../topup.php';</script>");
}

// --- เริ่มกระบวนการทำ Transaction (หักเงิน-เพิ่มเงิน-เปลี่ยนเจ้าของ) ---
// ใช้ begin_transaction() เพื่อให้ชัวร์ว่าถ้ามีอะไร Error กลางคัน ระบบจะไม่ตัดเงินฟรีๆ
$conn->begin_transaction();

try {
    // 3. หักเงินผู้ซื้อ
    $deduct_sql = "UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?";
    $deduct_stmt = $conn->prepare($deduct_sql);
    $deduct_stmt->bind_param("di", $amount_to_pay, $buyer_id);
    $deduct_stmt->execute();
    $deduct_stmt->close();

    // 4. เพิ่มเงินให้ผู้ขาย
    $add_sql = "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?";
    $add_stmt = $conn->prepare($add_sql);
    $add_stmt->bind_param("di", $amount_to_pay, $seller_id);
    $add_stmt->execute();
    $add_stmt->close();

    // 5. อัปเดตสถานะการประมูลเป็น completed (ชำระแล้ว)
    $update_auction_sql = "UPDATE auctions SET status = 'completed' WHERE id = ?";
    $ua_stmt = $conn->prepare($update_auction_sql);
    $ua_stmt->bind_param("i", $auction_id);
    $ua_stmt->execute();
    $ua_stmt->close();

    // 6. เปลี่ยนชื่อเจ้าของการ์ดใบนี้ (owner_id) ให้เป็นชื่อผู้ชนะ! (โอนกรรมสิทธิ์)
    $transfer_card_sql = "UPDATE cards SET owner_id = ? WHERE id = ?";
    $tc_stmt = $conn->prepare($transfer_card_sql);
    $tc_stmt->bind_param("ii", $buyer_id, $card_id);
    $tc_stmt->execute();
    $tc_stmt->close();

    // ถ้าทุกอย่างผ่านฉลุย ให้กดยืนยัน (Commit) บันทึกลงฐานข้อมูลจริงๆ
    $conn->commit();

    echo "<script>alert('ชำระเงินสำเร็จ! การ์ดใบนี้เป็นของคุณแล้ว'); window.location.href='../profile.php';</script>";

} catch (Exception $e) {
    // ถ้ามีปัญหาบรรทัดไหน ให้ยกเลิก (Rollback) คืนเงินทั้งหมด
    $conn->rollback();
    echo "<script>alert('เกิดข้อผิดพลาดของระบบ: " . $e->getMessage() . "'); window.location.href='../cart.php';</script>";
}

$conn->close();
?>