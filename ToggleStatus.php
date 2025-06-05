<?php
session_start();
include("sql_php.php");

// 權限檢查
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'seller'])) {
    echo "未授權訪問，請先登入。";
    exit();
}

if (!isset($_GET['id'])) {
    echo "未指定商品ID。";
    exit();
}

$productID = $_GET['id'];
$role = $_SESSION['role'];

if ($role === 'seller') {
    if (!isset($_SESSION['Seller_ID'])) {
        echo "未授權訪問，請先登入賣家帳號。";
        exit();
    }
    $Seller_ID = $_SESSION['Seller_ID'];
    $sql = "SELECT Shelf_status FROM product WHERE Product_ID = ? AND Seller_ID = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ss", $productID, $Seller_ID);
} elseif ($role === 'admin') {
    // 管理員不限制 Seller_ID
    $sql = "SELECT Shelf_status FROM product WHERE Product_ID = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $productID);
}

// 取得當前狀態
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "查無此商品或無權限修改。";
    exit();
}

$row = $result->fetch_assoc();
$currentStatus = (int)$row['Shelf_status'];

// 狀態切換：0->1->2->0
$newStatus = ($currentStatus + 1) % 3;

// 更新狀態的 SQL
if ($role === 'seller') {
    $updateSql = "UPDATE product SET Shelf_status = ? WHERE Product_ID = ? AND Seller_ID = ?";
    $updateStmt = $link->prepare($updateSql);
    $updateStmt->bind_param("iss", $newStatus, $productID, $Seller_ID);
} else { // admin
    $updateSql = "UPDATE product SET Shelf_status = ? WHERE Product_ID = ?";
    $updateStmt = $link->prepare($updateSql);
    $updateStmt->bind_param("is", $newStatus, $productID);
}

if ($updateStmt->execute()) {
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'Seller_Product.php'));
    exit();
} else {
    echo "狀態更新失敗。";
}
?>
