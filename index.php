<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include("sql_php.php");

// 撈各區資料數量
function getCount($link, $table) {
    $result = $link->query("SELECT COUNT(*) AS total FROM `$table`");
    $row = $result->fetch_assoc();
    return $row['total'];
}

$productCount = getCount($link, 'product');
$memberCount = getCount($link, 'member');
$sellerCount = getCount($link, 'seller');
$orderCount = getCount($link, 'ordershop');
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
    </style>
</head>
<body>
<div id="top-menu">
    <ul class="topmenu">
       <li> <button onclick="toggleSidebar()" class="img-button"></button></li>
       <li></li>
        <li><a href="#">網頁前端</a></li>
        <li><a href="logout.php">登出</a></li>
    </ul>   
</div>

<div id="leftside">
    <ul class="menuleft">
        <li><a href="index.php">首頁</a></li>
        
        <li>
            <a href="#" onclick="toggleMenu(event)">網站管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="#">網站管理</a></li>
            </ul>
        </li>

        <li>
            <a href="#" onclick="toggleMenu(event)">商品管理系統 <span class="count-badge"><?php echo $productCount; ?></span></a>
            <ul class="menuleft_hide">
                <li><a href="Add_Product.php">新增商品</a></li>
                <li><a href="Product.php">商品管理</a></li>
            </ul>
        </li>

        <li>
            <a href="#" onclick="toggleMenu(event)">會員管理系統 <span class="count-badge"><?php echo $memberCount; ?></span></a>
            <ul class="menuleft_hide">
                <li><a href="Member.php">會員管理</a></li>
                <li><a href="Add_Member.php">新增會員</a></li>
            </ul>
        </li> <!-- 這裡少了這個關閉標籤 -->

        <li>
            <a href="#" onclick="toggleMenu(event)">賣家管理系統 <span class="count-badge"><?php echo $sellerCount; ?></span></a>
            <ul class="menuleft_hide">
                <li><a href="Seller.php">賣家管理</a></li>
                <li><a href="Add_Seller.php">新增賣家</a></li>
            </ul>
        </li>

        <li>
            <a href="#" onclick="toggleMenu(event)">訂單管理系統 <span class="count-badge"><?php echo $orderCount; ?></span></a>
            <ul class="menuleft_hide">
                <li><a href="Order.php">訂單資料管理</a></li>
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
            <h3>會員數量</h3>
            <p><?php echo $memberCount; ?> 人</p>
        </div>
        <div class="dashboard-card">
            <h3>賣家數量</h3>
            <p><?php echo $sellerCount; ?> 位</p>
        </div>
        <div class="dashboard-card">
            <h3>訂單數量</h3>
            <p><?php echo $orderCount; ?> 筆</p>
        </div>
    </div>
</main>

<script src="JS/leftside.js"></script>
</body>
</html>
