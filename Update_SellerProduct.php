<?php
include("sql_php.php");

$upload_dir = "uploads/";

// GitHub 設定
$token = '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';
$repoOwner = 'TYING45';
$repoName = 'product';
$branch = 'main';
$githubPagesUrl = "https://tying45.github.io/product/images/";

// GitHub API 上傳圖片函式
function uploadImageToGitHub($filePath, $fileName) {
    global $token, $repoOwner, $repoName, $branch;

    $content = base64_encode(file_get_contents($filePath));
    $apiUrl = "https://api.github.com/repos/$repoOwner/$repoName/contents/images/$fileName";

    $data = json_encode([
        "message" => "Add image $fileName",
        "branch" => $branch,
        "content" => $content
    ]);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "User-Agent: PHP"
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 201 || $httpcode == 200) {
        return true;
    } else {
        // 你可以印出錯誤訊息做偵錯
        // echo $response;
        return false;
    }
}

if (isset($_POST["action"]) && $_POST["action"] == "update") {
    if (!empty($_POST["Product_ID"]) && !empty($_POST["Product_name"]) && !empty($_POST["price"]) &&
        !empty($_POST["quantity"]) && !empty($_POST["Product_introduction"]) && !empty($_POST["Type"])) {

        $image_url = ""; // 最終要存資料庫的圖片URL

        if (!empty($_FILES["Image"]["name"])) {
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            $file_ext = strtolower(pathinfo($_FILES["Image"]["name"], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_types)) {
                // 產生檔名（確保不超過 50 字元）
                $image_name = substr(md5(uniqid('', true)), 0, 40) . "." . $file_ext;

                // 先上傳到本地
                if (!move_uploaded_file($_FILES["Image"]["tmp_name"], $upload_dir . $image_name)) {
                    echo "錯誤：無法將圖片上傳至伺服器。";
                    exit();
                }

                // 再上傳到 GitHub
                if (!uploadImageToGitHub($upload_dir . $image_name, $image_name)) {
                    echo "錯誤：圖片無法上傳至 GitHub。";
                    exit();
                }

                // GitHub Pages 的公開圖片 URL
                $image_url = $githubPagesUrl . $image_name;

            } else {
                echo "錯誤：圖片格式必須為 JPG、JPEG、PNG 或 GIF";
                exit();
            }
        } else {
            // 若沒上傳新圖片，保留舊圖片 URL
            $query = "SELECT Image FROM product WHERE Product_ID=?";
            $stmt = $link->prepare($query);
            $stmt->bind_param("s", $_POST["Product_ID"]);
            $stmt->execute();
            $stmt->bind_result($old_image);
            $stmt->fetch();
            $stmt->close();
            $image_url = $old_image;
        }

        // 更新資料庫
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

if (isset($_GET["id"])) {
    $Product_ID = $_GET["id"];

    $sql_select = "SELECT Product_name, Type, price, quantity, Product_introduction, Image, Remark FROM product WHERE Product_ID = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $Product_ID);
    $stmt->execute();
    $stmt->bind_result($Product_name, $Type, $price, $quantity, $Product_introduction, $Image, $Remark);
    if ($stmt->fetch()) {
        // 讀到資料
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8" />
    <title>更新商品資料</title>
    <link rel="stylesheet" href="CSS/Product_update.css" />
</head>
<body>
<form method="post" enctype="multipart/form-data">
    <h1><b>更新商品資料</b></h1>

    <label>商品編號:</label>
    <input type="hidden" name="action" value="update" />
    <input type="text" name="Product_ID" value="<?php echo htmlspecialchars($Product_ID); ?>" readonly /><br />

    <?php if (!empty($Image)): ?>
        <img src="<?php echo htmlspecialchars($Image); ?>" alt="Image" style="max-width:200px;max-height:200px;" />
    <?php endif; ?>
    <input type="file" name="Image" /><br />

    <label>商品名稱:</label>
    <input type="text" name="Product_name" value="<?php echo htmlspecialchars($Product_name); ?>" /><br />

    <label>商品價格:</label>
    <input type="text" name="price" value="<?php echo htmlspecialchars($price); ?>" /><br />

    <label>庫存數量：</label>
    <input type="text" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" /><br />

    <label>商品種類:</label>
    <select name="Type" required>
        <?php
        $types = ["家具", "家電", "衣物", "3C", "書", "玩具", "運動用品", "其他"];
        foreach ($types as $t) {
            $selected = ($t == $Type) ? "selected" : "";
            echo "<option value='$t' $selected>$t</option>";
        }
        ?>
    </select><br />

    <label>商品簡介:</label><br />
    <textarea name="Product_introduction" rows="10" cols="100"><?php echo htmlspecialchars($Product_introduction); ?></textarea><br /><br />

    <label>備註：</label><br />
    <textarea name="Remark" rows="2" cols="100"><?php echo htmlspecialchars($Remark); ?></textarea><br />
    <input type="button" value="取消" onclick="location.href='Seller_Product.php'" />
    <button type="submit">更新</button>
</form>
</body>
</html>

