<?php
session_start();
include("sql_php.php");

$seller_id = $_SESSION['Seller_ID'] ?? null;
if (!$seller_id) {
    echo "請先登入賣家帳號";
    exit;
}

$search_keyword = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$order_status = $_GET['order_status'] ?? '';

$page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// 計算總筆數
$sql_count = "
    SELECT COUNT(DISTINCT o.id) AS total 
    FROM ordershop o
    INNER JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.Seller_ID = ?
";

$params = [$seller_id];
$types = "s";

if ($search_keyword !== '') {
    $sql_count .= " AND (o.Order_ID LIKE CONCAT('%', ?, '%') OR o.Member_ID IN (SELECT Member_ID FROM member WHERE Member_Name LIKE CONCAT('%', ?, '%'))) ";
    $params[] = $search_keyword;
    $params[] = $search_keyword;
    $types .= "ss";
}
if ($year !== '') {
    $sql_count .= " AND YEAR(o.Order_Date) = ? ";
    $params[] = $year;
    $types .= "s";
}
if ($month !== '') {
    $sql_count .= " AND MONTH(o.Order_Date) = ? ";
    $params[] = $month;
    $types .= "s";
}
if ($payment_status !== '') {
    $sql_count .= " AND o.Payment_status = ? ";
    $params[] = $payment_status;
    $types .= "s";
}
if ($order_status !== '') {
    $sql_count .= " AND o.Order_status = ? ";
    $params[] = $order_status;
    $types .= "s";
}

$stmt = $link->prepare($sql_count);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$total_rows = $result->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_rows / $items_per_page);

// 主查詢：取出訂單 + 該賣家商品總額
$sql = "
    SELECT DISTINCT o.*, 
        (SELECT SUM(oi.quantity * oi.price)
         FROM order_items oi 
         WHERE oi.order_id = o.id AND oi.Seller_ID = ?) AS seller_total
    FROM ordershop o
    INNER JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.Seller_ID = ?
";

$params = [$seller_id, $seller_id]; // 用在子查詢與主查詢
$types = "ss";

if ($search_keyword !== '') {
    $sql .= " AND (o.Order_ID LIKE CONCAT('%', ?, '%') OR o.Member_ID IN (SELECT Member_ID FROM member WHERE Member_Name LIKE CONCAT('%', ?, '%'))) ";
    $params[] = $search_keyword;
    $params[] = $search_keyword;
    $types .= "ss";
}
if ($year !== '') {
    $sql .= " AND YEAR(o.Order_Date) = ? ";
    $params[] = $year;
    $types .= "s";
}
if ($month !== '') {
    $sql .= " AND MONTH(o.Order_Date) = ? ";
    $params[] = $month;
    $types .= "s";
}
if ($payment_status !== '') {
    $sql .= " AND o.Payment_status = ? ";
    $params[] = $payment_status;
    $types .= "s";
}
if ($order_status !== '') {
    $sql .= " AND o.Order_status = ? ";
    $params[] = $order_status;
    $types .= "s";
}

$sql .= " ORDER BY o.Order_Date DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $link->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8" />
    <title>賣家訂單管理</title>
    <link rel="stylesheet" href="CSS/leftside.css" />
    <link rel="stylesheet" href="CSS/topmenu.css" />
    <link href="CSS/form.css" rel="stylesheet" />
    <link href="CSS/UOrder.css" rel="stylesheet" />
</head>
<body>

<div id="top-menu">
    <ul class="topmenu">
        <button onclick="toggleSidebar()" class="img-button"></button> <li></li><li></li>
        <li><a href="https://secondhandshop.netlify.app/">網頁前端</a></li>
        <li><a href="Seller_data.php">賣家資料</a></li>
        <li><a href="logout.php">登出</a></li>
    </ul>
</div>

<div id="leftside">
    <ul class="menuleft">
        <li><a href="Seller_index.php">首頁</a></li>
        <li>
            <a href="#" onclick="toggleMenu(event)">商品管理</a>
            <ul class="menuleft_hide">
                <li><a href="Add_Product.php">新增商品</a></li>
                <li><a href="Seller_Product.php">商品管理</a></li>
            </ul>
        </li>
        <li>
            <a href="#" onclick="toggleMenu(event)">訂單管理</a>
            <ul class="menuleft_hide">
                <li><a href="Seller_Order.php">訂單管理</a></li>
            </ul>
        </li>
    </ul>
</div>

<main class="main">
    <h2>賣家訂單管理</h2>

    <form method="GET" action="Seller_Order.php">
        <input name="search" type="text" placeholder="訂單編號或會員姓名" size="20"
               value="<?= htmlspecialchars($search_keyword) ?>">
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
                $monthVal = str_pad($m, 2, '0', STR_PAD_LEFT);
                $selected = ($month == $monthVal) ? 'selected' : '';
                echo "<option value='$monthVal' $selected>$m 月</option>";
            }
            ?>
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

    <table border="1" cellpadding="5" cellspacing="0" style="margin-top: 1rem; width: 100%;">
        <thead>
        <tr>
            <th>訂單編號</th>
            <th>訂購日期</th>
            <th>付款狀態</th>
            <th>訂單狀態</th>
            <th>賣家商品總額</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="6" style="text-align:center;">沒有資料</td></tr>
        <?php else: ?>
            <?php while ($order = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($order['Order_ID']) ?></td>
                    <td><?= htmlspecialchars($order['Order_Date']) ?></td>
                    <td><?= htmlspecialchars($row['Payment_status'] ?? '') ?></td>
                    <td><?= htmlspecialchars($order['Order_status'] ?? '') ?></td>
                    <td><?= htmlspecialchars(number_format($order['seller_total'], 2)) ?></td>
                    <td><a href="UpdateSeller_Order.php?Order_ID=<?= urlencode($order['Order_ID']) ?>">查看</a></td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        <?php if ($total_pages > 1): ?>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php if ($p == $page): ?>
                    <strong><?= $p ?></strong>
                <?php else: ?>
                    <a href="?page=<?= $p ?>&search=<?= urlencode($search_keyword) ?>&year=<?= urlencode($year) ?>&month=<?= urlencode($month) ?>&payment_status=<?= urlencode($payment_status) ?>&order_status=<?= urlencode($order_status) ?>"><?= $p ?></a>
                <?php endif; ?>
                &nbsp;
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</main>

<script src="JS/leftside.js"></script>

</body>
</html>
