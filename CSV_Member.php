<?php 
include("sql_php.php"); 

$query = $link->query("SELECT * FROM `member`");

if ($query->num_rows > 0) {
    $delimiter = ",";
    $fileName = '會員.csv';

    echo "\xEF\xBB\xBF";
    $fp = fopen('php://output', 'w');//防止亂碼

    $fields = ['Member_ID', 'Member_name', 'username', 'password', 'Email', 'Phone', 'Address'];
    $fields = array_map(fn($field) => mb_convert_encoding($field, "UTF-8", "auto"), $fields);
    fputcsv($fp, $fields, $delimiter);
    while ($row = $query->fetch_assoc()) {
        $lineData = [
            $row['Member_ID'],
            $row['Member_name'],
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
