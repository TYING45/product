<?php
include("sql_php.php");

$order_display_id = $_GET['Order_ID'] ?? null;
if (!$order_display_id) {
    echo "訂單編號錯誤";
    exit;
}

// Step 1: 查詢訂單主資料（先找出主鍵 id）
$sql_order = "SELECT * FROM ordershop WHERE Order_ID = ?";
$stmt_order = $link->prepare($sql_order);
$stmt_order->bind_param("s", $order_display_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();
if ($order_result->num_rows == 0) {
    echo "找不到該訂單資料";
    exit;
}
$order = $order_result->fetch_assoc();
$ordershop_id = $order['id']; // 用來查 order_items

// Step 2: 查會員資料
$sql_member = "SELECT * FROM member WHERE Member_ID = ?";
$stmt_member = $link->prepare($sql_member);
$stmt_member->bind_param("s", $order['Member_ID']);
$stmt_member->execute();
$member_result = $stmt_member->get_result();
$member = $member_result->fetch_assoc();

// Step 3: 查訂單商品明細
$sql_items = "SELECT * FROM order_items WHERE order_id = ?";
$stmt_items = $link->prepare($sql_items);
$stmt_items->bind_param("i", $ordershop_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$products = [];
$sellers_cache = [];

while ($item = $items_result->fetch_assoc()) {
    $product_id = $item['product_id'];
    $product_name = $item['product_name'];
    $price = $item['price'];
    $quantity = $item['quantity'];
    $seller_id = $item['Seller_ID'];

    if (!isset($sellers_cache[$seller_id])) {
        $sql_seller = "SELECT * FROM seller WHERE Seller_ID = ?";
        $stmt_seller = $link->prepare($sql_seller);
        $stmt_seller->bind_param("s", $seller_id);
        $stmt_seller->execute();
        $seller_result = $stmt_seller->get_result();
        $sellers_cache[$seller_id] = $seller_result->fetch_assoc();
    }

    $seller = $sellers_cache[$seller_id];
    $subtotal = $price * $quantity;

    $products[] = [
        'product_id' => $product_id,
        'product_name' => $product_name,
        'price' => $price,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
        'Seller' => $seller,
    ];
}

$shipping_fee = $order['shipping_fee'] ?? 0;
$total_amount = $order['total_price'] + $shipping_fee;
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>訂單詳情</title>
    <link rel="stylesheet" href="CSS/leftside.css">
    <link rel="stylesheet" href="CSS/topmenu.css">
    <link href="CSS/form.css" rel="stylesheet">
    <link href="CSS/UOrder.css" rel="stylesheet">
</head>
<body>

<div id="top-menu">
    <ul class="topmenu">
        <button onclick="toggleSidebar()" class="img-button"></button><li></li>
        <li><a href="https://secondhandshop.netlify.app/">網頁前端</a></li>
        <li><a href="login.php">登出</a></li>
    </ul>   
</div>

<div id="leftside">
    <ul class="menuleft">
        <li><a href="index.php">首頁</a></li>
        <li>
            <a href="#" onclick="toggleMenu(event)">商品管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="#">商品類別</a></li>
                <li><a href="Product.php">商品管理</a></li>
            </ul>
        </li>
        <li>
            <a href="#" onclick="toggleMenu(event)">會員管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Member.php">會員管理</a></li>
                <li><a href="Add_Member.php">新增會員</a></li>
            </ul>
        </li>
        <li>
            <a href="#" onclick="toggleMenu(event)">管理員管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Permissions.php">管理員管理</a></li>
                <li><a href="Add_permissions.php">新增管理員</a></li>
            </ul>
        </li>
        <li>
            <a href="#" onclick="toggleMenu(event)">賣家管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Seller.php">賣家管理</a></li>
                <li><a href="Add_Seller.php">新增賣家</a></li>
            </ul>
        </li>
        <li>
            <a href="#" onclick="toggleMenu(event)">訂單管理系統</a>
            <ul class="menuleft_hide">
                <li><a href="Order.php">訂單資料管理</a></li>
            </ul>
        </li>   
    </ul>
</div>

<main>
    <div class="order-container">
        <div class="order-header">訂購人資訊</div>
        <table>
            <tr><td class="label">訂單編號</td><td class="content"><?= htmlspecialchars($order['Order_ID']) ?></td></tr>
            <tr><td class="label">訂購日期</td><td class="content"><?= htmlspecialchars($order['Order_Date']) ?></td></tr>
            <tr><td class="label">訂購人姓名</td><td class="content"><?= htmlspecialchars($seller['Seller_Name'] ?? '未知') ?></td></tr>
            <tr><td class="label">聯絡電話</td><td class="content"><?= htmlspecialchars($seller['Phone'] ?? '未知') ?></td></tr>
            <tr><td class="label">e-mail</td><td class="content"><?= htmlspecialchars($seller['Email'] ?? '未知') ?></td></tr>
            <tr><td class="label">付款方式</td><td class="content"><?= htmlspecialchars($order['Payment_method'] ?? '尚未付款') ?></td></tr>
        </table>
    </div>

    <br><br>

    <div class="order-container">
        <div class="order-header">收件人資訊</div>
        <table>
            <tr><td class="label">收件人姓名</td><td class="content"><?= htmlspecialchars($order['billing_name']) ?></td></tr>
            <tr><td class="label">聯絡電話</td><td class="content"><?= htmlspecialchars($order['billing_phone']) ?></td></tr>
            <tr><td class="label">Email</td><td class="content"><?= htmlspecialchars($order['billing_email']) ?></td></tr>
            <tr><td class="label">收件地址</td><td class="content"><?= htmlspecialchars($order['shipping_address']) ?></td></tr>
            <tr><td class="label">取貨方式</td><td class="content"><?= htmlspecialchars($order['Transport'] ?? '宅配') ?></td></tr>
            <tr><td class="label">送貨時間</td><td class="content"><?= htmlspecialchars($order['Delivery_Time'] ?? '無') ?></td></tr>
            <tr><td class="label">備註</td><td class="content"><?= htmlspecialchars($order['Remarks'] ?? '無') ?></td></tr>
        </table>
    </div>

    <br><br>

    <div class="order-container">
        <table>
            <div class="order-header">訂單明細</div>
           <?php foreach ($products as $item): ?>
             <tr>
            <th class="label1">商品ID</th>
            <th class="label1">商品名稱</th>
            <th class="label1">價格</th>
            <th class="label1">金額</th>
            <th class="label1">數量</th>
            <th class="label1">小計</th>
        </tr>
        <tr>
            <td class="content"><?= htmlspecialchars($item['product_id']) ?></td>
            <td class="content"><?= htmlspecialchars($item['product_name']) ?></td>
            <td class="content"><?= htmlspecialchars(number_format($item['price'], 2)) ?></td>
            <td class="content"><?= htmlspecialchars($item['price']) ?></td>
            <td class="content"><?= htmlspecialchars($item['quantity']) ?></td>
            <td class="content"><?= htmlspecialchars(number_format($item['subtotal'], 2)) ?></td>
        </tr>
            <tr>
                <th>賣家ID</th>
                <td class="content1" colspan="5"><?= htmlspecialchars($item['Seller']['Seller_ID'] ?? '未知') ?></td>
            </tr>
            <tr>
                <th>賣家電話</th>
                <td class="content1" colspan="5"><?= htmlspecialchars($item['Seller']['Phone'] ?? '未知') ?></td>
            </tr>
            <tr><td colspan="6"><hr></td></tr>
            <?php endforeach; ?>

            <tr>
                <td class="label1">運費</td>
                <td class="content1" colspan="5"><?= htmlspecialchars($shipping_fee) ?></td>
            </tr>
            <tr>
                <td class="label1">總金額</td>
                <td class="content1" colspan="5"><?= htmlspecialchars($total_amount) ?></td>
            </tr>
        </table>
    </div>

    <br><br>
    <div class="order-container">
        <div class="order-header">訂單管理</div>
        <form method="post" action="UPdateOrder.php?id=<?= htmlspecialchars($order_display_id) ?>">
            <table>
                <tr>
                    <th class="label1">處理狀態</th>
                    <th class="label1">收款狀態</th>
                    <th class="label1">商品種類</th>
                    <th class="label1">發貨日期</th>
                    <th class="label1">收貨方式</th>
                    <th class="label1">宅配號碼</th>
                </tr>
                <tr>
                    <td class="content">
                        <select name="Order_status" required>
                            <?php
                            $statuses = ['未處理', '訂單處理中', '商品寄出', '商品退貨', '交易取消', '結案'];
                            foreach ($statuses as $status) {
                                $selected = ($order['Order_status'] ?? '') === $status ? 'selected' : '';
                                echo "<option value=\"$status\" $selected>$status</option>";
                            }
                            ?>
                        </select>
                    </td>
                  <td>
                <?php
                $payment_method = $order['Payment_method'] ?? '';
                $order_status = $order['Order_status'] ?? '';

                if ($payment_method === 'cc') {
                $Payment_status = '已繳款';
                } elseif ($payment_method === 'cod' && $order_status === '結案') {
                $Payment_status = '已繳款';
                } else {
                $Payment_status = '尚未繳款';
                }
                ?>
                </td>
                    <td class="content">多項</td>
                    <td class="content"><input type="date" name="Ship_Date" value="<?= htmlspecialchars($order['Ship_Date'] ?? '') ?>"></td>
                    <td class="content"><input type="text" name="Transport" value="<?= htmlspecialchars($order['Transport'] ?? '') ?>"></td>
                    <td class="content"><input type="text" name="shipping_zip" value="<?= htmlspecialchars($order['shipping_zip'] ?? '') ?>"></td>
                </tr>
            </table>
            <br>
            <button type="submit">更新</button>
        </form>
    </div>

    <br><br>
    <div class="button-container">
        <input type="button" value="返回表單" onclick="location.href='Order.php'">
    </div>
</main>

<script src="JS/leftside.js"></script>

</body>
</html>
