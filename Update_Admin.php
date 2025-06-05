<?php
include("sql_php.php");

if (isset($_POST["action"]) && $_POST["action"] == "update") {
    if (!empty($_POST["Admin_ID"]) && !empty($_POST["username"]) && !empty($_POST["password"]) &&
        !empty($_POST["Email"]) && !empty($_POST["Phone"]) && !empty($_POST["Admin_name"])) {
        
        $sql = "UPDATE `admin` SET `Admin_name`=?, `username`=?, `password`=?, `Phone`=?, `Email`=? WHERE `Admin_ID`=?";
        $stmt = $link->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssssss", 
                $_POST["Admin_name"], 
                $_POST["username"], 
                $_POST["password"],
                $_POST["Phone"], 
                $_POST["Email"], 
                $_POST["Admin_ID"]
            );
            $stmt->execute();
            $stmt->close();
        }

        header("Location: Permissions.php");
        exit();
    } else {
        echo "錯誤：有欄位未填";
    }
}

// 取得資料
if (isset($_GET["id"])) {
    $Admin_ID = $_GET["id"];

    $sql_select = "SELECT Admin_ID, Admin_name, username, password, Phone, Email FROM admin WHERE Admin_ID = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $Admin_ID);
    $stmt->execute();
    $stmt->bind_result($Admin_ID, $Admin_name, $username, $password, $Phone, $Email);
    $stmt->fetch();
    $stmt->close();
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
<form method="post">
    <h1><b>編輯管理員資料</b></h1>

    <label class='labels1'>管理員ID:</label>
    <input type="hidden" name="action" value="update">
    <input class="input1" type="text" name="Admin_ID" value="<?php echo htmlspecialchars($Admin_ID); ?>" readonly><br>

    <label class="labels2">姓名:</label>
    <input class="input2" type="text" name="Admin_name" value="<?php echo htmlspecialchars($Admin_name); ?>"><br>

    <label class="labels3">管理員帳號:</label>
    <input class="input3" type="text" name="username" value="<?php echo htmlspecialchars($username); ?>"><br>

    <label class="labels4">管理員密碼:</label> 
    <input class="input4" type="text" name="password" value="<?php echo htmlspecialchars($password); ?>"><br>

    <label class="labels5">電話:</label> 
    <input class="input5" type="text" name="Phone" value="<?php echo htmlspecialchars($Phone); ?>"><br>
    
    <label class="labels6">E-mail:</label> 
    <input class="input6" type="text" name="Email" value="<?php echo htmlspecialchars($Email); ?>"><br><br>

    <input type="button" value="取消" onclick="location.href='Permissions.php'">    
    <button type="submit">更新</button>
</form>
</body>
</html>
