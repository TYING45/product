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
        SUM(oi.quantity * oi.price) AS total_amount
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

$data = [];
if ($typeFilter && in_array($typeFilter, $allTypes)) {
    $data[$typeFilter] = ['total_sold' => 0, 'total_amount' => 0];
} else {
    foreach ($allTypes as $t) {
        $data[$t] = ['total_sold' => 0, 'total_amount' => 0];
    }
}

while ($row = $result->fetch_assoc()) {
    $cleanType = trim($row['Type']);
    $data[$cleanType] = [
        'total_sold' => (int)$row['total_sold'],
        'total_amount' => (float)$row['total_amount'],
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<title>銷售報表</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body {
        font-family: "微軟正黑體", sans-serif;
        max-width: 900px;
        margin: 30px auto;
        padding: 10px;
        background: #fafafa;
        color: #333;
    }
    h2 {
        text-align: center;
        margin-bottom: 25px;
    }
    form {
        margin-bottom: 20px;
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }
    label {
        font-weight: 600;
    }
    select {
        padding: 6px 10px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 4px;
        min-width: 130px;
    }
    /* 原本的簡單表格CSS */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    th, td {
        border: 1px solid #999;
        padding: 8px;
        text-align: center;
    }
    th {
        background-color: #eee;
        font-weight: bold;
    }
    a.back-btn {
        display: inline-block;
        margin: 25px auto 0 auto;
        padding: 10px 25px;
        background-color: #3498db;
        color: #fff;
        text-decoration: none;
        border-radius: 5px;
        text-align: center;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    a.back-btn:hover {
        background-color: #217dbb;
    }
    #salesChart {
        max-width: 100%;
        height: 250px;  /* 縮小高度 */
        margin: 0 auto 30px auto;
        display: block;
    }
</style>
</head>
<body>
<main>
    <h2>銷售報表（<?php echo htmlspecialchars($selectedMonth); ?>）</h2>

    <form id="filterForm" method="get" action="monthly_report.php">
        <label for="month">選擇月份：</label>
        <select id="month" name="month" onchange="this.form.submit()">
            <?php foreach ($months as $m): ?>
                <option value="<?php echo $m; ?>" <?php if ($m === $selectedMonth) echo 'selected'; ?>>
                    <?php echo $m; ?>
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
                <th>銷售金額 (元)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $type => $info): ?>
                <tr>
                    <td><?php echo htmlspecialchars($selectedMonth); ?></td>
                    <td><?php echo htmlspecialchars($type); ?></td>
                    <td><?php echo (int)$info['total_sold']; ?></td>
                    <td><?php echo number_format($info['total_amount'], 0); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="text-align:center;">
        <a href="<?php echo ($role === 'admin') ? 'index.php' : 'Seller_index.php'; ?>" class="back-btn">回首頁</a>
    </div>

    <script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode([$selectedMonth]); ?>,
            datasets: <?php
                $datasets = [];
                foreach ($data as $type => $info) {
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
                        'data' => [(int)$info['total_sold']],
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
