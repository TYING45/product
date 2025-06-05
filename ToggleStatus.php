<?php
session_start();
include("sql_php.php");

// 檢查是否登入且為賣家
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    echo "未授權訪問。請先登入為賣家帳號。";
    exit();
}

$Seller_ID = $_SESSION['Seller_ID'];

// 檢查是否提供商品 ID
if (!isset($_GET['id'])) {
    echo "未提供商品 ID";
    exit();
}

$productID = $_GET['id'];

// 先查出目前狀態
$query = "SELECT Shelf_status FROM product WHERE Product_ID = ? AND Seller_ID = ?";
$stmt = $link->prepare($query);
$stmt->bind_param("ss", $productID, $Seller_ID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "查無此商品或無權限修改。";
    exit();
}

$row = $result->fetch_assoc();
$currentStatus = $row["Shelf_status"];
$newStatus = ($currentStatus + 1) % 3;

// 更新狀態
$update = "UPDATE product SET Shelf_status = ? WHERE Product_ID = ? AND Seller_ID = ?";
$stmt = $link->prepare($update);
$stmt->bind_param("iss", $newStatus, $productID, $Seller_ID);
$stmt->execute();

header("Location: Seller_Product.php");
exit();
