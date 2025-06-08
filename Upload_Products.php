<?php  
require 'vendor/autoload.php';
include("sql_php.php");

use Dotenv\Dotenv;

// 載入 .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 保留檔案原名，僅移除潛在危險字元（例如換行符）
function clean_filename($filename) {
    return preg_replace('/[\x00-\x1F\x7F]/', '', $filename); 
}

// 下載或搬移圖片
function handle_image($imageField) {
    $uploadDir = "uploads/";
    $csvImageDir = "uploads_csv/";

    if (filter_var($imageField, FILTER_VALIDATE_URL)) {
        $imageName = clean_filename(basename(parse_url($imageField, PHP_URL_PATH)));
        $savePath = $uploadDir . $imageName;

        if (!file_exists($savePath)) {
            $imageData = file_get_contents($imageField);
            file_put_contents($savePath, $imageData);
        }
    } else {
        $imageName = clean_filename($imageField);
        $srcPath = $csvImageDir . $imageName;
        $destPath = $uploadDir . $imageName;

        if (file_exists($srcPath) && !file_exists($destPath)) {
            copy($srcPath, $destPath);
        }
    }

    return $imageName;
}

// 查詢 GitHub 上是否已有該檔案
function get_file_sha_from_github($path) {
    $token = $_ENV['GITHUB_TOKEN'];
    $owner = $_ENV['GITHUB_REPO_OWNER'] ?? 'TYING45';
    $repo = $_ENV['GITHUB_REPO_NAME'] ?? 'product';
    $branch = $_ENV['GITHUB_BRANCH'] ?? 'main';

    $url = "https://api.github.com/repos/$owner/$repo/contents/" . rawurlencode($path) . "?ref=$branch";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "User-Agent: $owner"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status == 200) {
        $json = json_decode($result, true);
        return $json['sha'] ?? null;
    }
    return null;
}

// 上傳 CSV 到 GitHub
function upload_to_github($filename, $content) {
    $token = $_ENV['GITHUB_TOKEN'];
    $owner = $_ENV['GITHUB_REPO_OWNER'] ?? 'TYING45';
    $repo = $_ENV['GITHUB_REPO_NAME'] ?? 'product';
    $branch = $_ENV['GITHUB_BRANCH'] ?? 'main';
    $path = "uploads/seller/" . $filename;

    $sha = get_file_sha_from_github($path);

    $data = [
        "message" => "Upload CSV $filename",
        "content" => base64_encode($content),
        "branch" => $branch
    ];

    if ($sha) {
        $data['sha'] = $sha; // 加上 sha 表示是更新
    }

    $uploadUrl = "https://api.github.com/repos/$owner/$repo/contents/" . rawurlencode($path);

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

// 主流程
if (isset($_POST['output'])) {
    $csvMimes = [
        'text/x-comma-separated-values', 'text/comma-separated-values',
        'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv',
        'text/x-csv', 'text/csv', 'application/csv', 'application/excel',
        'application/vnd.msexcel', 'text/plain'
    ];

    if (!empty($_FILES["fileUpload"]["name"]) && in_array($_FILES["fileUpload"]["type"], $csvMimes)) {
        if (is_uploaded_file($_FILES["fileUpload"]["tmp_name"])) {
            $csvFilename = basename($_FILES["fileUpload"]["name"]);
            $csvContent = file_get_contents($_FILES["fileUpload"]["tmp_name"]);

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

                // 檢查是否存在
                $prevQuery = "SELECT * FROM `product` WHERE `Product_ID` = ?";
                $stmt = $link->prepare($prevQuery);
                $stmt->bind_param("s", $Product_ID);
                $stmt->execute();
                $prevResult = $stmt->get_result();
                $stmt->close();

                if ($prevResult->num_rows > 0) {
                    // 更新
                    $sql = "UPDATE `product` 
                            SET Product_name=?, Seller_ID=?, quantity=?, Product_introduction=?, price=?, Image=?, Remark=?, Type=?, Sell_quantity=? 
                            WHERE Product_ID=?";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param("sssisissis", $Product_name, $Seller_ID, $quantity, $Product_introduction, $price, $imageFilename, $Remark, $Type, $Sell_quantity, $Product_ID);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // 新增
                    $sql = "INSERT INTO `product` 
                            (`Product_ID`, `Seller_ID`, `Product_name`, `Type`, `quantity`, `Product_introduction`, `price`, `Image`, `Remark`, `Sell_quantity`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param("ssssisissi", $Product_ID, $Seller_ID, $Product_name, $Type, $quantity, $Product_introduction, $price, $imageFilename, $Remark, $Sell_quantity);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            fclose($csvFile);

            // 上傳 CSV 到 GitHub
            upload_to_github($csvFilename, $csvContent);

        } else {
            die("檔案上傳失敗");
        }
    } else {
        die("請上傳 CSV 檔案");
    }
}

header("Location: Seller_Product.php");
exit();
?>
