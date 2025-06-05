<?php
session_start();
include("sql_php.php");

$seller_id = $_SESSION['Seller_ID'] ?? null;
if (!$seller_id) {
    echo "請先登入賣家帳號";
    exit;
}

$search_keyword = $_GET['search'] ?? '';

// 準備 SQL，篩選有該賣家商品的訂單
$sql = "
    SELECT DISTINCT o.Order_ID, o.Order_Date, o.Member_ID, o.Payment_status, o.Order_status, (o.total_price + IFNULL(o.shipping_fee, 0)) AS total_amount
    FROM ordershop o
    INNER JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.Seller_ID = ?
";

$params = [$seller_id];
$types = "s";

if ($search_keyword !== '') {
    $sql .= " AND (o.Order_ID LIKE CONCAT('%', ?, '%') OR o.Member_ID IN (SELECT Member_ID FROM member WHERE Member_Name LIKE CONCAT('%', ?, '%')))";
    $params[] = $search_keyword;
    $params[] = $search_keyword;
    $types .= "ss";
}

$sql .= " ORDER BY o.Order_Date DESC";

$stmt = $link->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "沒有資料可匯出";
    exit;
}

// 設定 header 下載 CSV 檔
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=seller_orders.csv');

// 開啟輸出流
$output = fopen('php://output', 'w');

// 寫入 UTF-8 BOM (避免 Excel 開啟亂碼)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV 標題列
fputcsv($output, ['訂單編號', '訂購日期', '會員編號', '付款狀態', '訂單狀態', '總金額']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['Order_ID'],
        $row['Order_Date'],
        $row['Member_ID'],
        $row['Payment_status'] ?? '尚未付款',
        $row['Order_status'] ?? '未處理',
        number_format($row['total_amount'], 2),
    ]);
}

fclose($output);
exit;
