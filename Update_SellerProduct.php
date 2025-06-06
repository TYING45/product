<?php
require_once __DIR__ . '/vendor/autoload.php';
include("sql_php.php");

// 載入 .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 環境變數
$token = $_ENV['GITHUB_TOKEN'];
$repoOwner = $_ENV['GITHUB_REPO_OWNER'];
$repoName = $_ENV['GITHUB_REPO_NAME'];
$branch = $_ENV['GITHUB_BRANCH'];
$githubPagesUrl = rtrim($_ENV['GITHUB_PAGES_URL'], '/') . '/';

// GitHub API 上傳圖片
function uploadImageToGitHub($tmpFile, $imageName) {
    global $token, $repoOwner, $repoName, $branch;

    $content = base64_encode(file_get_contents($tmpFile));
    $url = "https://api.github.com/repos/$repoOwner/$repoName/contents/images/$imageName";

    $data = json_encode([
        "message" => "Upload image $imageName",
        "branch" => $branch,
        "content" => $content
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "Authorization: token $token",
            "User-Agent: PHP"
        ]
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return in_array($code, [200, 201]);
}

// 商品更新流程
if (isset($_POST["action"]) && $_POST["action"] === "update") {
    if (!empty($_POST["Product_ID"]) && !empty($_POST["Product_name"]) && !empty($_POST["price"]) &&
        !empty($_POST["quantity"]) && !empty($_POST["Product_introduction"]) && !empty($_POST["Type"])) {

        $image_url = "";

        if (!empty($_FILES["Image"]["name"])) {
            $allowed_exts = ["jpg", "jpeg", "png", "gif"];
            $ext = strtolower(pathinfo($_FILES["Image"]["name"], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed_exts)) {
                $imageName = substr(md5(uniqid()), 0, 40) . '.' . $ext;

                // 上傳到 GitHub
                if (uploadImageToGitHub($_FILES["Image"]["tmp_name"], $imageName)) {
                    $image_url = $githubPagesUrl . $imageName;
                } else {
                    echo "錯誤：圖片無法上傳至 GitHub。";
                    exit();
                }
            } else {
                echo "錯誤：圖片格式需為 JPG、JPEG、PNG 或 GIF。";
                exit();
            }
        } else {
            // 無新圖片，上次圖片保留
            $stmt = $link->prepare("SELECT Image FROM product WHERE Product_ID = ?");
            $stmt->bind_param("s", $_POST["Product_ID"]);
            $stmt->execute();
            $stmt->bind_result($old_image);
            $stmt->fetch();
            $stmt->close();
            $image_url = $old_image;
        }

        // 更新商品
        $stmt = $link->prepare("UPDATE product SET Product_name=?, Type=?, price=?, quantity=?, Product_introduction=?, Image=?, Remark=? WHERE Product_ID=?");
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

        header("Location: Seller_Product.php");
        exit();
    } else {
        echo "錯誤：欄位不得為空";
        exit();
    }
}

// 編輯時讀取資料
if (isset($_GET["id"])) {
    $Product_ID = $_GET["id"];
    $stmt = $link->prepare("SELECT Product_name, Type, price, quantity, Product_introduction, Image, Remark FROM product WHERE Product_ID = ?");
    $stmt->bind_param("s", $Product_ID);
    $stmt->execute();
    $stmt->bind_result($Product_name, $Type, $price, $quantity, $Product_introduction, $Image, $Remark);
    $stmt->fetch();
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

    <label class = "labels1">商品編號:</label>
    <input type="hidden" name="action" value="update" />
    <input class="input1" type="text" name="Product_ID" value="<?php echo htmlspecialchars($Product_ID); ?>" readonly /><br />

    <?php if (!empty($Image)): ?>
        <img src="<?php echo htmlspecialchars($Image); ?>" alt="Image" style="max-width:200px;max-height:200px;" />
    <?php endif; ?>
    <input type="file" name="Image" /><br />

    <label class = "labels2">商品名稱:</label>
    <input  class="input2" type="text" name="Product_name" value="<?php echo htmlspecialchars($Product_name); ?>" /><br />

    <label class = "labels3">商品價格:</label>
    <input  class="input3" type="text" name="price" value="<?php echo htmlspecialchars($price); ?>" /><br />

    <label class = "labels4">庫存數量：</label>
    <input  class="input4" type="text" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" /><br />

    <label class = "labels5">商品種類:</label>
    <select  class="input5" name="Type" required>
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
