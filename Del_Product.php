<?php
include("sql_php.php");

if (isset($_POST["action"]) && ($_POST["action"] == "delete")) {
    $sqli_query = "DELETE FROM `product` WHERE Product_ID = ?";
    $stmt = $link->prepare($sqli_query);

    // 綁定參數並執行刪除操作
    $stmt->bind_param("s", $_POST["Product_ID"]);
    if ($stmt->execute()) {
        // 刪除成功後重定向到 data.php
        header("Location: Product.php");
        exit();
    } else {
        echo "刪除失敗：" . $stmt->error;
    }
}

// 從資料庫獲取要刪除的商品資料
if (isset($_GET["id"])) {
    $sql_select = "SELECT Product_ID, Product_name, price FROM `product` WHERE `Product_ID` = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $_GET["id"]);
    $stmt->execute();
    $stmt->bind_result($Product_ID, $Product_name, $price);
    $stmt->fetch();
} else {
    echo "未提供商品編號！";
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <link href="CSS/Add_Del.css" rel="stylesheet" type="text/css">
    <meta charset="UTF-8">
    <title>商品資料管理</title>
</head>
<body>
    <h1 align="center">刪除商品資料</h1>
    <h4 align="center">是否刪除此資料嗎？</h4>
    <form action="" method="post" name="formDel" id="formDel">
        <table align="center">
            <tr><th>欄位</th><th>資料</th></tr>
            <tr><td>商品ID</td><td><input type="text" name="Product_ID" id="Product_ID" value="<?php echo htmlspecialchars($Product_ID); ?>" readonly></td></tr>
            <tr><td>商品名稱</td><td><input type="text" name="Product_name" id="Product_name" value="<?php echo htmlspecialchars($Product_name); ?>" readonly></td></tr>
            <tr><td>價格</td><td><input type="text" name="price" id="price" value="<?php echo htmlspecialchars($price); ?>" readonly></td></tr>
            <tr>
                <td colspan="2" align="center">
                    <input name="action" type="hidden" value="delete">
                    <input type="button" value="取消" onclick="location.href='Product.php'">
                    <input type="submit" name="button0" id="button0" value="刪除">
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
