<?php
session_start();
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: login.php");
    exit();
}

include("sql_php.php");

$sellerID = $_SESSION['Seller_ID'] ?? null;
if (!$sellerID) {
    echo "賣家ID不存在，無法查詢資料。";
    exit();
}

$currentMonth = date('Y-m');
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i month"));
}

$selectedMonth = $_GET['month'] ?? $currentMonth;
if (!in_array($selectedMonth, $months)) {
    $selectedMonth = $currentMonth;
}

$allTypes = ['家具','家電', '衣物','3C', '書','玩具','運動用品','其他'];
$typeFilter = $_GET['type'] ?? '';

$typeCondition = '';
if ($typeFilter && in_array($typeFilter, $allTypes)) {
    $typeCondition = "AND p.Type = '" . $link->real_escape_string($typeFilter) . "'";
}

$sellerCondition = "AND p.Seller_ID = '" . $link->real_escape_string($sellerID) . "'";

// 撈類別總計
$sql = "
    SELECT p.Type,
           SUM(oi.quantity) AS total_sold,
           SUM(oi.quantity * oi.price) AS total_amount
    FROM order_items oi
    JOIN product p ON oi.product_id = p.Product_ID
    JOIN ordershop o ON oi.order_id = o.id
    WHERE o.Order_status NOT IN ('取消', '未付款')
      AND DATE_FORMAT(o.Order_Date, '%Y-%m') = '$selectedMonth'
      $typeCondition
      $sellerCondition
    GROUP BY p.Type
    ORDER BY p.Type
";

$result = $link->query($sql);

$data = [];
foreach ($allTypes as $t) {
    $data[$t] = ['total_sold' => 0, 'total_amount' => 0];
}

while ($row = $result->fetch_assoc()) {
    $type = trim($row['Type']);
    $data[$type] = [
        'total_sold' => (int)$row['total_sold'],
        'total_amount' => (float)$row['total_amount'],
    ];
}

$productDetails = [];
if ($typeFilter && in_array($typeFilter, $allTypes)) {
    $sqlDetails = "
        SELECT p.Product_ID, p.Product_Name,
               SUM(oi.quantity) AS total_sold,
               SUM(oi.quantity * oi.price) AS total_amount
        FROM order_items oi
        JOIN product p ON oi.product_id = p.Product_ID
        JOIN ordershop o ON oi.order_id = o.id
        WHERE o.Order_status NOT IN ('取消', '未付款')
          AND DATE_FORMAT(o.Order_Date, '%Y-%m') = '$selectedMonth'
          AND p.Type = '" . $link->real_escape_string($typeFilter) . "'
          $sellerCondition
        GROUP BY p.Product_ID, p.Product_Name
        ORDER BY total_sold DESC
    ";
    $resDetails = $link->query($sqlDetails);
    while ($row = $resDetails->fetch_assoc()) {
        $productDetails[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<title>賣家銷售報表</title>
<link rel="stylesheet" href="CSS/report.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* 簡單版，表格跟按鈕 */
.back-btn {
    display: inline-block;
    margin: 20px 0;
    padding: 6px 14px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 3px;
    font-weight: 600;
}
.back-btn:hover {
    background-color: #0056b3;
}
table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 20px;
}
table th, table td {
    border: 1px solid #ccc;
    padding: 6px 10px;
    text-align: center;
}
table th {
    background-color: #f4f4f4;
}
a.type-link {
    color: #007bff;
    text-decoration: none;
}
a.type-link:hover {
    text-decoration: underline;
}
</style>
</head>
<body>
<main>
    <h2>賣家銷售報表（<?php echo htmlspecialchars($selectedMonth); ?>）</h2>
    <form id="filterForm" method="get" action="Seller_report.php">
        <label for="month">選擇月份：</label>
        <select id="month" name="month" onchange="document.getElementById('filterForm').submit()">
            <?php foreach ($months as $m): ?>
                <option value="<?php echo $m; ?>" <?php if ($m === $selectedMonth) echo 'selected'; ?>>
                    <?php echo $m; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="type">選擇商品類別：</label>
        <select id="type" name="type" onchange="document.getElementById('filterForm').submit()">
            <option value="" <?php if ($typeFilter === '') echo 'selected'; ?>>全部類別</option>
            <?php foreach ($allTypes as $t): ?>
                <option value="<?php echo $t; ?>" <?php if ($typeFilter === $t) echo 'selected'; ?>>
                    <?php echo $t; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <table>
        <thead>
            <tr>
                <th>商品類別</th>
                <th>銷售數量</th>
                <th>銷售金額 (元)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $type => $info): ?>
                <tr>
                    <td>
                        <?php if (!$typeFilter): ?>
                            <a href="?month=<?php echo $selectedMonth; ?>&type=<?php echo urlencode($type); ?>" class="type-link">
                                <?php echo htmlspecialchars($type); ?>
                            </a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($type); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int)$info['total_sold']; ?></td>
                    <td><?php echo number_format($info['total_amount'], 0); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($typeFilter && in_array($typeFilter, $allTypes)): ?>
        <h3>類別 "<?php echo htmlspecialchars($typeFilter); ?>" 產品銷售明細</h3>
        <table>
            <thead>
                <tr>
                    <th>產品ID</th>
                    <th>產品名稱</th>
                    <th>銷售數量</th>
                    <th>銷售金額 (元)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($productDetails) === 0): ?>
                    <tr><td colspan="4">無銷售資料</td></tr>
                <?php else: ?>
                    <?php foreach ($productDetails as $prod): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($prod['Product_ID']); ?></td>
                            <td><?php echo htmlspecialchars($prod['Product_Name']); ?></td>
                            <td><?php echo (int)$prod['total_sold']; ?></td>
                            <td><?php echo number_format($prod['total_amount'], 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="Seller_index.php" class="back-btn">回首頁</a>
</main>
</body>
</html>
