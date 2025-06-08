<?php 
include("sql_php.php"); 

$query = $link->query("SELECT * FROM `product`");

if ($query->num_rows > 0) {
    $delimiter = ",";
    $fileName = 'product.csv';

    // 一定要在輸出任何內容前設定 Header
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$fileName\"");

    // 輸出 BOM 防止 Excel 顯示亂碼
    echo "\xEF\xBB\xBF";

    $fp = fopen('php://output', 'w'); // 開始輸出

    // 欄位標題
    $fields = ['Product_ID','Seller_ID', 'Product_name','Type', 'price', 'Product_introduction', 'quantity', 'Image', 'Remark','Sell_quantity'];
    fputcsv($fp, $fields, $delimiter);

    // 每筆資料
    while ($row = $query->fetch_assoc()) {
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
}
?>
