<?php
if (isset($_POST["action"]) && $_POST["action"] == "add") {
    include("sql_php.php");

    // 修正欄位順序正確對應 SQL 語句
    $stmt = $link->prepare("INSERT INTO `seller`(`Seller_ID`, `Seller_name`, `Company`, `username`, `password`, `email`, `phone`, `Address`, `Seller_introduction`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", 
        $_POST["Seller_ID"], 
        $_POST["Seller_name"], 
        $_POST["Company"], 
        $_POST["username"], 
        $_POST["password"], 
        $_POST["Email"], 
        $_POST["Phone"], 
        $_POST["Address"],
        $_POST["Seller_introduction"]
    );
    
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>alert('資料新增成功！'); window.location.href = 'Seller.php';</script>";
    } else {
        echo "<script>alert('資料新增失敗！請檢查輸入資料。');</script>";
    }

    $stmt->close();
    $link->close();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="CSS/Add_Del.css" rel="stylesheet" type="text/css">
    <title>新增廠商資料</title>
</head>
<body>
<main>
    <h1>賣家管理系統</h1>
    <form method="POST" action="">
        <table id="Add_Member">
            <tr><th>欄位</th><th>資料</th></tr>
            <tr><td>賣家ID</td><td><input type="text" name="Seller_ID" id="Seller_ID" required></td></tr>
            <tr><td>聯絡人</td><td><input type="text" name="Seller_name" id="Seller_name" required></td></tr>
            <tr><td>公司</td><td><input type="text" name="Company" id="Company" required></td></tr>
            <tr><td>賣家帳號</td><td><input type="text" name="username" id="username" required></td></tr>
            <tr><td>賣家密碼</td><td><input type="password" name="password" id="password" required></td></tr>
            <tr><td>email</td><td><input type="Email" name="Email" id="Email" required></td></tr>
            <tr><td>電話</td><td><input type="tel" name="Phone" id="Phone" pattern="\d{10}" required></td></tr>
            <tr><td>地址</td><td><input type="text" name="Address" id="Address"></td></tr>
             <tr><td>賣家介紹</td><td><input type="text" name="Seller_introduction" id="Seller_introduction"></td></tr>
            <tr>
                <td colspan="2" align="center">
                    <input name="action" type="hidden" value="add">
                    <input type="button" value="取消" onclick="location.href='Seller.php'">
                    <input type="submit" value="新增資料">
                </td>
            </tr>
        </table>
    </form>
</main>
</body>
</html>
