<?php  
include("sql_php.php");  
session_start();  

// 測試資料（可換成從 Session 或 GET/POST 拿資料） 
$Order_ID = "A1977"; 
$Order_name = "洗衣粉"; 
$Product_ID = "P001"; 
$Member_ID = "M001"; 
$Subscriber = "柯小勝"; 
$Seller_ID = "S001"; 
$Payment_method = "信用卡"; 
$Payment_status = "宅配"; 
$Amount = 1; 
$Order_cash = 599; 
$Transport = "Delivery"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {  
    $Order_ID = $_POST['Order_ID'];  
    $Order_name = $_POST['Order_name'];  
    $Product_ID = $_POST['Product_ID'];  
    $Member_ID = $_POST['Member_ID'];  
    $Subscriber = $_POST['Subscriber'];  
    $Seller_ID = $_POST['Seller_ID'];  
    $Payment_method = $_POST['Payment_method'];  
    $Amount = $_POST['Amount'];  
    $Order_cash = $_POST['Order_cash'];  

    // SQL with NOW() for Order_date
    $sql = "INSERT INTO `ordershop` 
            (`Order_ID`, `Order_name`, `Product_ID`, `Member_ID`, `Subscriber`, `Seller_ID`, `Payment_method`, `Amount`, `Order_cash`, `Order_date`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";  

    $stmt = $link->prepare($sql);  
    $stmt->bind_param("sssssssss", 
        $Order_ID, $Order_name, $Product_ID, $Member_ID, 
        $Subscriber, $Seller_ID, $Payment_method, $Amount, $Order_cash
    );  

    if ($stmt->execute()) {  
        echo "<script>alert('Order submitted successfully!');</script>";  
    } else {  
        echo "<script>alert('Submission failed: " . $stmt->error . "');</script>";  
    }  

    $stmt->close();  
}  

// 顯示目前時間（前端顯示用，不送到資料庫）
$order_date_display = date("Y-m-d H:i:s");
?>  

<!DOCTYPE html>  
<html>  
<head>  
    <meta charset="UTF-8">  
    <title>Checkout System</title>  
    <link rel="stylesheet" type="text/css" href="CSS/style.css">  
</head>  

<body>  
    <form method="post" action="">  
        <h2>Checkout</h2>  

        <label>Order ID</label>  
        <input type="text" name="Order_ID" value="<?= $Order_ID ?>" readonly><br>  

        <label>Order Name</label>  
        <input type="text" name="Order_name" value="<?= $Order_name ?>" readonly><br>  

        <label>Product ID</label>  
        <input type="text" name="Product_ID" value="<?= $Product_ID ?>" readonly><br>  

        <label>Member ID</label>  
        <input type="text" name="Member_ID" value="<?= $Member_ID ?>" readonly><br>  

        <label>Subscriber Name</label>  
        <input type="text" name="Subscriber" value="<?= $Subscriber ?>" readonly><br>  

        <label>Seller ID</label>  
        <input type="text" name="Seller_ID" value="<?= $Seller_ID ?>" readonly><br>  

        <label>Payment Method</label>  
        <input type="text" name="Payment_method" value="<?= $Payment_method ?>" readonly><br>  

        <label>Quantity</label>  
        <input type="text" name="Amount" value="<?= $Amount ?>" readonly><br>  

        <label>Total Price</label>  
        <input type="text" name="Order_cash" value="<?= $Order_cash ?>" readonly><br>  

        <label>Order Date</label>  
        <input type="text" value="<?= $order_date_display ?>" readonly><br>  

        <button type="submit">Checkout</button>  
    </form>  
</body>  
</html>
