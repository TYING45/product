<?php  
include("sql_php.php");  
include_once 'Uplaod_Menmber.html';  

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
                $SellerID = $row_result[0];
                $聯絡人 = $row_result[1];
                $公司 = $row_result[2];  
                $Seller_username = $row_result[3];
                $Seller_password = $row_result[4];
                $phone = $row_result[5];
                $email = $row_result[6];
                $地址 = $row_result[7];

                echo "$Seller_ID , $聯絡人, $公司, $Seller_username, $Seller_password,$phone,$email,$Address <br>";

                // 查詢是否存在
                $prevQuery = "SELECT * FROM `seller` WHERE `Seller_ID` = ?";
                $stmt = $link->prepare($prevQuery);
                $stmt->bind_param("s", $Seller_ID);
                $stmt->execute();
                $prevResult = $stmt->get_result();
                $stmt->close();

                if ($prevResult->num_rows > 0) {
                    // 如果有更新資料
                    $sqli_query = "UPDATE `seller` SET `Seller_name`=?,`Company`= ?, `username`=?, `password`=?, `Phone`=?, `Email`=?, `Address`=? WHERE `SellerID`=?";
                    $stmt = $link->prepare($sqli_query);
                    $stmt->bind_param("ssssssss", $Seller_name, $Company, $username ,$password,$Phone,$Email,$Address ,$SellerID);
                    if (!$stmt->execute()) {
                        die("更新錯誤: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    // 如果沒有就插入新資料
                    $sqli_query = "INSERT INTO `seller`(`SellerID`, `Seller_name`, `Company`, `username`, `password`, `Address`, `phone`, `Address`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $link->prepare($sqli_query);
                    $stmt->bind_param("ssssssss", $SellerID , $Seller_name, $Company, $Seller_username, $Seller_password,$Phone,$Email,$Address);
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

header("Location: Seller.php");
exit();
?>
