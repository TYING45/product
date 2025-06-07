<?php  

include("sql_php.php");  
include_once 'Upload_Menmber.html';  

if(isset($_POST['output'])) {
    $csvMimes = array(
        'text/x-comma-separated-values', 'text/comma-separated-values',
        'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv',
        'text/x-csv', 'text/csv', 'application/csv', 'application/excel',
        'application/vnd.msexcel', 'text/plain'
    );

    if (!empty($_FILES["fileUpload"]["name"]) && in_array($_FILES["fileUpload"]["type"], $csvMimes)) {
        if (is_uploaded_file($_FILES["fileUpload"]["tmp_name"])) {
            $csvFile = fopen($_FILES["fileUpload"]["tmp_name"], "r");

            if (!$csvFile) {
                die("無法讀取 CSV 檔案");
            }
            fgetcsv($csvFile);
            fgetcsv($csvFile);

            while (($row_result = fgetcsv($csvFile)) !== FALSE) {
                $Member_ID = $row_result[0];
                $Member_name = $row_result[1];
                $password = $row_result[2];
                $Email = $row_result[3];
                $Phone = $row_result[4];
                $Address = $row_result[4];

                echo "$Member_ID , $Member_name, $password, $Email, $Phone,$Address <br>";

                // 查詢是否存在
                $prevQuery = "SELECT * FROM `member` WHERE `Member_ID` = ?";
                $stmt = $link->prepare($prevQuery);
                $stmt->bind_param("s", $Member_ID);
                $stmt->execute();
                $prevResult = $stmt->get_result();
                $stmt->close();

                if ($prevResult->num_rows > 0) {
                    // 如果有更新資料
                    $sqli_query = "UPDATE `member` SET `Member_name`=?, `password`=?, `email`=?, `phone`=?, `Address`=? WHERE `Member_ID`=?";
                    $stmt = $link->prepare($sqli_query);
                    $stmt->bind_param("ssssss", $Member_name, $password, $Email, $Phone, $Address, $Member_ID);
                    if (!$stmt->execute()) {
                        die("更新錯誤: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    // 如果沒有就插入新資料
                    $sqli_query = "INSERT INTO `member`(`Member_ID`, `Member_name`, `username`, `password`, `Email`, `Phone`, `Address`) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $link->prepare($sqli_query);
                    $stmt->bind_param("ssssss", $Member_ID, $Member_name, $password, $Email, $Phone, $Address);
                    if (!$stmt->execute()) {
                        die("插入錯誤: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }
            fclose($csvFile);
        } else {
            die("檔案上傳失敗");
        }
    } else {
        die("請上傳 CSV 檔案");
    }
}

header("Location: Member.php");
exit();
?>
