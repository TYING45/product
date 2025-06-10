<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include("sql_php.php");
$isAdmin = isset($_SESSION['Admin_ID']);
$pageRow_records = 10;
$num_pages = isset($_GET['page']) ? intval($_GET['page']) : 1;
$startRow_records = ($num_pages - 1) * $pageRow_records;

$where = "";
$params = [];
$types = "";

if (!empty($_GET["keyword"])) {
    $where = " WHERE Product_name LIKE ? OR Product_ID LIKE ? OR Type LIKE ? OR Seller_ID LIKE ?";
    $keyword = "%" . $_GET["keyword"] . "%";
    $params = array_fill(0, 4, $keyword);
    $types = str_repeat("s", 4);
}

$total_query = "SELECT COUNT(*) FROM product" . $where;
$stmt = $link->prepare($total_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$stmt->bind_result($total_records);
$stmt->fetch();
$stmt->close();

$total_pages = ceil($total_records / $pageRow_records);

$query = "SELECT * FROM product" . $where . " ORDER BY Product_ID DESC LIMIT ?, ?";
$stmt = $link->prepare($query);
$params[] = $startRow_records;
$params[] = $pageRow_records;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

function keepURL() {
    $keepURL = "";
    if (!empty($_GET["keyword"])) {
        $keepURL .= "&keyword=" . urlencode($_GET["keyword"]);
    }
    if (!empty($_GET["cid"])) {
        $keepURL .= "&cid=" . $_GET["cid"];
    }
    return $keepURL;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>商品管理系統</title>
    <link rel="stylesheet" href="CSS/leftside.css" />
    <link rel="stylesheet" href="CSS/topmenu.css" />
    <link rel="stylesheet" href="CSS/form.css" />
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

<div id="leftside">
    <ul class="menuleft">
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
    </ul>
</div>

<main class="main">
    <h2>商品管理系統</h2>
    <?php if ($total_records == 0): ?>
        <p style='color: red;'>查無資料</p>
    <?php else: ?>
        <p>目前共有 <strong><?= $total_records ?></strong> 筆資料</p>
    <?php endif; ?>

    <form method="GET" action="Product.php">
        <input name="keyword" type="text" placeholder="請輸入關鍵字(ID、商品名稱、商品種類)" size="20"
               value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
        <input type="submit" value="查詢">
        <input type="button" onclick="location.href='CSV_Product.php'" value="輸出CSV">
    </form>

    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
        <tr>
            <th>圖片</th>
            <th>商品ID</th>
	    <th賣家ID</th>>
            <th>商品名稱</th>
            <th>商品種類</th>
            <th>價格</th>
            <th>庫存數量</th>
            <th>備註</th>
            <th>上架狀態</th>
            <th>詳細</th>
            <th>刪除</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><img src="uploads/<?php echo htmlspecialchars($row["Image"] ?: "default.png"); ?>" width="100"></td>
                <td><?= htmlspecialchars($row['Product_ID']) ?></td>
		<td><?= htmlspecialchars($row['Seller_ID']) ?></td>    
                <td><?= htmlspecialchars($row['Product_name']) ?></td>
                <td><?= htmlspecialchars($row['Type']) ?></td>
                <td><?= htmlspecialchars($row['price']) ?></td>
                <td><?= htmlspecialchars($row['quantity']) ?></td>
                <td><?= htmlspecialchars($row['Remark']) ?></td>
                <td>
                    <?php
                        $statusLabel = ['下架', '上架中', '缺貨'][$row['Shelf_status']];
                        $nextStatusLabel = ['上架', '缺貨', '下架'][$row['Shelf_status']];
                        echo $statusLabel . "<br>";
                        if ($isAdmin):
                    ?>
                        <a href="ToggleStatus.php?id=<?= $row['Product_ID'] ?>"
                           onclick="return confirm('確定要切換為【<?= $nextStatusLabel ?>】嗎？');">
                            切換為 <?= $nextStatusLabel ?>
                        </a>
                    <?php else: ?>
                        <span style="color: gray;">無權限</span>
                    <?php endif; ?>
                </td>
                <td><a href="Update_Product.php?id=<?= $row['Product_ID'] ?>">編輯</a></td>
                <td><a href="Del_Product.php?id=<?= $row['Product_ID'] ?>" onclick="return confirm('確定刪除？')">刪除</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div id="page">
        <?php
        $range = 2;
        if ($num_pages > ($range + 1)) {
            echo "<a href='Product.php?page=1" . keepURL() . "'>1</a> ";
            if ($num_pages > ($range + 2)) echo "... ";
        }
        for ($i = max(1, $num_pages - $range); $i <= min($num_pages + $range, $total_pages); $i++) {
            if ($i == $num_pages) {
                echo "<span class='current'>{$i}</span> ";
            } else {
                echo "<a href='Product.php?page={$i}" . keepURL() . "'>{$i}</a> ";
            }
        }
        if ($num_pages < ($total_pages - $range)) {
            if ($num_pages < ($total_pages - $range - 1)) echo "... ";
            echo "<a href='Product.php?page={$total_pages}" . keepURL() . "'>{$total_pages}</a> ";
        }
        ?>
    </div>
    <script src="JS/leftside.js"></script>
</main>
</body>
</html>
