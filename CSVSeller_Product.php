<?php
session_start();
include("sql_php.php");

$SellerID = $_SESSION['Seller_ID'] ?? null;
if (!$SellerID) {
    die("請先登入賣家帳號");
}

// 設定編碼避免亂碼
$link->set_charset("utf8mb4");

// 查詢該賣家商品
$query = $link->prepare("SELECT * FROM `product` WHERE Seller_ID = ?");
$query->bind_param("s", $SellerID);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $delimiter = ",";
    $fileName = 'product_' . date('Ymd_His') . '.csv';

    // 輸出 CSV header
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$fileName\"");

    // 防止 BOM 亂碼
    echo "\xEF\xBB\xBF";

    $fp = fopen('php://output', 'w');

    // CSV 欄位
    $fields = ['Product_ID','Seller_ID', 'Product_name','Type', 'price' ,'Product_introduction', 'quantity', 'Image', 'Remark','Sell_quantity'];
    fputcsv($fp, $fields, $delimiter);

    while ($row = $result->fetch_assoc()) {
        $lineData = [
            $row['Product_ID'],
            $row['Seller_ID'],
            $row['Product_name'],
            $row['Type'],
            $row['price'],
            $row['Product_introduction'],
            $row['quantity'],
            $row['Image'],
            $row['Remark'],
            $row['Sell_quantity']
        ];
        fputcsv($fp, $lineData, $delimiter);
    }

    fclose($fp);
    exit;
} else {
    echo "查無資料可匯出。";
}
?>
