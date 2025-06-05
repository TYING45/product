<?php
include("sql_php.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $Order_ID = $_POST['Order_ID'] ?? $_GET['id'] ?? null;
    $Order_status = $_POST['Order_status'] ?? '';
    $Ship_Date = $_POST['Ship_Date'] ?? null;
    $Transport = $_POST['Transport'] ?? '';
    $Tracking_number = $_POST['Tracking_number'] ?? '';

    if ($Order_ID) {
        $sql = "UPDATE ordershop 
                SET Order_status = ?, Ship_Date = ?, Transport = ?, Tracking_number = ?
                WHERE Order_ID = ?";
        $stmt = $link->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssss", $Order_status, $Ship_Date, $Transport, $Tracking_number, $Order_ID);
            $success = $stmt->execute();
            if ($success) {
                echo "<script>alert('訂單資料已更新'); window.location.href='Seller_Order.php';</script>";
            } else {
                echo "<script>alert('更新失敗: " . $stmt->error . "'); history.back();</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('資料庫連線失敗'); history.back();</script>";
        }
    } else {
        echo "<script>alert('缺少訂單編號'); history.back();</script>";
    }
} else {
    echo "<script>alert('無效的請求'); history.back();</script>";
}
?>
