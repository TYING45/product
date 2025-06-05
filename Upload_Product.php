<?php  
include("sql_php.php");  
include_once ('Upload_Product.html');  

function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9_\.-]/', '_', strtolower($filename));
}

function handle_image($imageField) {
    $uploadDir = "uploads/";
    $csvImageDir = "uploads_csv/";

    // 如果是網址
    if (filter_var($imageField, FILTER_VALIDATE_URL)) {
        $imageData = file_get_contents($imageField);
        $imageName = basename(parse_url($imageField, PHP_URL_PATH));
        $imageName = sanitize_filename($imageName);
        file_put_contents($uploadDir . $imageName, $imageData);
    } else {
        // 是本地檔名，從 uploads_csv/ 移動到 uploads/
        $imageName = sanitize_filename($imageField);
        $srcPath = $csvImageDir . $imageName;
        $destPath = $uploadDir . $imageName;
        if (file_exists($srcPath)) {
            copy($srcPath, $destPath);
        }
    }
    return $imageName;
}

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

            fgetcsv($csvFile); // 跳過第一行（標題）
            fgetcsv($csvFile); // 跳過第二行（說明）

            while (($row_result = fgetcsv($csvFile)) !== FALSE) {
                $Product_ID = $row_result[0];
                $Seller_ID = $row_result[1];
                $Product_name = $row_result[2];
                $Type = $row_result[3]; 
                $quantity = intval($row_result[4]);
                $Product_introduction = $row_result[5];
                $price = intval($row_result[6]);
                $Image = $row_result[7];
                $Remark = $row_result[8];
                $Sell_quantity = $row_result[9];

                $imageFilename = handle_image($Image);

                // 檢查是否已存在
                $prevQuery = "SELECT * FROM `product` WHERE `Product_ID` = ?";
                $stmt = $link->prepare($prevQuery);
                $stmt->bind_param("s", $Product_ID);
                $stmt->execute();
                $prevResult = $stmt->get_result();
                $stmt->close();

                if ($prevResult->num_rows > 0) {
                    // 更新
                    $sql = "UPDATE `product` 
                            SET Product_name=?, Seller_ID=?, quantity=?, Product_introduction=?, price=?, Image=?, Remark=?, Type=? ,Sell_quantity=? 
                            WHERE Product_ID=?";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param("sssisissss", $Product_name, $Seller_ID, $quantity, $Product_introduction, $price, $imageFilename, $Remark, $Type, $Sell_quantity,$Product_ID);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // 新增
                    $sql = "INSERT INTO `product` 
                            (`Product_ID`, `Seller_ID`, `Product_name`, `Type`, `quantity`, `Product_introduction`, `price`, `Image`, `Remark`,`Sell_quantity`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param("ssssisisss", $Product_ID, $Seller_ID, $Product_name, $Type, $quantity, $Product_introduction, $price, $imageFilename, $Remark,$Sell_quantity);
                    $stmt->execute();
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
header("Location: Product.php");
exit();
?>
