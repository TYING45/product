<!-- 權限資料-->
<?php
if (isset($_POST["action"]) && $_POST["action"] == "add") {
    include("sql_php.php");

    $stmt = $link->prepare("INSERT INTO `admin `(`Admin_ID`, `Admin_name`, `username`, `password`, `Email`, `Phone`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $_POST["UserID"], $_POST["name"], $_POST["username"], $_POST["password"], 
                      $_POST["Email"], $_POST["Phone"]);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>alert('資料新增成功！'); window.location.href = 'Permissions.php';</script>";
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
    <title>新增管理員</title>
</head>
<body>
<head>
<link href="CSS/form.css" rel="stylesheet" type="text/css">
<main>
<h1>新增管理員系統</h1>
<form method="POST" action="">
 <table id = "Permission" >
    <tr>
        <th>欄位</th><th>資料</th></tr>
		<tr><td>管理員編號</td><td><input type="text" name ="UserID"id="UserID" required></td></tr>
		<tr><td>姓名</td><td><input type="text" name ="name"id="name" required></td></tr>
        	<tr><td>帳號</td><td><input type="text" name ="username"id="username" required></td></tr>
		<tr><td>密碼</td><td><input type="text" name ="password"id="password" required></td></tr>
		<tr><td>電話</td><td><input type="text" name ="Phone"id="Phone" required></td></tr>
		<tr><td>email</td><td><input type="text" name ="Email"id="Email" required></td></tr>
		<tr>
		<td colspan="2" align="center">
		< <td colspan="2" align="center">
                    <input name="action" type="hidden" value="add">
                    <input type="button" value="取消" onclick="location.href='Permissions.php'">
                    <input type="submit" value="新增資料">
    </tr>
    
    </table>
</head>
</main>
