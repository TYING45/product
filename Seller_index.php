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
        JOIN order_items oi ON o.id = oi.order_id
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
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員系統</title>
    <link rel="stylesheet" href="CSS/leftside.css">
    <link rel="stylesheet" href="CSS/topmenu.css">
    <link rel="stylesheet" href="CSS/dashboard.css">
    <style>
        main {
            margin-left: 200px;
            padding-top: 80px;
            padding-left: 20px;
            padding-right: 20px;
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
            }
        }
    .report-button {
        display: inline-block;
        padding: 10px 20px;
        background-color: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 10px;
        transition: background-color 0.3s ease;
}

    .report-button:hover {
        background-color: #2980b9;
}

    </style>
</head>
<body>
<div id="top-menu">
    <ul class="topmenu">
       <li> <button onclick="toggleSidebar()" class="img-button"></button></li>
       <li></li>
         <li><a href="https://secondhandshop.netlify.app/">網頁前端</a></li>
        <li><a href="Seller_data.php">賣家資料</a></li>
        <li><a href="logout.php">登出</a></li>
    </ul>   
</div>

<div id="leftside">
    <ul class="menuleft">
        <li><a href="Seller_index.php">首頁</a></li>

        <li>
            <a href="#" onclick="toggleMenu(event)">商品管理系統 <span class="count-badge"><?php echo $productCount; ?></span></a>
            <ul class="menuleft_hide">
                <li><a href="SellerAdd_Product.php">新增商品</a></li>
                <li><a href="Seller_Product.php">商品管理</a></li>
            </ul>
        </li>
        <li>
            <a href="#" onclick="toggleMenu(event)">訂單管理系統 <span class="count-badge"><?php echo $orderCount; ?></span></a>
            <ul class="menuleft_hide">
                <li><a href="Seller_Order.php">訂單資料管理</a></li>
            </ul>
        </li>   
    </ul>
</div>

<main>
    <h2>後端管理系統</h2>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <h3>商品數量</h3>
            <p><?php echo $productCount; ?> 筆</p>
        </div>
        <div class="dashboard-card">
            <h3>訂單數量</h3>
            <p><?php echo $orderCount; ?> 筆</p>
        </div>
        <div class="dashboard-card">
            <h3>報表查詢</h3>
            <a href="Seller_report.php" class="report-button">前往報表</a>
        </div>
    </div>
</main>


<script src="JS/leftside.js"></script>
</body>
</html>
