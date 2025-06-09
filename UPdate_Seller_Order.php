
<?php
include("sql_php.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 優先從 POST 拿 Order_ID，沒就從 GET 拿
    $Order_ID = $_POST['Order_ID'] ?? $_GET['id'] ?? null;
    $Ship_Date = $_POST['Ship_Date'] ?? null;
    $Transport = $_POST['Transport'] ?? '';
    $shipping_zip = $_POST['shipping_zip'] ?? '';
    $Order_status = $_POST['Order_status'] ?? '';

    if (!$Order_ID) {
        echo "<script>alert('缺少訂單編號'); history.back();</script>";
        exit;
    }

    // 先查訂單主鍵 id 和目前付款方式及繳款狀態
    $sql_old = "SELECT id, Payment_method FROM ordershop WHERE Order_ID = ?";
    $stmt_old = $link->prepare($sql_old);
    $stmt_old->bind_param("s", $Order_ID);
    $stmt_old->execute();
    $result_old = $stmt_old->get_result();
    $order = $result_old->fetch_assoc();
    $stmt_old->close();

    if (!$order) {
        echo "<script>alert('找不到訂單'); history.back();</script>";
        exit;
    }

    $ordershop_id = $order['id'];
    $payment_method = $order['Payment_method'];
     if ($payment_method === 'cod') {
    $Payment_status = '尚未繳款';}
   if ($payment_method === 'cc' || $Order_status === '結案') {
    $Payment_status = '已繳款';
    } else {
    $Payment_status = '尚未繳款';
    }

    // 更新訂單資料
    $sql_update = "UPDATE ordershop SET 
        Order_status = ?, 
        Payment_status = ?, 
        Ship_Date = ?, 
        Transport = ?, 
        shipping_zip = ? 
        WHERE Order_ID = ?";
    $stmt_update = $link->prepare($sql_update);
    if (!$stmt_update) {
        echo "<script>alert('資料庫錯誤，無法更新'); history.back();</script>";
        exit;
    }
    $stmt_update->bind_param("ssssss", $Order_status, $Payment_status, $Ship_Date, $Transport, $shipping_zip, $Order_ID);
    $success = $stmt_update->execute();
    $stmt_update->close();

    if (!$success) {
        echo "<script>alert('更新失敗'); history.back();</script>";
        exit;
    }

    // 如果訂單狀態是「商品退貨」，補回商品庫存
    if ($Order_status === '商品退貨') {
        $sql_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt_items = $link->prepare($sql_items);
        $stmt_items->bind_param("i", $ordershop_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        while ($item = $items_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];

            $sql_update_qty = "UPDATE product SET Sell_quantity = Sell_quantity + ? WHERE id = ?";
            $stmt_qty = $link->prepare($sql_update_qty);
            $stmt_qty->bind_param("is", $quantity, $product_id);
            $stmt_qty->execute();
            $stmt_qty->close();
        }
        $stmt_items->close();
    }

    // 成功後跳轉回訂單列表頁
    echo "<script>alert('訂單已更新'); window.location.href='Seller_Order.php?Order_ID=" . urlencode($Order_ID) . "';</script>";
    exit;

} else {
    echo "<script>alert('無效的請求'); history.back();</script>";
    exit;
}
?>
