<?php  
session_start();
include("sql_php.php");  

// 設定連線編碼，避免編碼錯誤
$link->set_charset("utf8mb4");

// 安全處理檔名
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9_\.-]/', '_', strtolower($filename));
}

// 圖片處理（下載網址或移動本地檔案）
function handle_image($imageField) {
    $uploadDir = "uploads/";
    $csvImageDir = "uploads_csv/";

    if (filter_var($imageField, FILTER_VALIDATE_URL)) {
        $imageData = @file_get_contents($imageField);
        if ($imageData === false) return ''; // 下載失敗回傳空字串
        $imageName = basename(parse_url($imageField, PHP_URL_PATH));
        $imageName = sanitize_filename($imageName);
        file_put_contents($uploadDir . $imageName, $imageData);
    } else {
        $imageName = sanitize_filename($imageField);
        $srcPath = $csvImageDir . $imageName;
        $destPath = $uploadDir . $imageName;
        if (file_exists($srcPath)) {
            copy($srcPath, $destPath);
        } else {
            return ''; // 本地檔案不存在，回傳空字串
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

            // 跳過前兩行：標題與說明
            fgetcsv($csvFile);
            fgetcsv($csvFile);

            $lineNumber = 2;
            while (($row = fgetcsv($csvFile)) !== FALSE) {
                $lineNumber++;
                // 確保欄位數量足夠 (至少9欄)
                if (count($row) < 9) {
                    error_log("第{$lineNumber}行資料欄位不足，跳過");
                    continue;
                }

                // 轉UTF-8且trim
                $Product_ID = mb_convert_encoding(trim($row[0]), 'UTF-8', 'auto');
                $Seller_ID = mb_convert_encoding(trim($row[1]), 'UTF-8', 'auto');
                $Product_name = mb_convert_encoding(trim($row[2]), 'UTF-8', 'auto');
                $Type = mb_convert_encoding(trim($row[3]), 'UTF-8', 'auto');
                $price = intval(trim($row[4]));
                $Product_introduction = mb_convert_encoding(trim($row[5]), 'UTF-8', 'auto');
                $quantity = intval(trim($row[6]));
                $Image = mb_convert_encoding(trim($row[7]), 'UTF-8', 'auto');
                $Remark = mb_convert_encoding(trim($row[8]), 'UTF-8', 'auto');

                $imageFilename = handle_image($Image);

                // 查詢是否已存在該 Product_ID
                $prevQuery = "SELECT 1 FROM `product` WHERE `Product_ID` = ?";
                $stmt = $link->prepare($prevQuery);
                $stmt->bind_param("s", $Product_ID);
                $stmt->execute();
                $prevResult = $stmt->get_result();
                $stmt->close();

                if ($prevResult->num_rows > 0) {
                    // 更新
                    $sql = "UPDATE `product` SET 
                            Product_name=?, Seller_ID=?, Type=?, price=?, Product_introduction=?, quantity=?, Image=?, Remark=?,Sell_quantity=?
                            WHERE Product_ID=?";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param(
                        "sssssisisss", 
                        $Product_name, $Seller_ID, $Type, $price, $Product_introduction, 
                        $quantity, $imageFilename, $Remark, $Product_ID
                    );
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // 新增
                    $sql = "INSERT INTO `product` 
                            (Product_ID, Seller_ID, Product_name, Type, price, Product_introduction, quantity, Image, Remark,Sell_quantity) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param(
                        "ssssisisss", 
                        $Product_ID, $Seller_ID, $Product_name, $Type, $price, 
                        $Product_introduction, $quantity, $imageFilename, $Remark,$Sell_quantity
                    );
                    $stmt->execute();
                    $stmt->close();
                }
            }

            fclose($csvFile);
            error_log("資料處理完成。");
        } else {
            die("檔案上傳失敗");
        }
    } else {
        die("請上傳 CSV 檔案");
    }

    header("Location: Seller_Product.php");
    exit();
}
?>
