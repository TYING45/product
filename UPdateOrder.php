<?php
include("sql_php.php");

$order_display_id = $_GET['Order_ID'] ?? null;

if (!$order_display_id) {
    echo "缺少 Order_ID";
    exit;
}

// 查訂單主資料
$sql = "SELECT * FROM ordershop WHERE Order_ID = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $order_display_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "找不到訂單";
    exit;
}

$order_id = $order['Order_ID'];
$order_pk_id = $order['id']; // 主鍵 id，用於關聯 order_items

// 處理更新請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_status = $_POST['Order_status'] ?? '';
    $ship_date = $_POST['Ship_Date'] ?? '';
    $transport = $_POST['Transport'] ?? '';
    $shipping_zip = $_POST['shipping_zip'] ?? '';

    // 判斷付款狀態，假設付款方式為 cc 時表示已繳款，cod 表示未繳款
    $payment_method = $order['Payment_method'] ?? '';
    if ($payment_method === 'cc') {
        $payment_status = '已繳款';
    } elseif ($payment_method === 'cod') {
        $payment_status = '未繳款';
    } else {
        $payment_status = $order['Payment_status'] ?? '未繳款';
    }

    // 更新訂單資料
    $update_sql = "UPDATE ordershop SET 
        Order_status = ?, 
        Ship_Date = ?, 
        Transport = ?, 
        shipping_zip = ?, 
        Payment_status = ?
        WHERE Order_ID = ?";
    $stmt_update = $link->prepare($update_sql);
    $stmt_update->bind_param("ssssss", $order_status, $ship_date, $transport, $shipping_zip, $payment_status, $order_id);
    $stmt_update->execute();

    // 如果是退貨，補回商品庫存
    if ($order_status === '商品退貨') {
        $sql_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt_items = $link->prepare($sql_items);
        $stmt_items->bind_param("i", $order_pk_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        while ($item = $items_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];

            $sql_update_quantity = "UPDATE product SET Sell_quantity = Sell_quantity + ? WHERE id = ?";
            $stmt_update_quantity = $link->prepare($sql_update_quantity);
            $stmt_update_quantity->bind_param("ii", $quantity, $product_id);
            $stmt_update_quantity->execute();
        }
    }

    // 更新成功後導回訂單頁
    header("Location: Order.php?Order_ID=" . urlencode($order_id));
    exit;
}
?>
