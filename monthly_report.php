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
    $typeCondition = "AND p.Type = '" . $link->real_escape_string($typeFilter) . "'";
}

$sellerCondition = '';
if ($role === 'seller' && $sellerID !== null) {
    $escapedSellerID = $link->real_escape_string($sellerID);
    $sellerCondition = "AND p.Seller_ID = '$escapedSellerID'";
}

$sql = "
    SELECT 
        p.Type,
        SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN product p ON oi.product_id = p.Product_ID
    JOIN ordershop o ON oi.order_id = o.id
    WHERE o.Order_status NOT IN ('取消', '未付款')
      AND DATE_FORMAT(o.Order_Date, '%Y-%m') = '{$link->real_escape_string($selectedMonth)}'
      $typeCondition
      $sellerCondition
    GROUP BY p.Type
    ORDER BY p.Type
";

$result = $link->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['Type']] = (int)$row['total_sold'];
}
foreach ($allTypes as $type) {
    if (!isset($data[$type])) {
        $data[$type] = 0;
    }
}

$detailedType = $_GET['detail_type'] ?? '';
$detailedData = [];

if ($detailedType && in_array($detailedType, $allTypes)) {
    $escType = $link->real_escape_string($detailedType);
    $sqlDetail = "
        SELECT p.Product_Name, SUM(oi.quantity) AS qty_sold
        FROM order_items oi
        JOIN product p ON oi.product_id = p.Product_ID
        JOIN ordershop o ON oi.order_id = o.id
        WHERE p.Type = '$escType'
          AND o.Order_status NOT IN ('取消', '未付款')
          AND DATE_FORMAT(o.Order_Date, '%Y-%m') = '{$link->real_escape_string($selectedMonth)}'
          $sellerCondition
        GROUP BY p.Product_ID
        ORDER BY qty_sold DESC
    ";
    $resDetail = $link->query($sqlDetail);
    while ($row = $resDetail->fetch_assoc()) {
        $detailedData[] = $row;
    }
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
    color: #333;
  }
  h2, h3 {
    margin-bottom: 12px;
  }
  form {
    margin-bottom: 20px;
  }
  select {
    padding: 4px 8px;
    font-size: 14px;
  }
  table {
    border-collapse: collapse;
    width: 100%;
    max-width: 700px;
    margin-bottom: 20px;
  }
  th, td {
    border: 1px solid #ccc;
    padding: 8px 12px;
    text-align: left;
  }
  th {
    background: #eee;
  }
  a.clickable {
    color: #007bff;
    text-decoration: none;
  }
  a.clickable:hover {
    text-decoration: underline;
  }
  #salesChart {
    max-width: 700px;
    max-height: 280px;
    margin-bottom: 30px;
  }
  p > a {
    color: #007bff;
    text-decoration: none;
  }
  p > a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>
<main>
    <h2>銷售報表（<?php echo htmlspecialchars($selectedMonth); ?>）</h2>

    <form method="get" id="filterForm" action="monthly_report.php">
        <label for="month">月份：
            <select name="month" id="month" onchange="this.form.submit()">
                <?php foreach ($months as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label for="type">類別：
            <select name="type" id="type" onchange="this.form.submit()">
                <option value="" <?= $typeFilter === '' ? 'selected' : '' ?>>全部</option>
                <?php foreach ($allTypes as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= $typeFilter === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <canvas id="salesChart"></canvas>

    <table>
        <thead>
            <tr>
                <th>商品類別</th>
                <th>銷售數量</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $type => $qty): ?>
            <tr>
                <td>
                    <a href="?month=<?= urlencode($selectedMonth) ?>&type=<?= urlencode($type) ?>&detail_type=<?= urlencode($type) ?>" class="clickable">
                        <?= htmlspecialchars($type) ?>
                    </a>
                </td>
                <td><?= $qty ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($detailedType): ?>
        <h3><?= htmlspecialchars($detailedType) ?> 類別詳細銷售量</h3>
        <?php if ($detailedData): ?>
        <table>
            <thead>
                <tr><th>商品名稱</th><th>銷售數量</th></tr>
            </thead>
            <tbody>
            <?php foreach ($detailedData as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['Product_Name']) ?></td>
                    <td><?= (int)$item['qty_sold'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>該類別無銷售資料。</p>
        <?php endif; ?>
    <?php endif; ?>

    <p><a href="<?php echo ($role === 'admin') ? 'index.php' : 'Seller_index.php'; ?>">回首頁</a></p>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($data), JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: '銷售數量',
            data: <?php echo json_encode(array_values($data)); ?>,
            backgroundColor: '#007bff',
            barPercentage: 0.6,
            categoryPercentage: 0.7,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: '<?php echo htmlspecialchars($selectedMonth); ?> 銷售數量柱狀圖' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 },
                title: { display: true, text: '銷售數量' }
            },
            x: {
                ticks: { font: { size: 12 } }
            }
        }
    }
});
</script>
</main>
</body>
</html>
