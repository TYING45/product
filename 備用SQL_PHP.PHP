<?php
$host = '34.80.76.25';
$port = 3306;
$dbname = 'testdb';
$username = 'Ken';
$password = '11111111';

// 建立連線
$conn = new mysqli($host, $username, $password, $dbname, $port);

// 檢查連線
if ($conn->connect_error) {
    die("❌ 連線失敗: " . $conn->connect_error);
}
echo "✅ 成功連接資料庫<br>";
$conn->close();
?>

<?php
$host = 'localhost';
$user ='root';
$password = '';
$name = 'product';
$link=mysqli_connect($host,$user,$password,$name);
if(!mysqli_set_charset($link, 'utf8')){
    echo "不正確連接資料庫</br>" . mysqli_connect_error();
}
?>