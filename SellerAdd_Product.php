<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include("sql_php.php");

$github_owner = getenv('GITHUB_OWNER') ?: 'TYING45';
$github_repo = getenv('GITHUB_REPO') ?: 'product';
$github_branch = getenv('GITHUB_BRANCH') ?: 'main';
$github_token = getenv('GITHUB_TOKEN') ?: '';

// 檢查登入
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$seller_username = $_SESSION['username'];
$stmtSeller = $link->prepare("SELECT Seller_ID FROM seller WHERE username = ?");
$stmtSeller->bind_param("s", $seller_username);
$stmtSeller->execute();
$resultSeller = $stmtSeller->get_result();
if ($resultSeller->num_rows === 0) {
    die("查無賣家資料");
}
$sellerData = $resultSeller->fetch_assoc();
$sellerID = $sellerData['Seller_ID'];

// GitHub 圖片上傳函式
function uploadImageToGitHub($owner, $repo, $branch, $token, $image_tmp_path, $remote_path) {
    $content = base64_encode(file_get_contents($image_tmp_path));
    $url = "https://api.github.com/repos/TYING45/product/contents/$remote_path";

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

// 新增商品處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["action"]) && $_POST["action"] === "add") {
    $image_name = "";

    if (isset($_FILES["Image"]) && $_FILES["Image"]["error"] === UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES["Image"]["name"], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            die("圖片格式錯誤！");
        }

        $image_name = uniqid() . "." . $ext;
        $remote_path = "uploads/" . $image_name;

        list($statusCode, $res) = uploadImageToGitHub(
            $github_owner, $github_repo, $github_branch, $github_token,
            $_FILES["Image"]["tmp_name"], $remote_path
        );

        if ($statusCode != 201 && $statusCode != 200) {
            die("GitHub 上傳圖片失敗：" . $res);
        }
    } else {
        die("請選擇圖片上傳！");
    }

    $stmt = $link->prepare("INSERT INTO product 
        (Product_ID, Seller_ID, Product_name, Type, quantity, Product_introduction, price, Image, Remark, Shelf_status, Sell_quantity)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");

    $stmt->bind_param(
        "ssssisissi",
        $_POST["Product_ID"],
        $sellerID,
        $_POST["Product_name"],
        $_POST["Type"],
        $_POST["quantity"],
        $_POST["Product_introduction"],
        $_POST["price"],
        $image_name,
        $_POST["Remark"],
        $_POST["Shelf_status"]
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
    <link href="CSS/Add_Del.css" rel="stylesheet" type="text/css" />
    <title>新增商品系統</title>
</head>
<body>
<main>
    <h1>新增商品系統</h1>
    <form method="post" action="" enctype="multipart/form-data">
        <table id="Product">
            <tr><th>欄位</th><th>資料</th></tr>
            <tr><td>商品ID</td><td><input type="text" name="Product_ID" required></td></tr>
            <tr><td>圖片</td><td><input type="file" name="Image" accept="image/*" required></td></tr>
            <tr><td>商品名稱</td><td><input type="text" name="Product_name" required></td></tr>
            <tr><td>商品種類</td>
                <td>
                    <select name="Type" required>
                        <option value="家具">家具</option>
                        <option value="家電">家電</option>
                        <option value="衣物">衣物</option>
                        <option value="3C">3C</option>
                        <option value="書">書</option>
                        <option value="玩具">玩具</option>
                        <option value="運動用品">運動用品</option>
                        <option value="其他">其他</option>
                    </select>
                </td>
            </tr>
            <tr><td>價格</td><td><input type="number" name="price" required></td></tr>
            <tr><td>商品簡介</td><td><textarea name="Product_introduction" rows="4" required></textarea></td></tr>
            <tr><td>庫存數量</td><td><input type="number" name="quantity" required></td></tr>
            <tr><td>備註</td><td><input type="text" name="Remark"></td></tr>
            <tr><td>上下架狀態</td>
                <td>
                    <select name="Shelf_status" required>
                        <option value="0">已下架</option>
                        <option value="1">上架中</option>
                        <option value="2">缺貨</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input name="action" type="hidden" value="add" />
                    <input type="submit" value="新增" />
                    <input type="button" value="取消" onclick="location.href='Seller_Product.php'" />
                    <input type="reset" value="重設" />
                </td>
            </tr>
        </table>
    </form>
</main>
</body>
</html>
