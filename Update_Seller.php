<?php
include("sql_php.php");
$upload_dir = "uploads/";

if (isset($_POST["action"]) && $_POST["action"] == "update") {
    if (!empty($_POST["Seller_ID"]) && !empty($_POST["username"]) && !empty($_POST["password"]) &&
    !empty($_POST["Email"]) && !empty($_POST["Phone"]) && !empty($_POST["Seller_name"]) && !empty($_POST["Company"]) &&!empty("Seller_introduction")) {
        $sqli_query = "UPDATE `seller` SET `Seller_name`=?,`Company`= ?, `username`=?, `password`=?, `Phone`=?, `Email`=?, `Address`=?,Seller_introduction=? WHERE `Seller_ID`=?";
        
        $stmt = $link->prepare($sqli_query);
        if ($stmt) {
            $stmt->bind_param("sssssssss", $_POST["Seller_name"],$_POST["Company"], $_POST["username"], $_POST["password"],
                $_POST["Phone"], $_POST["Email"], $_POST["Address"],$_POST["Seller_introduction]", $_POST["Seller_ID"]);
            $stmt->execute();
            $stmt->close();
        }   

        header("Location: Seller.php");
        exit();
    } else {
        echo "錯誤：有欄位未填";
    }
}

// 取得 `id`，查詢該會員資訊
if (isset($_GET["id"])) {
    $Seller_ID = $_GET["id"];

    $sql_select = "SELECT Seller_name, Company, username, password, Phone, Email, Address,Seller_introduction FROM seller WHERE Seller_ID = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $Seller_ID);
    $stmt->execute();
    $stmt->bind_result($Seller_name,$Company, $username, $password, $Phone, $Email, $Address,$Seller_introduction);
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
<b><h1>編輯賣家資料</h1></b>

    <label class='labels1'>賣家ID:</label>
    <input type="hidden" name="action" value="update">
    <input class="input1" type="text" name="Seller_ID" value="<?php echo $Seller_ID; ?>" readonly><br>

    <label class="labels2">聯絡人:</label>
    <input class="input2" type="text" name="Seller_name" value="<?php echo $Seller_name; ?>"><br>

    <label class="labels3">賣家帳號:</label>
    <input class="input3" type="text" name="username" value="<?php echo $username; ?>"><br>

    <label class="labels4">賣家密碼:</label> 
    <input class="input4" type="text" name="password" value="<?php echo $password; ?>"><br>

    <label class="labels5">電話:</label> 
    <input class="input5" type="text" name="Phone" value="<?php echo $Phone; ?>"><br>
    
    <label class="labels6">E-mail:</label> 
    <input class="input6" type="text" name="Email" value="<?php echo $Email; ?>"><br>

    <label class="labels7">公司:</label> 
    <input class="input7" type="text" name="Company" value="<?php echo $Company; ?>"><br>

    <br>
    <label>地址:</label><br>
    <textarea name="Address" rows="2" cols="100"><?php echo $Address; ?></textarea><br> 
    <label>賣家介紹</label> 
    <textarea name="Seller_introduction" rows="3" cols="100"><?php echo $Seller_introduction; ?></textarea><br> 
    <input type="button" value="取消" onclick="location.href='Seller.php'">    
    <button type="submit">更新</button>
</form>
</body>
</html>
