<?php
include("sql_php.php");

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "❌ 錯誤：缺少訂單主鍵 id";
    exit;
}

// 查詢訂單資料
$sql = "SELECT * FROM ordershop WHERE id = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "❌ 找不到訂單";
    exit;
}

$order = $result->fetch_assoc();

// 接收表單資料
$order_status = $_POST['Order_status'] ?? '';
$ship_date = $_POST['Ship_Date'] ?? null;
$transport = $_POST['Transport'] ?? '';
$shipping_zip = $_POST['shipping_zip'] ?? '';

// 自動判斷付款狀態（cc = 已繳款, cod = 未繳款）
$payment_method = $order['Payment_method'] ?? '';
$payment_status = ($payment_method === 'cc') ? '已繳款' : '未繳款';

// 更新 ordershop 訂單主資料
$update_sql = "UPDATE ordershop SET 
    Order_status = ?, 
    Ship_Date = ?, 
    Transport = ?, 
    shipping_zip = ?, 
    Payment_status = ?
    WHERE id = ?";

$stmt_update = $link->prepare($update_sql);
$stmt_update->bind_param("sssssi", $order_status, $ship_date, $transport, $shipping_zip, $payment_status, $id);
$stmt_update->execute();

// 如果狀態為「商品退貨」或「訂單處理中」，更新庫存
if ($order_status === '商品退貨' || $order_status === '訂單處理中') {
    $sql_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $stmt_items = $link->prepare($sql_items);
    $stmt_items->bind_param("i", $id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();

    while ($item = $items_result->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        // 將 Sell_quantity 增加
        $update_qty = "UPDATE product SET Sell_quantity = Sell_quantity + ? WHERE Product_ID = ?";
        $stmt_qty = $link->prepare($update_qty);
        $stmt_qty->bind_param("is", $quantity, $product_id);
        $stmt_qty->execute();
    }
}

echo "<script>alert('訂單更新成功'); location.href='Order.php';</script>";
?>
