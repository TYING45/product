<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include("sql_php.php");

$role = $_SESSION['role'] ?? '';
$sellerID = $_SESSION['Seller_ID'] ?? null;

$currentMonth = date('Y-m');
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i month"));
}

$selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
if (!in_array($selectedMonth, $months)) {
    $selectedMonth = $currentMonth;
}

$allTypes = ['家具','家電', '衣物','3C', '書','玩具','運動用品','其他'];
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

$typeCondition = '';
if ($typeFilter && in_array($typeFilter, $allTypes)) {
    $typeCondition = "AND p.Type = '" . $link->real_escape_string($typeFilter) . "'";
}

$sellerCondition = '';
if ($role === 'seller' && $sellerID !== null) {
    $escapedSellerID = $link->real_escape_string($sellerID);
    $sellerCondition = "AND p.Seller_ID = '$escapedSellerID'";
}

$sql = "
    SELECT 
        DATE_FORMAT(o.Order_Date, '%Y-%m') AS month,
        p.Type,
        SUM(oi.quantity) AS total_sold,
        SUM(oi.quantity * p.Price) AS total_amount
    FROM order_items oi
    JOIN product p ON oi.product_id = p.Product_ID
    JOIN ordershop o ON oi.order_id = o.id
    WHERE o.Order_status NOT IN ('取消', '未付款')
      AND DATE_FORMAT(o.Order_Date, '%Y-%m') = '$selectedMonth'
      $typeCondition
      $sellerCondition
    GROUP BY month, p.Type
    ORDER BY p.Type
";

$result = $link->query($sql);

$sqlDetail = "
    SELECT 
        p.Type,
        p.Product_Name,
        SUM(oi.quantity) AS total_sold,
        SUM(oi.quantity * p.Price) AS total_amount
    FROM order_items oi
    JOIN product p ON oi.product_id = p.Product_ID
    JOIN ordershop o ON oi.order_id = o.id
    WHERE o.Order_status NOT IN ('取消', '未付款')
      AND DATE_FORMAT(o.Order_Date, '%Y-%m') = '$selectedMonth'
      $typeCondition
      $sellerCondition
    GROUP BY p.Type, p.Product_Name
    ORDER BY p.Type, p.Product_Name
";

$resultDetail = $link->query($sqlDetail);

$data = [];
$amountData = [];
if ($typeFilter && in_array($typeFilter, $allTypes)) {
    $data[$typeFilter] = 0;
    $amountData[$typeFilter] = 0;
} else {
    foreach ($allTypes as $t) {
        $data[$t] = 0;
        $amountData[$t] = 0;
    }
}

while ($row = $result->fetch_assoc()) {
    $cleanType = trim($row['Type']);
    $data[$cleanType] = (int)$row['total_sold'];
    $amountData[$cleanType] = round((float)$row['total_amount'], 2);
}

$productDetails = [];
while ($row = $resultDetail->fetch_assoc()) {
    $type = trim($row['Type']);
    $productDetails[$type][] = [
        'Product_Name' => $row['Product_Name'],
        'total_sold' => (int)$row['total_sold'],
        'total_amount' => round((float)$row['total_amount'], 2)
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8" />
<title>銷售報表 - 美化版</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    /* 基礎 Reset & 字體 */
    * {
        box-sizing: border-box;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f0f2f5;
        margin: 0; padding: 20px;
        color: #333;
    }

    main {
        max-width: 1100px;
        margin: 0 auto;
        background: white;
        padding: 25px 40px 40px 40px;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1);
        user-select: none;
    }

    h2 {
        text-align: center;
        margin-bottom: 30px;
        font-weight: 700;
        color: #2c3e50;
        letter-spacing: 1.2px;
    }

    form {
        display: flex;
        justify-content: center;
        gap: 25px;
        margin-bottom: 30px;
    }
    form label {
        font-weight: 600;
        color: #34495e;
        align-self: center;
        min-width: 90px;
        user-select: text;
    }
    form select {
        padding: 8px 14px;
        font-size: 15px;
        border-radius: 8px;
        border: 1.5px solid #bdc3c7;
        transition: border-color 0.3s ease;
        min-width: 150px;
        cursor: pointer;
        background-color: #fff;
    }
    form select:hover, form select:focus {
        border-color: #2980b9;
        outline: none;
        box-shadow: 0 0 6px #2980b9a0;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
        font-size: 15px;
    }
    thead tr {
        background: #2980b9;
        color: white;
        font-weight: 700;
        border-radius: 12px;
    }
    thead th {
        padding: 14px 20px;
        user-select: none;
    }
    tbody tr.type-row {
        background: linear-gradient(90deg, #6dd5fa, #2980b9);
        color: white;
        font-weight: 600;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.35s ease;
        box-shadow: 0 3px 8px rgb(41 128 185 / 0.3);
    }
    tbody tr.type-row:hover {
        background: linear-gradient(90deg, #2980b9, #6dd5fa);
        box-shadow: 0 6px 15px rgb(41 128 185 / 0.5);
    }

    tbody tr.detail-row {
        background: #f7f9fc;
        box-shadow: inset 0 0 6px #d7e2f0;
        border-radius: 10px;
    }

    tbody tr.detail-row td {
        padding: 0;
        border: none;
    }

    .sub-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 6px;
    }
    .sub-table thead tr {
        background-color: #3498db;
        color: white;
        font-weight: 600;
    }
    .sub-table th, .sub-table td {
        padding: 10px 14px;
        text-align: left;
    }
    .sub-table tbody tr {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgb(0 0 0 / 0.05);
        transition: background 0.25s ease;
    }
    .sub-table tbody tr:hover {
        background: #eaf4fb;
    }

    .back-btn {
        display: inline-block;
        margin-top: 35px;
        padding: 12px 30px;
        background: #2980b9;
        color: white;
        font-weight: 600;
        font-size: 16px;
        text-decoration: none;
        border-radius: 8px;
        box-shadow: 0 8px 15px rgba(41,128,185,0.3);
        transition: background 0.3s ease, box-shadow 0.3s ease;
        user-select: none;
    }
    .back-btn:hover {
        background: #1c5987;
        box-shadow: 0 12px 20px rgba(28,89,135,0.6);
    }

    /* Chart 容器美化 */
    #salesChart {
        background: white;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 15px 25px rgba(41, 128, 185, 0.1);
        max-width: 720px;
        margin: 0 auto 40px auto;
        user-select: none;
    }

</style>
</head>
<body>
<main>
    <h2>銷售報表（<?php echo htmlspecialchars($selectedMonth); ?>）</h2>

    <form id="filterForm" method="get" action="monthly_report.php" aria-label="篩選條件">
        <label for="month">選擇月份：</label>
        <select id="month" name="month" onchange="document.getElementById('filterForm').submit()">
            <?php foreach ($months as $m): ?>
                <option value="<?php echo $m; ?>" <?php if ($m === $selectedMonth) echo 'selected'; ?>>
                    <?php echo $m; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <canvas id="salesChart" aria-label="分類銷售數量條形圖" role="img"></canvas>

    <table aria-describedby="salesTableDesc">
        <caption id="salesTableDesc" style="caption-side: bottom; text-align: center; font-style: italic; padding: 10px 0;">
            點擊分類列展開該分類下各產品銷售明細
        </caption>
        <thead>
            <tr>
                <th scope="col">月份</th>
                <th scope="col">商品類別（點擊展開明細）</th>
                <th scope="col">銷售數量</th>
                <th scope="col">銷售金額（元）</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $type => $quantity): 
                $detailId = md5($type);
            ?>
                <tr class="type-row" tabindex="0" role="button" aria-expanded="false" aria-controls="detail-<?php echo $detailId; ?>" 
                    onclick="toggleDetail('<?php echo $detailId; ?>')" onkeypress="if(event.key==='Enter') toggleDetail('<?php echo $detailId; ?>')">
                    <td><?php echo htmlspecialchars($selectedMonth); ?></td>
                    <td><?php echo htmlspecialchars($type); ?></td>
                    <td><?php echo (int)$quantity; ?></td>
                    <td><?php echo number_format($amountData[$type], 2); ?></td>
                </tr>

                <?php if (!empty($productDetails[$type])): ?>
                    <tr class="detail-row" id="detail-<?php echo $detailId; ?>" style="display: none;">
                        <td colspan="4">
                            <table class="sub-table">
                                <thead>
                                    <tr>
                                        <th>產品名稱</th>
                                        <th>銷售數量</th>
                                        <th>銷售金額（元）</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productDetails[$type] as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['Product_Name']); ?></td>
                                            <td><?php echo (int)$product['total_sold']; ?></td>
                                            <td><?php echo number_format($product['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="<?php echo ($role === 'admin') ? 'index.php' : 'Seller_index.php'; ?>" class="back-btn" role="link" aria-label="回首頁">回首頁</a>

<script>
function toggleDetail(id) {
    const row = document.getElementById('detail-' + id);
    if (!row) return;

    const isHidden = row.style.display === 'none';
    row.style.display = isHidden ? '' : 'none';

    // 更新 aria-expanded 狀態
    const triggerRow = row.previousElementSibling;
    if (triggerRow) {
        triggerRow.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    }
}

const ctx = document.getElementById('salesChart').getContext('2d');

const colors = {
    '家具': '#F1C40F',
    '家電': '#2ECC71',
    '衣物': '#E67E22',
    '3C': '#3498DB',
    '書': '#9B59B6',
    '玩具': '#1ABC9C',
    '運動用品': '#27AE60',
    '其他': '#95A5A6'
};

const dataLabels = Object.keys(<?php echo json_encode($data, JSON_UNESCAPED_UNICODE); ?>);
const dataValues = Object.values(<?php echo json_encode($data, JSON_UNESCAPED_UNICODE); ?>);
const dataColors = dataLabels.map(label => color
