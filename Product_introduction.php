<?php
include("sql_php.php");

// 確認是否已建立資料庫連線
if (!$link) {
    die("資料庫連線失敗：" . mysqli_connect_error());
}

// 定義基礎查詢語句
$sqli_query = "SELECT * FROM `product`";
$params = [];
$types = "";

// 根據條件設定查詢語句
if (isset($_GET["cid"]) && $_GET["cid"] != "") {
    $sqli_query .= " WHERE `商品ID` = ? ORDER BY `商品ID` DESC";
    $params[] = $_GET["cid"];
    $types .= "i"; // 數字類型
} elseif (isset($_GET["keyword"]) && $_GET["keyword"] != "") {
    $sqli_query .= " WHERE `商品名稱` LIKE ? OR `商品ID` LIKE ? ORDER BY `商品ID` DESC";
    $keyword = "%" . $_GET["keyword"] . "%";
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "ss"; // 字串類型
} else {
    $sqli_query .= " ORDER BY `商品ID` DESC";
}

// 預處理語句
$stmt = mysqli_prepare($link, $sqli_query);
if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

function keepURL() {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="CSS/form.css" rel="stylesheet" type="text/css">
    <title>商品介紹</title>
</head>
<body>
<main>
    <p><b><font size="5">商品介紹</font></b></p>
    <?php
    $rows = mysqli_num_rows($result);
    if ($rows == 0) {
        echo "查無資料";
    } else {
        echo "目前有 " . $rows . " 筆資料";
    }
    ?>
    <form method="get" action="">
        <select name="num">
            <option value="1">1</option>
            <option value="2" selected>2</option>
        </select>
        <input name="keyword" type="text" id="keyword" value="請輸入關鍵字" size="12" onclick="this.value='';">
        <input type="submit" name="import" id="button0" value="查詢">
    </form>
    <table border="1">
    <!-- 表格表頭 -->
    <tr>
        <th>圖片</th>
        <th>商品ID</th>
        <th>商品名稱</th>
    </tr>
    <!-- 資料內容 -->
    <?php
    while ($row_result = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td><img src='" . htmlspecialchars($row_result["圖片"]) . "' alt='商品圖片'></td>";
        echo "<td>" . htmlspecialchars($row_result["商品ID"]) . "</td>";
        echo "<td>" . htmlspecialchars($row_result["商品名稱"]) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td colspan='3'>商品簡介：" . htmlspecialchars($row_result["商品簡介"]) . "</td>";
        echo "</tr>";
    }
    ?>
</table>
</main>
</body>
</html>
