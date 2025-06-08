<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include("sql_php.php"); // 你的資料庫連線檔

// 取得使用者角色與 Seller_ID
$role = $_SESSION['role'] ?? '';
$sellerID = $_SESSION['Seller_ID'] ?? null;

// 取得當前月份，格式: YYYY-MM
$currentMonth = date('Y-m');

// 最近6個月
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i month"));
}

// 選擇的月份（預設本月）
$selectedMonth = $_GET['month'] ?? $currentMonth;
if (!in_array($selectedMonth, $months)) {
    $selectedMonth = $currentMonth;
}

// 商品類別（固定）
$allTypes = ['家具','家電', '衣物','3C', '書','玩具','運動用品','其他'];

// 賣家條件：seller只能看自己資料
$sellerCondition = '';
if ($role === 'seller' && $sellerID !== null) {
    $escapedSellerID = $link->real_escape_string($sellerID);
    $sellerCondition = "AND p.Seller_ID = '$escapedSellerID'";
}

// 撈每個類別銷售數量（只算已付款未取消）
$sql = "
    SELECT 
        p.Type,
        SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN product p ON oi.product_id = p.Product_ID
    JOIN ordershop o ON oi.order_id = o.id
    WHERE o.Order_status NOT IN ('取消', '未付款')
      AND DATE_FORMAT(o.Order_Date, '%Y-%m') = '{$link->real_escape_string($selectedMonth)}'
      $sellerCondition
    GROUP BY p.Type
    ORDER BY p.Type
";

$result = $link->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['Type']] = (int)$row['total_sold'];
}

// 確保每個類別都有欄位，即使沒銷售也顯示0
foreach ($allTypes as $type) {
    if (!isset($data[$type])) {
        $data[$type] = 0;
    }
}

// 取得詳細類別查詢參數，點分類名稱會帶入
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
<meta charset="UTF-8">
<title>銷售報表</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="CSS/original_table.css"> <!-- 換成你原本的CSS檔案 -->
<style>
  #salesChart {
    max-width: 700px;
    max-height: 300px;
  }
  .clickable {
    cursor: pointer;
    color: blue;
    text-decoration: underline;
  }
</style>
</head>
<body>
<main>
    <h2>銷售報表（<?php echo htmlspecialchars($selectedMonth); ?>）</h2>

    <!-- 篩選月份 -->
    <form method="get" id="filterForm" action="monthly_report.php">
        <label for="month">月份：
            <select name="month" id="month" onchange="this.form.submit()">
                <?php foreach ($months as $m): ?>
                <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <!-- 銷售數量條形圖 -->
    <canvas id="salesChart"></canvas>

    <!-- 商品類別銷售量表 -->
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
                    <a href="?month=<?= htmlspecialchars($selectedMonth) ?>&detail_type=<?= urlencode($type) ?>" class="clickable">
                        <?= htmlspecialchars($type) ?>
                    </a>
                </td>
                <td><?= $qty ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 詳細商品銷售明細 -->
    <?php if ($detailedType): ?>
    <h3>「<?= htmlspecialchars($detailedType) ?>」類別詳細銷售量</h3>
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
            backgroundColor: '#3498db',
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
