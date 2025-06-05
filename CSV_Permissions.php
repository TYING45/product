<?php 
include("sql_php.php"); 

// 只選取 role 為 admin 的使用者
$query = $link->prepare("SELECT * FROM `admin` WHERE `role` = ?");
$role = 'admin';
$query->bind_param("s", $role);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $delimiter = ",";
    $fileName = "權限資料.csv";

    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // 輸出 UTF-8 BOM 防止 Excel 亂碼
    echo "\xEF\xBB\xBF";

    $fp = fopen('php://output', 'w');

    // 表頭欄位
    $fields = ['Admin_ID', 'Admin_name', 'username', 'password', 'Email', 'Phone'];
    fputcsv($fp, $fields, $delimiter);

    // 資料列
    while ($row = $result->fetch_assoc()) {
        $lineData = [
            $row['Admin_ID'],
            $row['Admin_name'],
            $row['username'],
            $row['password'],
            $row['Email'],
            $row['Phone'],
        ];
        fputcsv($fp, $lineData, $delimiter);
    }

    fclose($fp);
    exit;
} else {
    echo "查無 role = 'admin' 的資料可匯出。";
}
?>
