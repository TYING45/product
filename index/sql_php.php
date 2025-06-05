
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