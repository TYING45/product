<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include("sql_php.php");

$search_keyword = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';
$seller_id = $_GET['seller_id'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$order_status = $_GET['order_status'] ?? '';

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];
$types = "";

// 搜尋關鍵字（訂單編號或會員姓名）
if ($search_keyword !== '') {
    $where .= " AND (`Order_ID` LIKE ? OR `Order_name` LIKE ?)";
    $kw = "%$search_keyword%";
    $params[] = $kw;
    $params[] = $kw;
    $types .= "ss";
}

// 年篩選
if ($year !== '') {
    $where .= " AND YEAR(Order_Date) = ?";
    $params[] = $year;
    $types .= "i";
}

// 月篩選
if ($month !== '') {
    $where .= " AND MONTH(Order_Date) = ?";
    $params[] = $month;
    $types .= "i";
}

// 賣家篩選
if ($seller_id !== '') {
    $where .= " AND Seller_ID = ?";
    $params[] = $seller_id;
    $types .= "i";
}

// 付款狀態篩選
if ($payment_status !== '') {
    $where .= " AND Payment_status = ?";
    $params[] = $payment_status;
    $types .= "s";
}

// 訂單狀態篩選
if ($order_status !== '') {
    $where .= " AND Order_status = ?";
    $params[] = $order_status;
    $types .= "s";
}

// 查詢總筆數
$count_sql = "SELECT COUNT(*) FROM ordershop $where";
$count_stmt = $link->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_rows);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_rows / $per_page);

// 查詢訂單資料
$sql = "SELECT * FROM ordershop $where ORDER BY Order_ID DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";


$stmt = $link->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 取得所有賣家名稱
$sellers = [];
$seller_result = $link->query("SELECT Seller_ID, Seller_name FROM seller");
while ($row = $seller_result->fetch_assoc()) {
    $sellers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>訂單管理（管理員）</title>
    <link rel="stylesheet" href="CSS/leftside.css">
    <link rel="stylesheet" href="CSS/topmenu.css">
    <link href="CSS/form.css" rel="stylesheet">
</head>
<body>
<div id="top-menu">
    <ul class="topmenu">
        <li><button onclick="toggleSidebar()" class="img-button"></button></li>
        <li></li><li></li>
        <li><a href="https://secondhandshop.netlify.app/">網易前端</a></li>
        <li><a href="logout.php">登出</a></li>
    </ul>   
</div>

<div id="leftside">
    <ul class="menuleft">
        <li><a href="index.php">首頁</a></li>
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
                <li><a href="Permissions.php">管理員管理</a></li>
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
        <li>
            <a href="#" onclick="toggleMenu(event)">訂單管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Order.php">訂單資料管理</a></li>
            </ul>
        </li>   
    </ul>
</div>

<main class="main">
    <h2>訂單管理（管理員）</h2>

    <form method="GET" action="Order.php">
        <input name="search" type="text" placeholder="訂單編號或會員姓名" value="<?= htmlspecialchars($search_keyword) ?>">

        <select name="year">
            <option value="">全部年</option>
            <?php
            $currentYear = date("Y");
            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                $selected = ($year == $y) ? 'selected' : '';
                echo "<option value='$y' $selected>$y 年</option>";
            }
            ?>
        </select>

        <select name="month">
            <option value="">全部月</option>
            <?php
            for ($m = 1; $m <= 12; $m++) {
                $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                $selected = ($month == $val) ? 'selected' : '';
                echo "<option value='$val' $selected>$m 月</option>";
            }
            ?>
        </select>

        <select name="seller_id">
            <option value="">全部賣家</option>
            <?php foreach ($sellers as $seller): ?>
                <option value="<?= $seller['Seller_ID'] ?>" <?= ($seller_id == $seller['Seller_ID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($seller['Seller_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="payment_status">
            <option value="">全部繳款狀態</option>
            <option value="尚未繳款" <?= $payment_status === '尚未繳款' ? 'selected' : '' ?>>尚未繳款</option>
            <option value="已繳款" <?= $payment_status === '已繳款' ? 'selected' : '' ?>>已繳款</option>
        </select>

        <select name="order_status">
            <option value="">全部訂單狀態</option>
            <option value="未處理" <?= $order_status === '未處理' ? 'selected' : '' ?>>未處理</option>
            <option value="訂單處理中" <?= $order_status === '訂單處理中' ? 'selected' : '' ?>>訂單處理中</option>
            <option value="商品寄出" <?= $order_status === '商品寄出' ? 'selected' : '' ?>>商品寄出</option>
            <option value="商品退貨" <?= $order_status === '商品退貨' ? 'selected' : '' ?>>商品退貨</option>
            <option value="交易取消" <?= $order_status === '交易取消' ? 'selected' : '' ?>>交易取消</option>
            <option value="結案" <?= $order_status === '結案' ? 'selected' : '' ?>>結案</option>
        </select>

        <input type="submit" value="查詢">
    </form>

    <table border="1" cellpadding="5" cellspacing="0" style="margin-top:1rem;width:100%;">
        <thead>
        <tr>
            <th>訂單編號</th>
            <th>訂購日期</th>
            <th>付款狀態</th>
            <th>訂單狀態</th>
            <th>總金額</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="6" style="text-align:center;">沒有資料</td></tr>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['Order_ID']) ?></td>
                    <td><?= htmlspecialchars($row['Order_Date']) ?></td>
                    <td><?= htmlspecialchars($row['Payment_status'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['Order_status'] ?? '未處理') ?></td>
                    <td><?= number_format(($row['total_price'] ?? 0) + ($row['shipping_fee'] ?? 0), 2) ?></td>
                    <td><a href="Update_Order.php?Order_ID=<?= urlencode($row['Order_ID']) ?>">查看</a></td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <?php if ($p == $page): ?>
                <strong><?= $p ?></strong>
            <?php else: ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search_keyword) ?>&year=<?= $year ?>&month=<?= $month ?>&seller_id=<?= $seller_id ?>&payment_status=<?= urlencode($payment_status) ?>&order_status=<?= urlencode($order_status) ?>"><?= $p ?></a>
            <?php endif; ?>
            &nbsp;
        <?php endfor; ?>
    </div>
</main>
<script src="JS/leftside.js"></script>
</body>
</html>
