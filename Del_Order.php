<?php
include("sql_php.php");

if (isset($_POST["action"]) && ($_POST["action"] == "delete")) {
    $sqli_query = "DELETE FROM `ordershop` WHERE Order_ID = ?";
    $stmt = $link->prepare($sqli_query);

    // 綁定參數並執行刪除操作
    $stmt->bind_param("s", $_POST["Order_ID"]);
    if ($stmt->execute()) {
        header("Location: Order.php");
        exit();
    } else {
        echo "刪除失敗：" . $stmt->error;
    }
}

// 從資料庫獲取要刪除的會員資料
if (isset($_GET["id"])) {
    $sql_select = "SELECT Order_ID,Order_name,billing_name FROM `ordershop` WHERE `Order_ID` = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $_GET["id"]);
    $stmt->execute();
    $stmt->bind_result($Order_ID, $Order_name, $billing_name);
    $stmt->fetch();
    $stmt->close(); // 關閉查詢
} else {
    echo "未提供訂單編號！";
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <link href="CSS/Add_Del.css" rel="stylesheet" type="text/css">
    <meta charset="UTF-8">
    <title>會員資料管理</title>
</head>
<body>
    <h1 align="center">刪除會員資料</h1>
    <h4 align="center">是否刪除此資料嗎？</h4>
    <form action="" method="post" name="formDel" id="formDel">
        <table align="center">
            <tr><th>欄位</th><th>資料</th></tr>
            <tr>
                <td>訂單編號</td>
                <td><input type="text" name="Order_ID" id="Order_ID" value="<?php echo htmlspecialchars($Order_ID); ?>" readonly></td>
            </tr>
            <tr>
                <td>商品名稱</td>
                <td><input type="text" name="Order_name" id="Order_name" value="<?php echo htmlspecialchars($Order_name); ?>" readonly></td>
            </tr>
            <tr>
            <tr>
                <td>姓名</td>
                <td><input type="text" name="billing_name" id="billing_name" value="<?php echo htmlspecialchars($billing_name); ?>" readonly></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input name="action" type="hidden" value="delete">
                    <input type="button" value="取消" onclick="location.href='Order.php'">
                    <input type="submit" name="button0" id="button0" value="刪除">
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
