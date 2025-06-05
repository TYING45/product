<?php 
include("sql_php.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = mysqli_real_escape_string($link, $_POST['username']);
        $password = mysqli_real_escape_string($link, $_POST['password']);

        if (empty($username)) {
            header("Location: login.php?error=請輸入帳號");
            exit();
        } else if (empty($password)) {
            header("Location: login.php?error=請輸入密碼");
            exit();
        } else {
            // 查 Admin 表
            $query_user = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
            $result_user = mysqli_query($link, $query_user);

            // 查 Seller 表
            $query_seller = "SELECT * FROM seller WHERE username='$username' AND password='$password'";
            $result_seller = mysqli_query($link, $query_seller);

            if (mysqli_num_rows($result_user) === 1) {
                $row = mysqli_fetch_assoc($result_user);
                $_SESSION['username'] = $row['username'];
                $_SESSION['Admin_ID'] = $row['Admin_ID'];
                $_SESSION['role'] = 'admin';
                header("Location: index.php"); // 一般使用者主頁
                exit();
            } elseif (mysqli_num_rows($result_seller) === 1) {
                $row = mysqli_fetch_assoc($result_seller);
                $_SESSION['username'] = $row['username'];
                $_SESSION['Seller_ID'] = $row['Seller_ID'];
                $_SESSION['role'] = 'seller';
                header("Location: Seller_index.php"); // 賣家主頁
                exit();
            } else {
                header("Location: login.php?error=帳號或密碼錯誤");
                exit();
            }
        }
    } else {
        header("Location: login.php?error=表單不完整");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>管理登入畫面</title>
	<link rel="stylesheet" type="text/css" href="CSS/sytle.css">
</head>

<body>
     <form method="post" action="">
     	<h2>後端登入</h2>
     	<?php if (isset($_GET['error'])) { ?>
     		<p class="error"><?php echo $_GET['error']; ?></p>
     	<?php } ?>
     	<label>帳號</label>
     	<input type="text" name="username" placeholder="輸入帳號"><br>

     	<label>密碼</label>
     	<input type="password" name="password" placeholder="輸入密碼"><br>
		<a href="User_add.php">註冊</a>
     	<button type="submit">登入</button>
     </form>
</body>
</html>