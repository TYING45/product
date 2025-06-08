<?php
include("sql_php.php");

// 優先用 POST 的 Order_ID，沒有才用 GET 的 id
$Order_ID = $_POST['Order_ID'] ?? $_GET['id'] ?? null;

if (!$Order_ID) {
    echo "缺少訂單編號";
    exit;
}

// 用 Order_ID 查訂單資料
$sql = "SELECT * FROM ordershop WHERE Order_ID = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $Order_ID);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "找不到訂單";
    exit;
}

$order_pk_id = $order['id']; // 用主鍵 id

// 如果有送出表單
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Payment_status = $_POST['Payment_status'] ?? '';
    $Ship_Date = $_POST['Ship_Date'] ?? null;
    $Transport = $_POST['Transport'] ?? '';
    $shipping_zip = $_POST['shipping_zip'] ?? '';
    $Order_status = $_POST['Order_status'] ?? '';

    // 判斷付款狀態
    $payment_method = $order['Payment_method'] ?? '';
    if ($payment_method === 'cc') {
        $Payment_status = '已繳款';
    } elseif ($payment_method === 'cod') {
        $Payment_status = '未繳款';
    } else {
        // 若前面沒判斷到，就用 POST 送過來的或資料庫原本的
        if (!$Payment_status) {
            $Payment_status = $order['Payment_status'] ?? '';
        }
    }

    // 更新訂單
    $update_sql = "UPDATE ordershop SET 
        Order_status = ?, 
        Ship_Date = ?, 
        Transport = ?, 
        shipping_zip = ?, 
        Payment_status = ?
        WHERE Order_ID = ?";
    $stmt_update = $link->prepare($update_sql);
    $stmt_update->bind_param("ssssss", $Order_status, $Ship_Date, $Transport, $shipping_zip, $Payment_status, $Order_ID);
    $stmt_update->execute();

    // 如果是退貨，補回商品庫存
    if ($Order_status === '商品退貨') {
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

    header("Location: Order.php?Order_ID=" . urlencode($Order_ID));
    exit;
}
?>

