<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include("sql_php.php");

// 環境變數
$github_owner = getenv('GITHUB_OWNER') ?: 'TYING45';
$github_repo = getenv('GITHUB_REPO') ?: 'product';
$github_branch = getenv('GITHUB_BRANCH') ?: 'main';
$github_token = getenv('GITHUB_TOKEN') ?: '';

// 確認登入（示範用，依實際狀況調整）
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// 從 username 取 Seller_ID
$seller_username = $_SESSION['username'];
$stmtSeller = $link->prepare("SELECT Seller_ID FROM seller WHERE username = ?");
$stmtSeller->bind_param("s", $seller_username);
$stmtSeller->execute();
$resultSeller = $stmtSeller->get_result();
if ($resultSeller->num_rows === 0) {
    die("查無賣家資料，請重新登入");
}
$sellerData = $resultSeller->fetch_assoc();
$sellerID = $sellerData['Seller_ID'];

// GitHub 上傳圖片函式，和你 Update 版本一致
function uploadImageToGitHub($owner, $repo, $branch, $token, $image_tmp_path, $remote_path) {
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

    return [$httpCode, $response];
}

// 處理新增商品
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["action"]) && $_POST["action"] === "add") {
    // 檢查必填欄位
    if (
        empty($_POST["Product_ID"]) || empty($_POST["Product_name"]) || empty($_POST["price"]) ||
        empty($_POST["quantity"]) || empty($_POST["Product_introduction"]) || empty($_POST["Type"])
    ) {
        die("❌ 錯誤：有欄位未填。");
    }

    $image_name = "";
    if (isset($_FILES["Image"]) && !empty($_FILES["Image"]["name"])) {
        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        $file_ext = strtolower(pathinfo($_FILES["Image"]["name"], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            die("❌ 錯誤：圖片格式必須為 JPG、JPEG、PNG 或 GIF");
        }

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

        if ($code != 201 && $code != 200) {
            die("❌ GitHub 上傳圖片失敗<br>HTTP 狀態碼：$code<br>回應內容：$res");
        }
    } else {
        die("❌ 請上傳圖片。");
    }

    // 預設上架狀態，下架(0)或由表單帶入也可以改
    $shelf_status = 0;
    if (isset($_POST['Shelf_status'])) {
        $shelf_status = intval($_POST['Shelf_status']);
    }
    $sell_quantity = 0; // 預設銷售數量0

    // 新增資料
    $sql = "INSERT INTO product (Product_ID, Seller_ID, Product_name, Type, quantity, Product_introduction, price, Image, Remark, Shelf_status, Sell_quantity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("資料庫錯誤：" . $link->error);
    }

    $stmt->bind_param(
        "ssssisissii",
        $_POST["Product_ID"],
        $sellerID,
        $_POST["Product_name"],
        $_POST["Type"],
        $_POST["quantity"],
        $_POST["Product_introduction"],
        $_POST["price"],
        $image_name,
        $_POST["Remark"],
        $shelf_status,
        $sell_quantity
    );

    if ($stmt->execute()) {
        header("Location: Seller_Product.php");
        exit();
    } else {
        die("新增商品失敗：" . $stmt->error);
    }
}

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>新增商品資料</title>
<link rel="stylesheet" href="CSS/Add_Del.css" />
</head>
<body>
<form method="post" enctype="multipart/form-data">
    <h1><b>新增商品資料</b></h1>

    <label>商品編號:</label>
    <input type="text" name="Product_ID" required /><br />

    <label>圖片:</label>
    <input type="file" name="Image" accept="image/*" required /><br />

    <label>商品名稱:</label>
    <input type="text" name="Product_name" required /><br />

    <label>商品價格:</label>
    <input type="number" name="price" required /><br />

    <label>庫存數量:</label>
    <input type="number" name="quantity" required /><br />

    <label>商品種類:</label>
    <select name="Type" required>
        <?php
        $types = ["家具", "家電", "衣物", "3C", "書", "玩具", "運動用品", "其他"];
        foreach ($types as $t) {
            echo "<option value='$t'>$t</option>";
        }
        ?>
    </select><br />

    <label>商品簡介:</label><br />
    <textarea name="Product_introduction" rows="6" required></textarea><br />

    <label>備註:</label><br />
    <textarea name="Remark" rows="2"></textarea><br />

    <label>上下架狀態:</label>
    <select name="Shelf_status" required>
        <option value="0">已下架</option>
        <option value="1">上架中</option>
        <option value="2">缺貨</option>
    </select><br /><br />

    <input type="hidden" name="action" value="add" />
    <button type="submit">新增</button>
    <input type="button" value="取消" onclick="location.href='Seller_Product.php'" />
</form>
</body>
</html>
