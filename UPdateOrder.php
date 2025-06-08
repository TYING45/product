<?php
include("sql_php.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = $_POST['Order_ID'];
    $order_status = $_POST['Order_status'];
    $ship_date = $_POST['Ship_Date'];
    $transport = $_POST['Transport'];
    $shipping_zip = $_POST['shipping_zip'];
    $payment_status = $_POST['Payment_status']; // 手動選已繳款/未繳款

    // 從付款方式自動判定
    $sql_payment = "SELECT Payment_method FROM ordershop WHERE Order_ID = ?";
    $stmt = $link->prepare($sql_payment);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $payment_method = $row['Payment_method'];

    if ($payment_method === 'cc') {
        $payment_status = '已繳款';
    } elseif ($payment_method === 'cod') {
        $payment_status = '未繳款';
    }

    // 查詢舊的訂單狀態及 DB 用的訂單 ID
    $sql_old_status = "SELECT id, Order_status FROM ordershop WHERE Order_ID = ?";
    $stmt = $link->prepare($sql_old_status);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "找不到訂單";
        exit;
    }
    $order_row = $result->fetch_assoc();
    $order_db_id = $order_row['id'];
    $old_status = $order_row['Order_status'];

    // 狀態改變時調整 Sell_quantity
    if ($order_status !== $old_status) {
        $sql_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt_items = $link->prepare($sql_items);
        $stmt_items->bind_param("i", $order_db_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        while ($item = $items_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];

            if ($order_status === '商品退貨') {
                $sql_update_sell = "UPDATE product SET Sell_quantity = Sell_quantity - ? WHERE id = ?";
            } elseif ($order_status === '訂單處理中' || $order_status === '商品寄出') {
                $sql_update_sell = "UPDATE product SET Sell_quantity = Sell_quantity + ? WHERE id = ?";
            } else {
                continue;
            }

            $stmt_update = $link->prepare($sql_update_sell);
            $stmt_update->bind_param("ii", $quantity, $product_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }

    // 更新訂單主資料
    $sql = "UPDATE ordershop 
            SET Order_status = ?, Ship_Date = ?, Transport = ?, shipping_zip = ?, Payment_status = ? 
            WHERE Order_ID = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ssssss", $order_status, $ship_date, $transport, $shipping_zip, $payment_status, $order_id);

    if ($stmt->execute()) {
        header("Location: Order.php?success=1");
        exit();
    } else {
        echo "更新失敗：" . $stmt->error;
    }
}
?>
