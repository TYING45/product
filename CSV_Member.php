<?php 
include("sql_php.php"); 

$query = $link->query("SELECT * FROM `member`");

if ($query->num_rows > 0) {
    $delimiter = ",";
    $fileName = '會員資料.csv';

    // 設定檔案下載標
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$fileName\"");

    // 輸出 BOM 頭防止 Excel 亂碼
    echo "\xEF\xBB\xBF";

    // 開始輸出 CSV
    $fp = fopen('php://output', 'w');

    // 輸出欄位名稱
    $fields = ['Member_ID', 'Member_name','password', 'Email', 'Phone', 'Address'];
    $fields = array_map(fn($field) => mb_convert_encoding($field, "UTF-8", "auto"), $fields);
    fputcsv($fp, $fields, $delimiter);

    while ($row = $query->fetch_assoc()) {
        $lineData = [
            $row['Member_ID'],
            $row['Member_name'],
            $row['password'],
            $row['Email'],
            $row['Phone'],
            $row['Address']
        ];
        fputcsv($fp, $lineData, $delimiter);
    }

    fclose($fp);
    exit;
}
?>
