<?php
include("sql_php.php");
$upload_dir = "uploads/";

if (isset($_POST["action"]) && $_POST["action"] == "update") {
    if (!empty($_POST["Member_ID"]) && !empty($_POST["username"]) && !empty($_POST["password"]) &&
        !empty($_POST["Email"]) && !empty($_POST["Phone"])) {
        
        $sqli_query = "UPDATE `member` SET `Member_name`=?, `username`=?,`password`=?, `Phone`=?, `Email`=?, `Address`=? WHERE `Member_ID`=?";
        
        $stmt = $link->prepare($sqli_query);
        if ($stmt) {
           $stmt->bind_param("sssssss", $_POST["Member_name"], $_POST["username"], $_POST["password"],
            $_POST["Phone"], $_POST["Email"], $_POST["Address"], $_POST["Member_ID"]);

            $stmt->execute();
            $stmt->close();
        }   

        header("Location: Member.php");
        exit();
    } else {
        echo "錯誤：有欄位未填";
    }
}

// 取得 `id`，查詢該會員資訊
if (isset($_GET["id"])) {
    $Member_ID = $_GET["id"];

    $sql_select = "SELECT Member_name, username, password, Phone, Email, Address FROM member WHERE Member_ID = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $Member_ID);
    $stmt->execute();
    $stmt->bind_result($Member_name, $username, $password, $Phone, $Email, $Address);
    if ($stmt->fetch()) {
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更新資料</title>
    <link rel="stylesheet" href="CSS/Update.css">
</head>
<body>
<form method="post" enctype="multipart/form-data">
<b><h1>更新會員資料</h1></b>

    <label class='labels1'>會員ID:</label>
    <input type="hidden" name="action" value="update">
    <input class="input1" type="text" name="Member_ID" value="<?php echo $Member_ID; ?>" readonly><br>

    <label class="labels2">姓名:</label>
    <input class="input2" type="text" name="Member_name" value="<?php echo $Member_name; ?>"><br>

    <label class="labels3">會員帳號:</label>
    <input class="input3" type="text" name="username" value="<?php echo $username; ?>"><br>

    <label class="labels4">會員密碼:</label> 
    <input class="input4" type="text" name="password" value="<?php echo $password; ?>"><br>

    <label class="labels5">電話:</label> 
    <input class="input5" type="text" name="Phone" value="<?php echo $Phone; ?>"><br>
    
    <label class="labels6">E-mail:</label> 
    <input class="input6" type="text" name="Email" value="<?php echo $Email; ?>"><br>

    <label>地址:</label><br>
    <textarea name="Address" rows="2" cols="100"><?php echo $Address; ?></textarea><br> 
    <input type="button" value="取消" onclick="location.href='Member.php'">    
    <button type="submit">更新</button>
</form>
</body>
</html>
