<?php  
include("sql_php.php");  
include_once 'Uplaod_Menmber.html';  

if (isset($_POST['output'])) {
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

            // 跳過標題行
            fgetcsv($csvFile);

            while (($row = fgetcsv($csvFile)) !== FALSE) {
                $SellerID = $row[0];
                $Seller_name = $row[1]; 
                $Company = $row[2];  
                $username = $row[3];
                $password = $row[4];
                $Phone = $row[5];
                $Email = $row[6];
                $Address = $row[7];
                $role = $row[8];

                echo "$SellerID, $Seller_name, $Company, $username, $password, $Phone, $Email, $Address, $role <br>";

                // 查詢是否存在
                $prevQuery = "SELECT * FROM `seller` WHERE `SellerID` = ?";
                $stmt = $link->prepare($prevQuery);
                $stmt->bind_param("s", $SellerID);
                $stmt->execute();
                $prevResult = $stmt->get_result();
                $stmt->close();

                if ($prevResult->num_rows > 0) {
                    // 更新資料
                    $updateQuery = "UPDATE `seller` SET `Seller_name`=?, `Company`=?, `username`=?, `password`=?, `Phone`=?, `Email`=?, `Address`=?, `role`=? WHERE `SellerID`=?";
                    $stmt = $link->prepare($updateQuery);
                    $stmt->bind_param("sssssssss", $Seller_name, $Company, $username, $password, $Phone, $Email, $Address, $role, $SellerID);
                    if (!$stmt->execute()) {
                        die("更新錯誤: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    // 插入新資料
                    $insertQuery = "INSERT INTO `seller`(`SellerID`, `Seller_name`, `Company`, `username`, `password`, `Phone`, `Email`, `Address`, `role`) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $link->prepare($insertQuery);
                    $stmt->bind_param("sssssssss", $SellerID, $Seller_name, $Company, $username, $password, $Phone, $Email, $Address, $role);
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
    header("Location: Seller.php");
    exit();
}
?>
