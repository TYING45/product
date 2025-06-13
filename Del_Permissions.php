<?php
include("sql_php.php");

if (isset($_POST["action"]) && ($_POST["action"] == "delete")) {
    $count_query = "SELECT COUNT(*) FROM `admin`";
    $result = $link->query($count_query);
    $row = $result->fetch_row();
    $admin_count = $row[0];

    if ($admin_count <= 1) {
        echo "<script>alert('無法刪除，至少需要一位管理員。'); window.location.href='Permissions.php';</script>";
        exit();
    }

    $sqli_query = "DELETE FROM `admin` WHERE Admin_ID = ?";
    $stmt = $link->prepare($sqli_query);
    $stmt->bind_param("s", $_POST["Admin_ID"]);

    if ($stmt->execute()) {
        header("Location: Permissions.php");
        exit();
    } else {
        echo "刪除失敗：" . $stmt->error;
    }
}

// 從資料庫獲取要刪除的資料
if (isset($_GET["id"])) {
    $sql_select = "SELECT Admin_ID, Admin_name FROM `admin` WHERE `Admin_ID` = ?";
    $stmt = $link->prepare($sql_select);
    $stmt->bind_param("s", $_GET["id"]);
    $stmt->execute();
    $stmt->bind_result($Admin_ID, $Admin_name);
    $stmt->fetch();
} else {
    echo "未提供編號！";
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <link href="CSS/Add_Del.css" rel="stylesheet" type="text/css">
    <meta charset="UTF-8">
    <title>管理員資料管理</title>
</head>
<body>
    <h1 align="center">刪除管理員資料</h1>
    <h4 align="center">是否刪除此資料嗎？</h4>
    <form action="" method="post" name="formDel" id="formDel">
        <table align="center">
            <tr><th>欄位</th><th>資料</th></tr>
            <tr><td>管理員ID</td><td><input type="text" name="Admin_ID" id="Admin_ID" value="<?php echo htmlspecialchars($Admin_ID); ?>" readonly></td></tr>
            <tr><td>姓名</td><td><input type="text" name="Admin_name" id="name" value="<?php echo htmlspecialchars($Admin_name); ?>" readonly></td></tr>
            <tr>
                <td colspan="2" align="center">
                    <input name="action" type="hidden" value="delete">
                    <input type="button" value="取消" onclick="location.href='Product.php'">
                    <input type="submit" name="button0" id="button0" value="刪除">
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
