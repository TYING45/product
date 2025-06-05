<?php
if (isset($_POST["action"]) && $_POST["action"] == "add") {
    include("sql_php.php");

    $shelf_status = isset($_POST["Shelf_status"]) ? intval($_POST["Shelf_status"]) : 0;
    $sell_quantity = 0; // 預設為 0
    $image_path = "";

    if (isset($_FILES['Image']) && $_FILES['Image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $image_name = uniqid() . '.' . $ext;
            $target_file = $upload_dir . $image_name;
            move_uploaded_file($_FILES['Image']['tmp_name'], $target_file);
            $image_path = $target_file;
        }
    }

    $query = "INSERT INTO `product`
        (`Product_ID`, `Seller_ID`, `Product_name`, `Type`, `quantity`, `Product_introduction`, `price`, `Image`, `Remark`, `Shelf_status`, `Sell_quantity`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $link->prepare($query);
    $stmt->bind_param(
        "ssssisissii",
        $_POST["Product_ID"],
        $_POST["Seller_ID"],
        $_POST["Product_name"],
        $_POST["Type"],
        $_POST["quantity"],
        $_POST["Product_introduction"],
        $_POST["price"],
        $image_path,
        $_POST["Remark"],
        $shelf_status,
        $sell_quantity
    );

    if ($stmt->execute()) {
        header("Location: Product.php");
        exit;
    } else {
        echo "新增失敗：" . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8" />
    <link href="CSS/Add_Del.css" rel="stylesheet" />
    <title>新增商品</title>
</head>
<body>
<main>
    <h1>新增商品</h1>
    <form method="post" enctype="multipart/form-data">
        <table id="Product">
            <tr><th>欄位</th><th>資料</th></tr>
            <tr><td>商品ID</td><td><input type="text" name="Product_ID" required></td></tr>
            <tr><td>賣家ID</td><td><input type="text" name="Seller_ID" required></td></tr>
            <tr><td>圖片</td><td><input type="file" name="Image" accept="image/*" required></td></tr>
            <tr><td>商品名稱</td><td><input type="text" name="Product_name" required></td></tr>
            <tr><td>商品種類</td>
            <td>
                    <select name="Type" required>
                        <option value="0">家具</option>
                        <option value="1">家電</option>
                        <option value="2">衣物</option>
                        <option value="3">3C</option>
                        <option value="4">書</option>
                        <option value="5">玩具</option>
                        <option value="6">運動用品</option>
                        <option value="7">其他</option>
                    </select>
                </td></tr>
            <tr><td>價格</td><td><input type="number" name="price" required></td></tr>
            <tr><td>商品簡介</td><td><textarea name="Product_introduction" rows="4" required></textarea></td></tr>
            <tr><td>庫存數量</td><td><input type="number" name="quantity" required></td></tr>
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
            <tr><td>備註</td><td><input type="text" name="Remark"></td></tr>
            
            <tr>
                <td colspan="2" align="center">
                    <input type="hidden" name="action" value="add">
                    <input type="submit" value="新增">
                    <input type="button" value="取消" onclick="location.href='Product.php'">
                    <input type="reset" value="重設">
                </td>
            </tr>
        </table>
    </form>
</main>
</body>
</html>
