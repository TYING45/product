<?php  
require 'vendor/autoload.php';
include("sql_php.php");

use Dotenv\Dotenv;

// 載入 .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9_\.-]/', '_', strtolower($filename));
}

function upload_to_github($filename, $content) {
    $token = $_ENV['GITHUB_TOKEN'];
    $owner = $_ENV['GITHUB_REPO_OWNER'] ?? 'TYING45';
    $repo = $_ENV['GITHUB_REPO_NAME'] ?? 'product';
    $branch = $_ENV['GITHUB_BRANCH'] ?? 'main';

    $uploadUrl = "https://api.github.com/repos/$owner/$repo/contents/uploads/$filename";

    $data = [
        "message" => "Upload file $filename",
        "content" => base64_encode($content),
        "branch" => $branch
    ];

    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "User-Agent: $owner",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatus < 200 || $httpStatus >= 300) {
        error_log("GitHub Upload Failed: HTTP $httpStatus\n$result");
    }
}

function handle_image($imageField) {
    $uploadDir = "uploads/";
    $csvImageDir = "uploads_csv/";

    if (filter_var($imageField, FILTER_VALIDATE_URL)) {
        $imageName = sanitize_filename(basename(parse_url($imageField, PHP_URL_PATH)));
        $savePath = $uploadDir . $imageName;

        if (!file_exists($savePath)) {
            $imageData = @file_get_contents($imageField);
            if ($imageData !== false) {
                file_put_contents($savePath, $imageData);
                upload_to_github("seller/" . $imageName, $imageData);
            } else {
                error_log("Image download failed: $imageField");
            }
        }
    } else {
        $imageName = sanitize_filename($imageField);
        $srcPath = $csvImageDir . $imageName;
        $destPath = $uploadDir . $imageName;

        if (file_exists($srcPath) && !file_exists($destPath)) {
            copy($srcPath, $destPath);
        }

        if (file_exists($destPath)) {
            $imageData = file_get_contents($destPath);
            upload_to_github("seller/" . $imageName, $imageData);
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
            $csvFilename = sanitize_filename(basename($_FILES["fileUpload"]["name"]));
            $csvContent = file_get_contents($_FILES["fileUpload"]["tmp_name"]);

            $csvFile = fopen($_FILES["fileUpload"]["tmp_name"], "r");
            fgetcsv($csvFile); // 跳過第一行（標題）
            fgetcsv($csvFile); // 跳過第二行（說明）

            while (($row = fgetcsv($csvFile)) !== FALSE) {
                if (count($row) < 10) continue;

                list($Product_ID, $Seller_ID, $Product_name, $Type, $quantity, $Product_introduction, 
                     $price, $Image, $Remark, $Sell_quantity) = $row;

                $quantity = intval($quantity);
                $price = intval($price);
                $Sell_quantity = intval($Sell_quantity);
                $imageFilename = handle_image($Image);

                // 檢查是否已存在
                $checkSQL = "SELECT 1 FROM `product` WHERE `Product_ID` = ?";
                $stmt = $link->prepare($checkSQL);
                $stmt->bind_param("s", $Product_ID);
                $stmt->execute();
                $exists = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if ($exists) {
                    $sql = "UPDATE `product` 
                            SET Product_name=?, Seller_ID=?, quantity=?, Product_introduction=?, 
                                price=?, Image=?, Remark=?, Type=?, Sell_quantity=? 
                            WHERE Product_ID=?";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param("sssisissis", $Product_name, $Seller_ID, $quantity, $Product_introduction, 
                                                        $price, $imageFilename, $Remark, $Type, $Sell_quantity, $Product_ID);
                } else {
                    $sql = "INSERT INTO `product` 
                            (`Product_ID`, `Seller_ID`, `Product_name`, `Type`, `quantity`, 
                             `Product_introduction`, `price`, `Image`, `Remark`, `Sell_quantity`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param("ssssisissi", $Product_ID, $Seller_ID, $Product_name, $Type, $quantity, 
                                                            $Product_introduction, $price, $imageFilename, $Remark, $Sell_quantity);
                }

                $stmt->execute();
                $stmt->close();
            }

            fclose($csvFile);

            // 上傳原始 CSV 檔案到 GitHub
            upload_to_github("seller/" . $csvFilename, $csvContent);
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
