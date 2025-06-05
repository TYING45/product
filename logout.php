<?php
session_start();          
session_unset();          // 清除所有 session
header("Location: login.php"); // 導回登入頁面
exit();