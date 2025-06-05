<?php
include("sql_php.php");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // 取得目前狀態
    $stmt = $link->prepare("SELECT `Shelf_status` FROM `product` WHERE `Product_ID` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($status);
    if ($stmt->fetch()) {
        $stmt->close();
        // 切換狀態
        $newStatus = $status ? 0 : 1;
        $update = $link->prepare("UPDATE `product` SET `Shelf_status` = ? WHERE `Product_ID` = ?");
        $update->bind_param("ii", $newStatus, $id);
        $update->execute();
        $update->close();
    } else {
        $stmt->close();
    }
}

header("Location: Product.php");
exit;
?>
