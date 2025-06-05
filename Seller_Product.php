<?php
session_start();
include("sql_php.php");

// 檢查是否登入且為賣家
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    echo "未授權訪問。請先登入為賣家帳號。";
    exit();
}

$Seller_ID = $_SESSION['Seller_ID'];

// 每頁顯示筆數
$pageRow_records = 10;
$num_pages = isset($_GET['page']) ? intval($_GET['page']) : 1;
$startRow_records = ($num_pages - 1) * $pageRow_records;

// 設定查詢條件（包含 Seller_ID 與搜尋）
$where = " WHERE Seller_ID = ?";
$params = [$Seller_ID];
$types = "s";  // Seller_ID 是字串

if (isset($_GET["keyword"]) && $_GET["keyword"] !== "") {
    $where .= " AND (`Product_name` LIKE ? OR `Product_ID` LIKE ? OR `Type` LIKE ?)";
    $keyword = "%" . $_GET["keyword"] . "%";
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "sss";
}

// 查詢總筆數
$total_query = "SELECT COUNT(*) FROM `product`" . $where;
$stmt = $link->prepare($total_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_records);
$stmt->fetch();
$stmt->close();

$total_pages = ceil($total_records / $pageRow_records);

// 查詢資料（分頁）
$data_query = "SELECT * FROM `product`" . $where . " ORDER BY `Product_ID` DESC LIMIT ?, ?";
$params[] = $startRow_records;
$params[] = $pageRow_records;
$types .= "ii";

$stmt = $link->prepare($data_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// URL 參數
function keepURL()
{
    $keepURL = "";
    if (isset($_GET["keyword"])) {
        $keepURL .= "&keyword=" . urlencode($_GET["keyword"]);
    }
    if (isset($_GET["cid"])) {
        $keepURL .= "&cid=" . $_GET["cid"];
    }
    return $keepURL;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>商品管理</title>
    <link rel="stylesheet" href="CSS/leftside.css">
    <link rel="stylesheet" href="CSS/topmenu.css">
    <link rel="stylesheet" href="CSS/form.css">
</head>
<body>
<div id="top-menu">
    <ul class="topmenu">
        <li><button onclick="toggleSidebar()" class="img-button"></button></li>
        <li></li>
        <li><a href="#">網頁前端</a></li>
        <li><a href="logout.php">登出</a></li>
    </ul>   
</div>
<!----------------------------------------->
<div id="leftside">
    <ul class="menuleft">
        <li><a href="Seller_index.php">首頁</a></li>
        <li>
            <a href="#" onclick="toggleMenu(event)">網站管理系統</a>
            <ul class="menuleft_hide"><li><a href="#">網站管理</a></li></ul>
        </li>
        <li>
            <a href="#" onclick="toggleMenu(event)">商品管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="SellerAdd_Product.php">新增商品</a></li>
                <li><a href="Seller_Product.php">商品管理</a></li>
            </ul>
        </li>
        <li>
            <a href="#" onclick="toggleMenu(event)">訂單管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Seller_Order.php">訂單資料管理</a></li>
                
            </ul>
        </li>   
    </ul>
</div>
<!---------------------------->
<main class="main">
    <p><b><font size="5">商品管理系統</font></b></p>
    <?php
    if ($total_records == 0) {
        echo "<p style='color: red;'>查無資料</p>";
    } else {
        echo "<p>目前共有 <strong>{$total_records}</strong> 筆資料</p>";
    }
    ?>
    <form method="GET" action="Seller_Product.php">
        <input name="keyword" type="text" placeholder="請輸入關鍵字(ID、商品名稱、商品種類)" 
            value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
        <input type="submit" value="查詢">
        <input type="button" onclick="location.href='CSVSeller_Product.php'" value="輸出CSV">
        <input type="button" onclick="location.href='Upload_Products.html'" value="上傳資料">
    </form>

    <table border="1">
        <tr>
            <th>圖片</th>
            <th>商品ID</th>
            <th>商品名稱</th>
            <th>商品種類</th>
            <th>價格</th>
            <th>庫存數量</th>
            <th>備註</th>
            <th>上架狀態</th>
            <th>詳細</th>
            <th>刪除</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><img src="uploads/<?php echo htmlspecialchars($row["Image"] ?: "default.png"); ?>" width="100"></td>
            <td><?php echo htmlspecialchars($row["Product_ID"]); ?></td>
            <td><?php echo htmlspecialchars($row["Product_name"]); ?></td>
            <td><?php echo htmlspecialchars($row["Type"]); ?></td>
            <td><?php echo htmlspecialchars($row["price"]); ?></td>
            <td><?php echo htmlspecialchars($row["quantity"]); ?></td>
            <td><?php echo htmlspecialchars($row["Remark"]); ?></td>
            <td>
                <?php
                    // 狀態文字對應
                    $statusTexts = ['0' => '已下架', '1' => '上架中', '2' => '缺貨'];
                    $currentStatus = $row["Shelf_status"];
                    $statusText = isset($statusTexts[$currentStatus]) ? $statusTexts[$currentStatus] : "未知狀態";

                    // 下一狀態循環 0->1->2->0
                    $nextStatus = ($currentStatus + 1) % 3;
                    $toggleText = isset($statusTexts[$nextStatus]) ? $statusTexts[$nextStatus] : "未知";

                    echo $statusText . " ";
                ?>
                <a href="Seller_ToggleStatus.php?id=<?= urlencode($row["Product_ID"]) ?>"
                   onclick="return confirm('確定要切換為【<?= $toggleText ?>】嗎？');">
                   切換為 <?= $toggleText ?>
                </a>
            </td>
            <td><a href="Update_SellerProduct.php?id=<?php echo urlencode($row["Product_ID"]); ?>">編輯</a></td>
            <td><a href="Del_SellerProduct.php?id=<?php echo urlencode($row["Product_ID"]); ?>" onclick="return confirm('確定刪除？')">刪除</a></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- 分頁 -->
    <div id="page">
        <?php
        $range = 2;
        if ($num_pages > ($range + 1)) {
            echo "<a href='Seller_Product.php?page=1" . keepURL() . "'>1</a> ";
            if ($num_pages > ($range + 2)) {
                echo "... ";
            }
        }
        for ($i = max(1, $num_pages - $range); $i <= min($total_pages, $num_pages + $range); $i++) {
            if ($i == $num_pages) {
                echo "<span style='font-weight:bold; color:red;'>$i</span> ";
            } else {
                echo "<a href='Seller_Product.php?page={$i}" . keepURL() . "'>$i</a> ";
            }
        }
        if ($num_pages < ($total_pages - $range)) {
            if ($num_pages < ($total_pages - $range - 1)) {
                echo "... ";
            }
            echo "<a href='Seller_Product.php?page={$total_pages}" . keepURL() . "'>$total_pages</a>";
        }
        ?>
    </div>
</main>

<script src="JS/leftside.js"></script>
</body>
</html>
