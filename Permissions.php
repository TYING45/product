<?php
include("sql_php.php");

$pageRow_records = 3; // 每頁顯示筆數
$num_pages = isset($_GET['page']) ? intval($_GET['page']) : 1;
$startRow_records = ($num_pages - 1) * $pageRow_records;

$where = "";
$params = [];
$types = "";
if (isset($_GET["keyword"]) && $_GET["keyword"] != "") {
    $where = " WHERE `Admin_name` LIKE ? OR `Admin_ID` LIKE ?";
    $keyword = "%" . $_GET["keyword"] . "%";
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "ss";
}

$total_query = "SELECT COUNT(*) FROM `admin`" . $where;
$stmt = $link->prepare($total_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$stmt->bind_result($total_records);
$stmt->fetch();
$stmt->close();

$total_pages = ceil($total_records / $pageRow_records);

$query = "SELECT * FROM `admin` " . $where . " ORDER BY `Admin_ID` DESC LIMIT ?, ?";
$stmt = $link->prepare($query);
$params[] = $startRow_records;
$params[] = $pageRow_records;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

function keepURL() {
    $keepURL = "";
    if (isset($_GET["keyword"])) {
        $keepURL .= "&keyword=" . urlencode($_GET["keyword"]);
    }
    return $keepURL;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>權限管理系統</title>
    <link rel="stylesheet" href="CSS/leftside.css">
    <link rel="stylesheet" href="CSS/topmenu.css">
    <link rel="stylesheet" href="CSS/form.css">
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
        <li><a href="#" onclick="toggleMenu(event)">網站管理系統</a>
            <ul class="menuleft_hide"><li><a href="#">網站管理</a></li></ul></li>
        <li><a href="#" onclick="toggleMenu(event)">商品管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Add_Product.php">新增商品</a></li>
                <li><a href="Product.php">商品管理</a></li>
            </ul></li>
        <li><a href="#" onclick="toggleMenu(event)">會員管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Member.php">會員管理</a></li>
                <li><a href="Add_Member.php">新增會員</a></li>
            </ul></li>
        <li><a href="#" onclick="toggleMenu(event)">權限管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Permissions.php">權限管理</a></li>
                <li><a href="Add_permissions.php">新增權限</a></li>
            </ul></li>
        <li><a href="#" onclick="toggleMenu(event)">賣家管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Seller.php">賣家管理</a></li>
                <li><a href="Add_Seller.php">新增賣家</a></li>
            </ul></li>
        <li><a href="#" onclick="toggleMenu(event)">訂單管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Order.php">訂單資料管理</a></li>
                <li><a href="Time_Order.php">歷史訂單管理</a></li>
            </ul></li>
    </ul>
</div>

<main class="main">
    <p><b><font size="5">權限管理系統</font></b></p>

    <?php
    if ($total_records == 0) {
        echo "<p style='color:red;'>查無資料</p>";
    } else {
        echo "<p>目前共有 <strong>{$total_records}</strong> 筆資料</p>";
    }
    ?>

    <form method="GET" action="Permissions.php">
        <input name="keyword" type="text" id="keyword" placeholder="請輸入關鍵字(ID、姓名)" size="12" 
            value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
        <input type="submit" value="查詢">
        <input type="button" onclick="location.href='CSV_Permissions.php'" value="輸出CSV">
    </form>

    <table border="1">
        <tr>
            <th>管理員編號</th>
            <th>姓名</th>
            <th>電話</th>
            <th>Email</th>
            <th>帳號</th>
            <th>密碼</th>
            <th>修改</th>
            <th>刪除</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row["Admin_ID"] ?></td>
                <td><?= $row["Admin_name"] ?></td>
                <td><?= $row["Phone"] ?></td>
                <td><?= $row["Email"] ?></td>
                <td><?= $row["username"] ?></td>
                <td>********</td>
                <td><a href="Update_Admin.php?id=<?= $row["Admin_ID"] ?>">修改</a></td>
                <td><a href="Userdelete.php?id=<?= $row["Admin_ID"] ?>">刪除</a></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <?php if ($total_pages > 1): ?>
        <div style="margin-top: 10px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i . keepURL(); ?>" style="margin:0 5px;<?= $i == $num_pages ? 'font-weight:bold;' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</main>

<script src="JS/leftside.js"></script>
</body>
</html>
