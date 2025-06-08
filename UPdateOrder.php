<?php
include("sql_php.php");

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "缺少 id";
    exit;
}

// 查訂單主資料
$sql = "SELECT * FROM ordershop WHERE id = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "找不到訂單";
    exit;
}

$order_id = $order['Order_ID'];

// 處理更新請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_status = $_POST['Order_status'] ?? '';
    $ship_date = $_POST['Ship_Date'] ?? '';
    $transport = $_POST['Transport'] ?? '';
    $shipping_zip = $_POST['shipping_zip'] ?? '';

    // 根據付款方式判斷付款狀態
    $payment_method = $order['Payment_method'] ?? '';
    $payment_status = ($payment_method === 'cc') ? '已繳款' : '未繳款';

    // 更新訂單資料
    $update_sql = "UPDATE ordershop SET 
        Order_status = ?, 
        Ship_Date = ?, 
        Transport = ?, 
        shipping_zip = ?, 
        Payment_status = ?
        WHERE id = ?";
    $stmt = $link->prepare($update_sql);
    $stmt->bind_param("sssssi", $order_status, $ship_date, $transport, $shipping_zip, $payment_status, $id);
    $stmt->execute();

    // 如果是退貨，補回商品庫存
    if ($order_status === '商品退貨') {
        $sql_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt_items = $link->prepare($sql_items);
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        while ($item = $items_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];

            $sql_update_quantity = "UPDATE product SET Sell_quantity = Sell_quantity + ? WHERE id = ?";
            $stmt_update = $link->prepare($sql_update_quantity);
            $stmt_update->bind_param("ii", $quantity, $product_id);
            $stmt_update->execute();
        }
    }

    // 更新成功後導回訂單頁
    header("Location: Order.php?Order_ID=" . urlencode($order_id));
    exit;
}
?>
