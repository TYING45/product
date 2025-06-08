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

$selectedMonth = $_GET['month'] ?? $currentMonth;
if (!in_array($selectedMonth, $months)) {
    $selectedMonth = $currentMonth;
}

$allTypes = ['家具','家電', '衣物','3C', '書','玩具','運動用品','其他'];
$typeFilter = $_GET['type'] ?? '';

$typeCondition = '';
if ($typeFilter && in_array($typeFilter, $allTypes)) {
    $typeFilterEscaped = $link->real_escape_string($typeFilter);
    $typeCondition = "AND p.Type = '$typeFilterEscaped'";
}

$sellerCondition = '';
if ($role === 'seller' && $sellerID !== null) {
    $sellerIDEscaped = $link->real_escape_string($sellerID);
    $sellerCondition = "AND p.Seller_ID = '$sellerIDEscaped'";
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
    $type = trim($row['Type']);
    $data[$type] = (int)$row['total_sold'];
    $amountData[$type] = round((float)$row['total_amount'], 2);
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
<title>銷售報表</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #fff;
        color: #222;
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
    }
    form {
        text-align: center;
        margin-bottom: 20px;
    }
    select {
        padding: 6px 10px;
        margin: 0 8px;
        font-size: 14px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 14px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }
    th {
        background-color: #eee;
        font-weight: bold;
    }
    tr.type-row {
        background-color: #f9f9f9;
        cursor: pointer;
    }
    tr.detail-row {
        background-color: #fafafa;
        display: none;
    }
    .sub-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .sub-table th, .sub-table td {
        border: 1px solid #ccc;
        padding: 5px;
        text-align: left;
    }
</style>
</head>
<body>
<h2>銷售報表（<?php echo htmlspecialchars($selectedMonth); ?>）</h2>
<form method="get" action="monthly_report.php" aria-label="篩選條件">
    <label for="month">月份：</label>
    <select id="month" name="month" onchange="this.form.submit()">
        <?php foreach ($months as $m): ?>
            <option value="<?php echo $m; ?>" <?php if ($m === $selectedMonth) echo 'selected'; ?>>
                <?php echo $m; ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<canvas id="salesChart" style="max-width:700px; margin: 0 auto 30px auto; display:block;"></canvas>

<table aria-describedby="salesTableDesc">
    <caption id="salesTableDesc" style="caption-side: bottom; text-align: center; font-style: italic; padding: 8px 0;">
        點擊分類列可展開各產品銷售明細
    </caption>
    <thead>
        <tr>
            <th>月份</th>
            <th>類別（點擊展開）</th>
            <th>銷售數量</th>
            <th>銷售金額(元)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $type => $quantity):
            $detailId = md5($type);
        ?>
            <tr class="type-row" tabindex="0" role="button" aria-expanded="false" aria-controls="detail-<?php echo $detailId; ?>"
                onclick="toggleDetail('<?php echo $detailId; ?>')" onkeypress="if(event.key==='Enter') toggleDetail('<?php echo $detailId; ?>')">
                <td><?php echo htmlspecialchars($selectedMonth); ?></td>
                <td style="text-align:left; padding-left:10px;"><?php echo htmlspecialchars($type); ?></td>
                <td><?php echo (int)$quantity; ?></td>
                <td><?php echo number_format($amountData[$type], 2); ?></td>
            </tr>

            <?php if (!empty($productDetails[$type])): ?>
                <tr class="detail-row" id="detail-<?php echo $detailId; ?>">
                    <td colspan="4">
                        <table class="sub-table" aria-label="<?php echo htmlspecialchars($type); ?> 類別產品銷售明細">
                            <thead>
                                <tr>
                                    <th>產品名稱</th>
                                    <th>銷售數量</th>
                                    <th>銷售金額(元)</th>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const data = {
        labels: <?php echo json_encode(array_keys($data)); ?>,
        datasets: [{
            label: '銷售數量',
            data: <?php echo json_encode(array_values($data)); ?>,
            backgroundColor: '#3498db',
            borderRadius: 4,
            barPercentage: 0.5
        }]
    };
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    title: { display: true, text: '銷售數量' }
                },
                x: {
                    title: { display: true, text: '商品類別' }
                }
            }
        }
    };
    const salesChart = new Chart(
        document.getElementById('salesChart'),
        config
    );

    function toggleDetail(id) {
        const detailRow = document.getElementById('detail-' + id);
        if (!detailRow) return;
        const isHidden = detailRow.style.display === 'none' || detailRow.style.display === '';
        detailRow.style.display = isHidden ? 'table-row' : 'none';

        const typeRow = detailRow.previousElementSibling;
        if(typeRow) {
            typeRow.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        }
    }
</script>
</body>
</html>
