include("sql_php.php");

function generateRandomMemberID($link) {
    do {
        $random_part = bin2hex(random_bytes(8));
        $random_id = 'M' . substr($random_part, 0, 15);
        $check = $link->prepare("SELECT 1 FROM member WHERE Member_ID = ?");
        $check->bind_param("s", $random_id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    $check->close();
    return $random_id;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add") {
    if (!empty($_POST["Member_name"]) && !empty($_POST["password"]) && !empty($_POST["Email"]) && !empty($_POST["Phone"])) {

        $new_id = generateRandomMemberID($link);

        $hashed_password = password_hash($_POST["password"], PASSWORD_DEFAULT);

        $stmt = $link->prepare("INSERT INTO `member`(`Member_ID`, `Member_name`, `password`, `Email`, `Phone`, `Address`) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $new_id, $_POST["Member_name"], $hashed_password,
                          $_POST["Email"], $_POST["Phone"], $_POST["Address"]);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>alert('新增成功！會員ID：$new_id'); window.location.href = 'Member.php';</script>";
        } else {
            echo "<script>alert('資料新增失敗！');</script>";
        }

        $stmt->close();
        $link->close();
    } else {
        echo "<script>alert('有欄位未填寫');</script>";
    }
}
