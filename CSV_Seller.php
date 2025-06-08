<?php 
include("sql_php.php"); 

$query = $link->query("SELECT * FROM `seller`");

if ($query->num_rows > 0) {
    $delimiter = ",";
    $fileName = '賣家資料.csv';
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=$fileName");


    echo "\xEF\xBB\xBF";

    $fp = fopen('php://output', 'w');

    // 寫入欄位名稱
    $fields = ['Seller_ID', 'Seller_name', 'Company','username', 'password', ,'Email' 'Phone','Address'];
    fputcsv($fp, $fields, $delimiter);

    // 寫入資料
    while ($row = $query->fetch_assoc()) {
        $lineData = [
            $row['Seller_ID'],
            $row['Seller_name'],
            $row['Company'],
            $row['username'],
            $row['password'],
            $row['Email'],
            $row['Phone'],
            $row['Address']
        ];
        fputcsv($fp, $lineData, $delimiter);
    }

    fclose($fp); // 關閉輸出
    exit;
}
?>
