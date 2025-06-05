<?php
include("sql_php.php");
$upload_dir = "uploads/";
if (isset($_POST["action"]) && $_POST["action"] == "update") {
    if (!empty($_POST["Product_ID"]) && !empty($_POST["Product_name"]) && !empty($_POST["price"]) &&
        !empty($_POST["quantity"]) && !empty($_POST["Product_introduction"] && !empty($_POST["Type"]))) {
        
        $image_name = ""; // 預設圖片名稱
        if (!empty($_FILES["Image"]["name"])) {
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            $file_ext = strtolower(pathinfo($_FILES["Image"]["name"], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_types)) {
                $image_name = uniqid() . "." . $file_ext;
                move_uploaded_file($_FILES["Image"]["tmp_name"], $upload_dir . $image_name);
            } else {
                echo "錯誤：圖片格式必須為 JPG、JPEG、PNG 或 GIF";
                exit();
            }
        } else {
            // 若未上傳新圖片，則保留原本圖片
            $query = "SELECT Image FROM product WHERE Product_ID=?";
            $stmt = $link->prepare($query);
            $stmt->bind_param("s", $_POST["Product_ID"]);
            $stmt->execute();
            $stmt->bind_result($old_image);
            $stmt->fetch();
            $stmt->close();
            $image_name = $old_image;
        }

        $sql_query = "UPDATE `product` SET Product_name=?,Type=?, price=?, quantity=?, Product_introduction=?, Image=?, Remark=?  WHERE Product_ID=?";
        
        $stmt = $link->prepare($sql_query);
        if ($stmt) {
            $stmt->bind_param("sisissss", 
            $_POST["Product_name"], 
            $_POST["Type"], $_POST["price"], 
            $_POST["quantity"],
            $_POST["Product_introduction"], 
            $image_name, 
            $_POST["Remark"], 
            $_POST["Product_ID"]);
            $stmt->execute();
            $stmt->close();
        }   

        header("Location: Product.php");
        exit();
    } else {
        echo "錯誤：有欄位未填";
    }
}
if (isset($_GET["id"])) {
    $Product_ID = $_GET["id"];

    $sql_select = "SELECT Product_name,Type, price, quantity, Product_introduction, Image,Remark FROM product WHERE Product_ID = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $Product_ID);
    $stmt->execute();
    $stmt->bind_result($Product_name,$Type, $price, $quantity, $Product_introduction, $Image, $Remark);
    if ($stmt->fetch()) 
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更新資料</title>
    <link rel="stylesheet" href="CSS/Product_update.css">
</head>
<body>
<form method="post" enctype="multipart/form-data">
<b><h1>更新商品資料</h1></b>

    <label class='labels1'>商品編號:</label>
    <input type="hidden" name="action" value="update">
    <input class="input1" type="text" name="Product_ID" value="<?php echo $Product_ID; ?>" readonly><br>
      
    <?php if (!empty($Image)): ?>
        <img src="uploads/<?php echo $Image; ?>" alt="Image">
    <?php endif; ?>
    <input type="file" name="Image"><br> 
    <label class="labels2">商品名稱:</label>
    <input class="input2" type="text" name="Product_name" value="<?php echo $Product_name; ?>"><br>

    <label class="labels3">商品價格:</label>
    <input class="input3" type="text" name="price" value="<?php echo $price; ?>"><br>

    <label class="labels4">庫存數量：</label> 
    <input class="input4" type="text" name="quantity" value="<?php echo $quantity; ?>"><br>

    <label class="labels5">商品種類:</label>
    <input class="input5" type="text" name="Type" value="<?php echo $Type; ?>"><br>
    
    <label>商品簡介:</label><br>
    <textarea name="Product_introduction" rows="10" cols="100"><?php echo $Product_introduction;?></textarea><br><br>

    <label>備註：</label><br>
    <textarea name="Remark" rows="2" cols="100"><?php echo $Remark;?></textarea><br> 
    <input type="button" value="取消" onclick="location.href='Product.php'">    
    <button type="submit">更新</button>
</form>
</body>
</html>
