<?php
session_start();

// 登入驗證
if (!isset($_SESSION['username']) || !isset($_SESSION['Seller_ID'])) {
    header("Location: login.php");
    exit();
}

include("sql_php.php");

// 查詢商品數量
function getSellerProductCount($link, $sellerId) {
    $stmt = $link->prepare("SELECT COUNT(*) AS total FROM product WHERE Seller_ID = ?");
    $stmt->bind_param("s", $sellerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

// 查詢賣家訂單數（distinct 訂單數，從 order_items 表抓產品）
function getSellerOrderCount($link, $sellerId) {
    $stmt = $link->prepare("
        SELECT COUNT(DISTINCT o.id) AS total
        FROM ordershop o
        JOIN order_items oi ON o.order = oi.order_ID
        JOIN product p ON oi.Product_ID = p.Product_ID
        WHERE p.Seller_ID = ?
    ");
    $stmt->bind_param("s", $sellerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

$sellerId = $_SESSION['Seller_ID'];
$productCount = getSellerProductCount($link, $sellerId);
$orderCount = getSellerOrderCount($link, $sellerId);
?>
