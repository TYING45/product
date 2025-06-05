<?php 
include("sql_php.php"); 

$query = $link->query("SELECT * FROM `product`");

if ($query->num_rows > 0) {
    $delimiter = ",";
    $fileName = 'product.csv';

    echo "\xEF\xBB\xBF";
    $fp = fopen('php://output', 'w');//防止亂碼

    $fields = ['Product_ID','Seller_ID', 'Product_name','Type', 'price' ,'Product_introduction', 'quantity', 'Image', 'Remark','Sell_quantity'];
    fputcsv($fp, $fields, $delimiter);
    while ($row = $query->fetch_assoc()) {
        $lineData = [
            $row['Product_ID'],
            $row['Seller_ID'],
            $row['Product_name'],
            $row['Type'],
            $row['price'],
            $row['Product_introduction'],
            $row['quantity'],
            $row['Image'].
            $row['Remark'],
            $row['Sell_quantity']
        ];
        fputcsv($fp, $lineData, $delimiter);
    }
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=$fileName");
    fpassthru($fp);//輸出
    fclose($fp);
    exit;
}
?>
