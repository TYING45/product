<?php
include("sql_php.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Order_ID = $_POST['Order_ID'] ?? $_GET['id'] ?? null;
    $Order_status = $_POST['Order_status'] ?? '';
    $Ship_Date = $_POST['Ship_Date'] ?? null;
    $Transport = $_POST['Transport'] ?? '';
    $shipping_zip = $_POST['shipping_zip'] ?? '';

    if ($Order_ID) {
        // 取得舊的訂單狀態和主鍵 id
        $sql_old = "SELECT id, Order_status FROM ordershop WHERE Order_ID = ?";
        $stmt_old = $link->prepare($sql_old);
        $stmt_old->bind_param("s", $Order_ID);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        $order = $result_old->fetch_assoc();
        $ordershop_id = $order['id'] ?? null;
        $old_status = $order['Order_status'] ?? '';
        $stmt_old->close();

        // 更新訂單資料
        $sql = "UPDATE ordershop 
                SET Order_status = ?, Ship_Date = ?, Transport = ?, shipping_zip = ?
                WHERE Order_ID = ?";
        $stmt = $link->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssss", $Order_status, $Ship_Date, $Transport, $shipping_zip, $Order_ID);
            $success = $stmt->execute();
            $stmt->close();

            // 若狀態有改變，且涉及「退貨」或「處理中」
            if ($success && $ordershop_id && $old_status !== $Order_status) {
                $sql_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
                $stmt_items = $link->prepare($sql_items);
                $stmt_items->bind_param("i", $ordershop_id);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();

                while ($item = $result_items->fetch_assoc()) {
                    $product_id = $item['product_id'];
                    $quantity = (int)$item['quantity'];

                    if ($Order_status === '商品退貨') {
                        // 減少 sell_quantity（但不得 < 0）
                        $link->query("UPDATE product 
                                      SET sell_quantity = GREATEST(sell_quantity - $quantity, 0) 
                                      WHERE Product_ID = '$product_id'");
                    } elseif ($Order_status === '訂單處理中') {
                        // 增加 sell_quantity
                        $link->query("UPDATE product 
                                      SET sell_quantity = sell_quantity + $quantity 
                                      WHERE Product_ID = '$product_id'");
                    }
                }

                $stmt_items->close();
            }

            echo "<script>alert('訂單資料已更新'); window.location.href='Order.php';</script>";
        } else {
            echo "<script>alert('資料庫連線失敗'); history.back();</script>";
        }
    } else {
        echo "<script>alert('缺少訂單編號'); history.back();</script>";
    }
} else {
    echo "<script>alert('無效的請求'); history.back();</script>";
}
?>
