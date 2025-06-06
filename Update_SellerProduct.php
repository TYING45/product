<?php
include("sql_php.php");

// GitHub 上傳圖片功能
function uploadImageToGitHub($file, $filename) {
    $token = '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';
    $repoOwner = 'TYING45';
    $repoName = 'product';
    $branch = 'main';

    $imageData = base64_encode(file_get_contents($file));
    $pathInRepo = "uploads/" . $filename;
    $url = "https://api.github.com/repos/$repoOwner/$repoName/contents/$pathInRepo";

    $payload = json_encode([
        "message" => "Upload $filename",
        "content" => $imageData,
        "branch" => $branch
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'GitHubUploader');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status == 201) {
        return "https://raw.githubusercontent.com/$repoOwner/$repoName/$branch/$pathInRepo";
    } else {
        return false;
    }
}

if (isset($_POST["action"]) && $_POST["action"] == "update") {
    if (!empty($_POST["Product_ID"]) && !empty($_POST["Product_name"]) && !empty($_POST["price"]) &&
        !empty($_POST["quantity"]) && !empty($_POST["Product_introduction"]) && !empty($_POST["Type"])) {

        $image_url = ""; // 預設圖片 URL

        // 如有上傳新圖片，則轉存 GitHub
        if (!empty($_FILES["Image"]["name"])) {
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            $file_ext = strtolower(pathinfo($_FILES["Image"]["name"], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_types)) {
                $new_filename = uniqid() . "." . $file_ext;
                $image_url = uploadImageToGitHub($_FILES["Image"]["tmp_name"], $new_filename);
                if (!$image_url) {
                    echo "圖片上傳 GitHub 失敗"; exit();
                }
            } else {
                echo "錯誤：圖片格式必須為 JPG、JPEG、PNG 或 GIF"; exit();
            }
        } else {
            // 沒有新圖片，上傳時保留原圖
            $query = "SELECT Image FROM product WHERE Product_ID=?";
            $stmt = $link->prepare($query);
            $stmt->bind_param("s", $_POST["Product_ID"]);
            $stmt->execute();
            $stmt->bind_result($old_image);
            $stmt->fetch();
            $stmt->close();
            $image_url = $old_image;
        }

        // 執行更新
        $sql_query = "UPDATE `product` SET Product_name=?, Type=?, price=?, quantity=?, Product_introduction=?, Image=?, Remark=? WHERE Product_ID=?";
        $stmt = $link->prepare($sql_query);
        if ($stmt) {
            $stmt->bind_param("ssiissss",
                $_POST["Product_name"],
                $_POST["Type"],
                $_POST["price"],
                $_POST["quantity"],
                $_POST["Product_introduction"],
                $image_url,
                $_POST["Remark"],
                $_POST["Product_ID"]
            );
            $stmt->execute();
            $stmt->close();
        }

        header("Location: Seller_Product.php");
        exit();
    } else {
        echo "錯誤：有欄位未填";
    }
}

// 編輯頁面資料載入
if (isset($_GET["id"])) {
    $Product_ID = $_GET["id"];
    $sql_select = "SELECT Product_name, Type, price, quantity, Product_introduction, Image, Remark FROM product WHERE Product_ID = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $Product_ID);
    $stmt->execute();
    $stmt->bind_result($Product_name, $Type, $price, $quantity, $Product_introduction, $Image, $Remark);
    if ($stmt->fetch()) 
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>更新商品資料</title>
    <link rel="stylesheet" href="CSS/Product_update.css">
</head>
<body>
<form method="post" enctype="multipart/form-data">
    <h1>更新商品資料</h1>
    <label class='labels1'>商品編號:</label>
    <input type="hidden" name="action" value="update">
    <input class="input1" type="text" name="Product_ID" value="<?php echo $Product_ID; ?>" readonly><br>

    <?php if (!empty($Image)): ?>
        <img src="<?php echo $Image; ?>" alt="商品圖片" width="200"><br>
    <?php endif; ?>
    <input type="file" name="Image"><br>

    <label class="labels2">商品名稱:</label>
    <input class="input2" type="text" name="Product_name" value="<?php echo $Product_name; ?>"><br>

    <label class="labels3">商品價格:</label>
    <input class="input3" type="text" name="price" value="<?php echo $price; ?>"><br>

    <label class="labels4">庫存數量：</label> 
    <input class="input4" type="text" name="quantity" value="<?php echo $quantity; ?>"><br>

    <label class="labels5">商品種類:</label>
    <select class="input5" name="Type" required>
        <?php
        $types = ["家具", "家電", "衣物", "3C", "書", "玩具", "運動用品", "其他"];
        foreach ($types as $t) {
            $selected = ($t == $Type) ? "selected" : "";
            echo "<option value='$t' $selected>$t</option>";
        }
        ?>
    </select><br>

    <label>商品簡介:</label><br>
    <textarea name="Product_introduction" rows="10" cols="100"><?php echo $Product_introduction;?></textarea><br><br>

    <label>備註：</label><br>
    <textarea name="Remark" rows="2" cols="100"><?php echo $Remark;?></textarea><br>

    <input type="button" value="取消" onclick="location.href='Seller_Product.php'">    
    <button type="submit">更新</button>
</form>
</body>
</html>
