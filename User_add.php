<?php
if (isset($_POST["action"]) && $_POST["action"] == "add") {
    include("sql_php.php");

    if (!empty($_POST["Seller_name"]) && !empty($_POST["Phone"]) && !empty($_POST["Email"]) &&
        !empty($_POST["username"]) && !empty($_POST["password"])) {

        // 取得最大編號數字（從 SXXX 擷取數字）
        $result = $link->query("SELECT MAX(CAST(SUBSTRING(Seller_ID, 2) AS UNSIGNED)) AS max_num FROM seller");
        $row = $result->fetch_assoc();
        $max_num = $row['max_num'] ?? 0;

        $new_num = $max_num + 1;
        $new_id = "S" . str_pad($new_num, 3, "0", STR_PAD_LEFT);  // 例如 S003


        $stmt = $link->prepare("INSERT INTO `seller`(`Seller_ID`, `Seller_name`,`Company`, `username`, `password`, `Email`, `Phone`, `Address`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $new_id, $_POST["Seller_name"], $_POST["Company"], $_POST["username"], $_POST["password"], $_POST["Email"], $_POST["Phone"], $_POST["Address"]);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            echo "新增失敗: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "所有欄位都必須填寫！";
    }

    $link->close();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>註冊</title>
    <link href="CSS/table.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1 align="center">新增用戶資料</h1>
    <form action="" method="post">
        <table align="center">
            <tr><td>姓名:</td><td><input type="text" name="Seller_name" required></td></tr>
            <tr><td>公司:</td><td><input type="text" name="Company" required></td></tr>  
            <tr><td>Email:</td><td><input type="email" name="Email" required></td></tr>
            <tr><td>電話:</td><td><input type="text" name="Phone" required></td></tr>
            <tr><td>帳號:</td><td><input type="text" name="username" required></td></tr>
            <tr><td>密碼:</td><td><input type="password" name="password" required></td></tr>
            <tr><td>地址:</td><td><input type="text" name="Address"></td></tr>
            <tr>
                <td colspan="2" align="center">
                    <input type="hidden" name="action" value="add">
                    <input type="button" value="取消" onclick="location.href='login.php'">
                    <input type="reset" value="重新修改">
                    <input type="submit" value="註冊">
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
