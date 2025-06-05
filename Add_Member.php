<!-- 會員資料-->
<?php
include("sql_php.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add") {
    if (!empty($_POST["Member_name"]) && !empty($_POST["password"]) && !empty($_POST["Email"]) && !empty($_POST["Phone"])) {

        // 查出最大 Member_ID
        $result = $link->query("SELECT MAX(Member_ID) AS max_id FROM member");
        $row = $result->fetch_assoc();

        if ($row["max_id"]) {
            // 取得數字部分，轉為整數後 +1
            $num = intval(substr($row["max_id"], 1)) + 1;
            $new_id = "M" . str_pad($num, 4, "0", STR_PAD_LEFT);  // 補滿3位數
        } else {
            $new_id = "M001";  // 第一筆資料
        }

        $stmt = $link->prepare("INSERT INTO `member`(`Member_ID`, `Member_name`,  `password`, `Email`, `Phone`, `Address`) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $new_id, $_POST["Member_name"], $_POST["password"],
                          $_POST["Email"], $_POST["Phone"], $_POST["Address"]);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>alert('新增成功！會員ID：$new_id'); window.location.href = 'Member.php';</script>";
        } else {
            echo "<script>alert('資料新增失敗！');</script>";
        }

        $stmt->close();
        $link->close();
    } else {
        echo "<script>alert('有欄位未填寫');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="CSS/Add_Del.css" rel="stylesheet" type="text/css">
    <title>新增會員資料</title>
</head>
<body>
<main>
 <p><b><font size="5">新增會員資料</font></b></p>
 <form method="POST" action="">
 <table id = "Add_Member" >
    <tr>
        <th>欄位</th><th>資料</th></tr>
		<tr><td>會員姓名</td><td><input type="text" name ="Member_name"id="Member_name"required></td></tr>
		<tr><td>會員密碼</td><td><input type="text" name ="password"id="password"required></td></tr>
		<tr><td>email</td><td><input type="text" name ="Email"id="Email" required></td></tr>
		<tr><td>電話</td><td><input type="text" name ="Phone"id="Phone" pattern="\d{10}" required></td></tr>
        <tr><td>地址</td><td><input type="text" name ="Address"id="Address" ></td></tr>
		<tr>
        <td colspan="2" align="center">
                    <input name="action" type="hidden" value="add">
                    <input type="button" value="取消" onclick="location.href='Member.php'">
                    <input type="submit" value="新增資料">
    </tr>
    
    </table>
    </form>
</main>
</body>
</html>
