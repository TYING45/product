<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include("sql_php.php");

if (isset($_GET["cid"]) && ($_GET["cid"] != "")) {
    $stmt = $link->prepare("SELECT * FROM `ordershop` WHERE `Order_ID` = ? ORDER BY Order_ID DESC");
    $stmt->bind_param("i", $_GET["cid"]);
} elseif (isset($_GET["keyword"]) && ($_GET["keyword"] != "")) {
    $stmt = $link->prepare("SELECT * FROM `ordershop` WHERE `Order_name` LIKE ? OR `Order_ID` LIKE ? ORDER BY Order_ID DESC");
    $keyword = "%" . $_GET["keyword"] . "%";
    $stmt->bind_param("ss", $keyword, $keyword);
} else {
    $stmt = $link->prepare("SELECT * FROM `ordershop` ORDER BY Order_ID DESC");
}

$stmt->execute();
$result = $stmt->get_result();
$rows = $result->num_rows;

function keepURL() {
    $keepURL = "";
    if (isset($_GET["keyword"])) $keepURL .= "&keyword=" . urlencode($_GET["keyword"]);
    if (isset($_GET["cid"])) $keepURL .= "&cid=" . $_GET["cid"];
    return $keepURL;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員系統</title>
    <link rel="stylesheet" href="CSS/leftside.css">
	<link rel="stylesheet" href="CSS/topmenu.css">
    <link href="CSS/form.css" rel="stylesheet" type="text/css">
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
            <li>
                <a href="index.php">首頁</a>
            </li>
            <li>
                <a href="#" onclick="toggleMenu(event)">網站管理系統</a>
                <ul class="menuleft_hide">
                    <li><a href="#">網站管理</a></li>
                </ul>
            </li>
            <li>
                <a href="#" onclick="toggleMenu(event)">商品管理系統</a>
                <ul class="menuleft_hide">
                    <li><a href="Add_Product.php">新增商品</a></li>
                    <li><a href="Product.php">商品管理</a></li>
                </ul>
            </li>

            <li>
                <a href="#" onclick="toggleMenu(event)">會員管理系統</a>
                <ul class="menuleft_hide">
                    <li><a href="Member.php">會員管理</a></li>
                    <li><a href="Add_Member.php">新增會員</a></li>
                </ul>
            </li>
            <li>
                <a href="#" onclick="toggleMenu(event)">權限管理系統</a>
                <ul class="menuleft_hide">
                    <li><a href="Permissions.php">權限管理</a></li>
                    <li><a href="Add_permissions.php">新增權限</a></li>
                </ul>
            </li>
            <li>
                <a href="#" onclick="toggleMenu(event)">賣家管理系統</a>
                <ul class="menuleft_hide">
                    <li><a href="Seller.php">賣家管理</a></li>
                    <li><a href="Add_Seller.php">新增賣家</a></li>
                </ul>
            </li>
            <li><a href="#" onclick="toggleMenu(event)">訂單管理系統</a>
            <ul class="menuleft_hide">
                    <li><a href="Order.php">訂單資料管理</a></li>
                </ul>
            </li>   
        </ul>
    </div>

<!-- id  權限管理 -->
    <main class="main">
    <p><b><font size="5">會員管理系統</font></b></p>
    <?php
    if ($rows == 0) {
        echo "查無資料";
    } else {
        echo "目前有 " . $rows . " 筆資料";
    }
    ?>
    <form method="get" action="">
        <select name="num">
            <option value="1">1</option>
            <option value="2" selected>2</option>
        </select>
        <input name="keyword" type="text" id="keyword" placeholder="請輸入關鍵字" size="12">
        <input type="submit" name="import" id="button0" value="查詢">
        <input type="button" onclick="location.href='CSV_Order.php'" value="輸出CSV">
    </form>
    <table border="1">
        <tr>
            <th>訂單ID</th>
            <th>ID</th>
            <th>訂單日期</th>
            
            <th>訂購人</th>
            <th>付款方式</th>
            <th>訂單金額</th>
            <th>處理進度</th>
            <th>修改</th>
            <th>刪除</th>
        </tr>
        <?php
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["id"] . "</td>";
            echo "<td>" . $row["Order_ID"] . "</td>";
            echo "<td>" . $row["Order_Date"] . "</td>";
            echo "<td>" . $row["billing_name"] . "</td>";
            echo "<td>" . $row["Payment_method"] . "</td>";
            echo "<td>" . $row["total_price"] . "</td>";
            echo "<td>" . $row["Order_status"] . "</td>";
            echo "<td><a href='Update_Order.php?Order_ID=" . urlencode($row["Order_ID"]) . "'>詳細</a></td>";
            echo "<td><a href='Del_Order.php?id=" . $row["Order_ID"] . "'>刪除</a></td>";
            echo "</tr>";
        }
        ?>
    </table>
</main>
<script src="JS/leftside.js"></script>
</body>
</html>
