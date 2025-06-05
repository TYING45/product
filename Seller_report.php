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

// 商品類別,
$allTypes = ['家具','家電', '衣物','3C', '書','玩具','運動用品','其他'];
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

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

// 撈資料
$sql = "
    SELECT 
        DATE_FORMAT(o.Order_Date, '%Y-%m') AS month,
        p.Type,
        SUM(oi.quantity) AS total_sold
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

// 初始化資料，補0
$data = [];
if ($typeFilter && in_array($typeFilter, $allTypes)) {
    $data[$typeFilter] = 0;
} else {
    foreach ($allTypes as $t) {
        $data[$t] = 0;
    }
}

while ($row = $result->fetch_assoc()) {
    $cleanType = trim($row['Type']);
    $data[$cleanType] = (int)$row['total_sold'];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<title>銷售報表</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" type="text/css" href="CSS/report.css">
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

    <canvas id="salesChart"></canvas>

    <table>
        <thead>
            <tr>
                <th>月份</th>
                <th>商品類別</th>
                <th>銷售數量</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $type => $quantity): ?>
                <tr>
                    <td><?php echo htmlspecialchars($selectedMonth); ?></td>
                    <td><?php echo htmlspecialchars($type); ?></td>
                    <td><?php echo (int)$quantity; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="<?php echo ($role === 'admin') ? 'index.php' : 'Seller_index.php'; ?>" class="back-btn">回首頁</a>

    <script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode([$selectedMonth]); ?>,
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
            text: '銷售數量條形圖'
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            precision: 0,
            ticks: {
                stepSize: 1
            }
        },
        x: {
            stacked: false, // 非堆疊，改為並排
            ticks: {
                font: {
                    size: 14
                }
            }
        }
    },
    categoryPercentage: 0.6, // 類別區間間隔比例（0~1，越小越集中）
    barPercentage: 0.8       // 類別中每個條形的寬度比例（0~1）
}


    });
    </script>
</main>
</body>
</html>
