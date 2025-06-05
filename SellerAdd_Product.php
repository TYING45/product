<?php
session_start();
include("sql_php.php");

// 檢查是否已登入
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 從 username 查對應 SellerID
$seller_username = $_SESSION['username'];
$stmtSeller = $link->prepare("SELECT Seller_ID FROM seller WHERE username = ?");
$stmtSeller->bind_param("s", $seller_username);
$stmtSeller->execute();
$resultSeller = $stmtSeller->get_result();

if ($resultSeller->num_rows === 0) {
    echo "查無賣家資料，請重新登入。";
    exit();
}
$sellerData = $resultSeller->fetch_assoc();
$sellerID = $sellerData['Seller_ID'];

// 新增商品處理
// 新增商品處理
if (isset($_POST["action"]) && $_POST["action"] == "add") {
    $shelf_status = 0; // 固定為下架
    $Sell_quantity = 0; // 預設銷售數量為 0

    $image_name = "";
    if (isset($_FILES['Image']) && $_FILES['Image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            $image_name = uniqid() . '.' . $file_ext;
            $target_file = $upload_dir . $image_name;

            if (!move_uploaded_file($_FILES['Image']['tmp_name'], $target_file)) {
                echo "圖片上傳失敗！";
                exit;
            }
        } else {
            echo "不支援的圖片格式！";
            exit;
        }
    } else {
        echo "請選擇圖片上傳！";
        exit;
    }

    $sqli_query = "INSERT INTO `product` 
        (`Product_ID`, `Seller_ID`, `Product_name`, `Type`, `quantity`, `Product_introduction`, `price`, `Image`, `Remark`, `Shelf_status`, `Sell_quantity`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($link, $sqli_query);
    mysqli_stmt_bind_param(
        $stmt,
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
        $Sell_quantity
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: Seller_Product.php");
        exit;
    } else {
        echo "新增商品失敗：" . mysqli_error($link);
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
            <th>欄位</th><th>資料</th></tr>
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
                </td></tr>
            <tr><td>價格</td><td><input type="number" name="price" required></td></tr>
            <tr><td>商品簡介</td><td><textarea name="Product_introduction" rows="4" required></textarea></td></tr>
            <tr><td>庫存數量</td><td><input type="number" name="quantity" required></td></tr>
            <tr><td>備註</td><td><input type="text" name="Remark"></td></tr>
             <tr>
                <td>上下架狀態</td>
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
