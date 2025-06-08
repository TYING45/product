<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include("sql_php.php");

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
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
if (!in_array($selectedMonth, $months)) {
    $selectedMonth = $currentMonth;
}

// 商品類別清單
$allTypes = ['家具','家電', '衣物','3C', '書','玩具','運動用品','其他'];
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

// SQL 條件建構
$typeCondition = '';
if ($typeFilter && in_array($typeFilter, $allTypes)) {
    $typeCondition = "AND p.Type = '" . $link->real_escape_string($typeFilter) . "'";
}

// 賣家條件：僅限 seller 看自己的資料
$sellerCondition = '';
if ($role === 'seller' && $sellerID !== null) {
    $escapedSellerID = $link->real_escape_string($sellerID);
    $sellerCondition = "AND p.Seller_ID = '$escapedSellerID'";
}

// 撈分類統計資料（銷售數量 & 金額）
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

// 撈產品明細（分類底下所有產品銷售量、金額）
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

// 初始化資料結構
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

// 分類銷售數量與金額
while ($row = $result->fetch_assoc()) {
    $cleanType = trim($row['Type']);
    $data[$cleanType] = (int)$row['total_sold'];
    $amountData[$cleanType] = round((float)$row['total_amount'], 2);
}

// 產品明細陣列
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
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    .type-row { cursor: pointer; background-color: #f8f8f8; }
    .detail-row { background-color: #fdfdfd; }
    .sub-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .sub-table th, .sub-table td { border: 1px solid #ccc; padding: 6px; }
    form label { margin-right: 10px; }
    form select { margin-right: 20px; }
    .back-btn { display: inline-block; margin-top: 20px; padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 4px;}
    .back-btn:hover { background: #2980b9; }
</style>
</head>
<body>
<main>
    <h2>銷售報表（<?php echo htmlspecialchars($selectedMonth); ?>）</h2>

    <form id="filterForm" method="get" action="monthly_report.php">
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

    <canvas id="salesChart" style="max-width: 700px; margin-top: 30px;"></canvas>

    <table>
        <thead>
            <tr>
                <th>月份</th>
                <th>商品類別（點擊展開明細）</th>
                <th>銷售數量</th>
                <th>銷售金額（元）</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $type => $quantity): 
                $detailId = md5($type);
            ?>
                <tr class="type-row" onclick="toggleDetail('<?php echo $detailId; ?>')">
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

    <a href="<?php echo ($role === 'admin') ? 'index.php' : 'Seller_index.php'; ?>" class="back-btn">回首頁</a>

<script>
function toggleDetail(id) {
    const row = document.getElementById('detail-' + id);
    if (row) {
        row.style.display = (row.style.display === 'none') ? '' : 'none';
    }
}

// Chart.js 條形圖 - 顯示各分類銷售數量
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php echo '"' . htmlspecialchars($selectedMonth) . '"'; ?>],
        datasets: <?php
            $datasets = [];
            foreach ($data as $type => $value) {
                $cleanType = trim($type);
                $color = match($cleanType) {
                    '家具' => '#FFFF00',
                    '家電' => '#00FF00',
                    '衣物' => '#FF8800',
                    '3C' => '#3498db',
                    '書' => '#2ecc71',
                    '玩具' => '#33FFFF',
                    '運動用品' => '#66FF66',
                    default => '#aaa'
                };
                $datasets[] = [
                    'label' => $cleanType,
                    'data' => [(int)$value],
                    'backgroundColor' => $color
                ];
            }
            echo json_encode($datasets, JSON_UNESCAPED_UNICODE);
        ?>,
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: {
                display: true,
                text: '分類銷售數量條形圖'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                precision: 0,
                ticks: { stepSize: 1 }
            },
            x: {
                stacked: false,
                ticks: { font: { size: 14 } }
            }
        },
        categoryPercentage: 0.6,
        barPercentage: 0.8
    }
});
</script>
</main>
</body>
</html>
