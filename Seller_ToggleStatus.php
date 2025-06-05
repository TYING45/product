<?php
include("sql_php.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // 取得目前狀態
    $stmt = $link->prepare("SELECT `Shelf_status` FROM `product` WHERE `Product_ID` = ?");
    $stmt->bind_param("s", $id); 
    $stmt->execute();
    $stmt->bind_result($status);
    if ($stmt->fetch()) {
        $stmt->close();

        // 循環切換狀態 0->1->2->0
        $newStatus = ($status + 1) % 3;

        $update = $link->prepare("UPDATE `product` SET `Shelf_status` = ? WHERE `Product_ID` = ?");
        $update->bind_param("is", $newStatus, $id);
        $update->execute();
        $update->close();
    } else {
        $stmt->close();
    }
}

header("Location: Seller_Product.php");
exit;
?>
