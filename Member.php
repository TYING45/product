<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include("sql_php.php");
$pageRow_records = 10; // 每頁顯示 10 筆資料
$num_pages = isset($_GET['page']) ? intval($_GET['page']) : 1; // 取得目前頁數
$startRow_records = ($num_pages - 1) * $pageRow_records; // 計算起始位置

// 設定查詢條件
$where = "";
$params = [];
$types = "";
if (isset($_GET["keyword"]) && $_GET["keyword"] != "") {
    $where = " WHERE `Member_name` LIKE ? OR `Member_ID` LIKE ?";
    $keyword = "%" . $_GET["keyword"] . "%";
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "ss";
}


// 計算總筆數
$total_query = "SELECT COUNT(*) FROM `member`" . $where;
$stmt = $link->prepare($total_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$stmt->bind_result($total_records);
$stmt->fetch();
$stmt->close();

$total_pages = ceil($total_records / $pageRow_records); // 計算總頁數

// 取得資料（加入分頁）
$query = "SELECT * FROM `member` " . $where . " ORDER BY `Member_ID` DESC LIMIT ?, ?";
$stmt = $link->prepare($query);

// 分頁數據
$params[] = $startRow_records;
$params[] = $pageRow_records;
$types .= "ii";
$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();

// 保留 URL 參數
function keepURL()
{
    $keepURL = "";
    if (isset($_GET["keyword"])) {
        $keepURL .= "&keyword=" . urlencode($_GET["keyword"]);
    }
    if (isset($_GET["cid"])) {
        $keepURL .= "&cid=" . $_GET["cid"];
    }
    return $keepURL;
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['stock'])) {
    $id = intval($_POST['id']);       // 獲取ID
    $stock = intval($_POST['stock']);
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
        <li><a href="https://secondhandshop.netlify.app/">網頁前端</a></li>
        <li><a href="logout.php">登出</a></li>
    </ul>   
</div>
	
	<!---------------------->
	<div id="leftside">
        <ul class="menuleft">
            <li>
                <a href="index.php">首頁</a>
            </li>
            <li>
                <a href="#" onclick="toggleMenu(event)">商品管理系統</a>
                <ul class="menuleft_hide">
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
                <a href="#" onclick="toggleMenu(event)">管理員管理系統</a>
                <ul class="menuleft_hide">
                    <li><a href="Permissions.php">管理員系統</a></li>
                    <li><a href="Add_permissions.php">新增管理員</a></li>
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
    if ($total_records == 0) {
    echo "<p style='color: red;'>查無資料</p>";
    } else {
    echo "<p>目前共有 <strong>{$total_records}</strong> 筆資料</p>";
    }
    ?>
    <form method="get" action="">
        <form method="GET" action="Member.php">
    <input name="keyword" type="text" id="keyword" placeholder="請輸入關鍵字(ID、姓名)" size="12" 
        value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
    <input type="submit" name="import" id="button0" value="查詢">
    </form>
        <input type="button" onclick="location.href='CSV_Member.php'" value="輸出CSV">
        <input type="button" onclick="location.href='Upload_Member.html'" value="匯入資料">

    </form>
    <table border="1">
        <tr>
            <th>會員ID</th>
            <th>姓名</th>
            <th>電話</th>
            <th>Email</th>
            <th>密碼</th>
            <th>詳細</th>
            <th>刪除</th>
        </tr>
        <?php
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["Member_ID"] . "</td>";
            echo "<td>" . $row["Member_name"] . "</td>";
            echo "<td>" . $row["Phone"] . "</td>";
            echo "<td>" . $row["Email"] . "</td>";
            echo "<td> ******* </td>";
            echo "<td><a href='Update_Member.php?id=" . $row["Member_ID"] . "'>編輯</a></td>";
            echo "<td><a href='Del_Member.php?id=" . $row["Member_ID"] . "'>刪除</a></td>";
            echo "</tr>";
        }
        ?>
        <table id="page">
    <tr>
        <!-- 分頁 -->
        <?php
        $range = 2; // 設定當前頁面前後最多顯示幾個頁碼

        if ($num_pages > ($range + 1)) {
            echo "<a href='Member.php?page=1" . keepURL() . "'>1</a> ";
            if ($num_pages > ($range + 2)) {
                echo "... ";
            }
        }

        for ($i = max(1, $num_pages - $range); $i <= min($total_pages, $num_pages + $range); $i++) {
            if ($i == $num_pages) {
                echo "<span style='font-weight:bold; color:red;'>$i</span> ";
            } else {
                echo "<a href='Member.php?page={$i}" . keepURL() . "'>$i</a> ";
            }
        }

        if ($num_pages < ($total_pages - $range)) {
            if ($num_pages < ($total_pages - $range - 1)) {
                echo "... ";
            }
            echo "<a href='Member.php?page={$total_pages}" . keepURL() . "'>$total_pages</a>";
        }
        ?>
    </tr>
</table>
    </table>
</main>
<script src="JS/leftside.js"></script>
</body>
</html>
