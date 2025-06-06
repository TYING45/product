<?php
ob_start();
include("sql_php.php");  // 確保連線變數 $link 正常

// GitHub 設定
$github_owner = "TYING45";
$github_repo = "product";
$github_branch = "main";
$github_token = "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"; 

function uploadImageToGitHub($owner, $repo, $branch, $token, $image_tmp_path, $remote_path) {
    if (!file_exists($image_tmp_path)) {
        return [0, "❌ 找不到暫存圖片檔"];
    }

    $content = base64_encode(file_get_contents($image_tmp_path));
    $url = "https://api.github.com/repos/$owner/$repo/contents/$remote_path";

    $data = [
        "message" => "Add image $remote_path via PHP script",
        "branch" => $branch,
        "content" => $content
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "User-Agent: PHP-script",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$httpCode ?: 0, $response ?: "無回應"];
}

if (isset($_POST["action"]) && $_POST["action"] == "update") {
    if (!empty($_POST["Product_ID"]) && !empty($_POST["Product_name"]) && !empty($_POST["price"]) &&
        !empty($_POST["quantity"]) && !empty($_POST["Product_introduction"]) && !empty($_POST["Type"])) {
        
        $image_name = "";
        $code = null;
        $res = null;

        if (isset($_FILES["Image"]) && !empty($_FILES["Image"]["name"])) {
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            $file_ext = strtolower(pathinfo($_FILES["Image"]["name"], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_types)) {
                $image_name = uniqid() . "." . $file_ext;
                $remote_path = "uploads/" . $image_name;

                list($code, $res) = uploadImageToGitHub(
                    $github_owner,
                    $github_repo,
                    $github_branch,
                    $github_token,
                    $_FILES["Image"]["tmp_name"],
                    $remote_path
                );

                if ($code !== 201 && $code !== 200) {
                    die("❌ GitHub 上傳圖片失敗，HTTP 狀態碼：$code，回應：$res");
                }
            } else {
                die("❌ 圖片格式錯誤，僅限 JPG, JPEG, PNG, GIF");
            }
        } else {
            // 無新圖片，保留舊圖
            $query = "SELECT Image FROM product WHERE Product_ID=?";
            $stmt = $link->prepare($query);
            $stmt->bind_param("s", $_POST["Product_ID"]);
            $stmt->execute();
            $stmt->bind_result($old_image);
            $stmt->fetch();
            $stmt->close();
            $image_name = $old_image;
        }

        // 更新資料庫
        $sql_query = "UPDATE product SET Product_name=?, Type=?, price=?, quantity=?, Product_introduction=?, Image=?, Remark=? WHERE Product_ID=?";
        $stmt = $link->prepare($sql_query);
        if ($stmt) {
            $stmt->bind_param("ssiissss", 
                $_POST["Product_name"], 
                $_POST["Type"], 
                $_POST["price"], 
                $_POST["quantity"],
                $_POST["Product_introduction"], 
                $image_name, 
                $_POST["Remark"], 
                $_POST["Product_ID"]
            );
            $stmt->execute();
            $stmt->close();
        }

        header("Location: Seller_Product.php");
        exit();
    } else {
        echo "❌ 錯誤：所有欄位皆為必填。";
    }
}

// 讀取舊資料
if (isset($_GET["id"])) {
    $Product_ID = $_GET["id"];
    $sql_select = "SELECT Product_name, Type, price, quantity, Product_introduction, Image, Remark FROM product WHERE Product_ID = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $Product_ID);
    $stmt->execute();
    $stmt->bind_result($Product_name, $Type, $price, $quantity, $Product_introduction, $Image, $Remark);
    $stmt->fetch();
    $stmt->close();
}

ob_end_flush();
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

    <input type="hidden" name="action" value="update" />
    <label class="labels1">商品編號:</label>
    <input class="input1" type="text" name="Product_ID" value="<?php echo htmlspecialchars($Product_ID); ?>" readonly /><br />

    <?php if (!empty($Image)): ?>
        <img src="https://raw.githubusercontent.com/<?php echo $github_owner; ?>/<?php echo $github_repo; ?>/<?php echo $github_branch; ?>/uploads/<?php echo htmlspecialchars($Image); ?>" alt="Image" style="max-width:200px;" />
    <?php endif; ?>

    <input type="file" name="Image" /><br />

    <label class="labels2">商品名稱:</label>
    <input class="input2" type="text" name="Product_name" value="<?php echo htmlspecialchars($Product_name); ?>" required /><br />

    <label class="labels3">商品價格:</label>
    <input class="input3" type="number" name="price" value="<?php echo htmlspecialchars($price); ?>" required /><br />

    <label class="labels4">庫存數量：</label>
    <input class="input4" type="number" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" required /><br />

    <label class="labels5">商品種類:</label>
    <select class="input5" name="Type" required>
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
