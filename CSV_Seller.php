<?php 
include("sql_php.php"); 

$query = $link->query("SELECT * FROM `seller`");

if ($query->num_rows > 0) {
    $delimiter = ",";
    $fileName = '賣家資料.csv';

    echo "\xEF\xBB\xBF";
    $fp = fopen('php://output', 'w');//防止亂碼

    $fields = ['Seller_ID', 'Seller_name', 'Company','username', 'password', 'Email', 'Phone', 'Address'];
    fputcsv($fp, $fields, $delimiter);
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
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=$fileName");
    fpassthru($fp);//輸出
    fclose($fp);
    exit;
}
?>
