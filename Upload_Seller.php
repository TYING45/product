<?php
require 'vendor/autoload.php';
include("sql_php.php");
include_once 'Upload_Seller.html';

use Dotenv\Dotenv;

// 載入 .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (isset($_POST['output'])) {
    $csvMimes = [
        'text/x-comma-separated-values', 'text/comma-separated-values',
        'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv',
        'text/x-csv', 'text/csv', 'application/csv', 'application/excel',
        'application/vnd.msexcel', 'text/plain'
    ];

    // 檢查檔案是否上傳且為 CSV 格式
    if (!empty($_FILES["fileUpload"]["name"]) && in_array($_FILES["fileUpload"]["type"], $csvMimes)) {
        if (is_uploaded_file($_FILES["fileUpload"]["tmp_name"])) {
            $csvFilePath = $_FILES["fileUpload"]["tmp_name"];
            $originalFilename = basename($_FILES["fileUpload"]["name"]);
            $csvFilename = time() . "_" . $originalFilename; // 避免重複檔名
            $csvContent = file_get_contents($csvFilePath);

            // 讀取 CSV 並寫入資料庫
            $csvFile = fopen($csvFilePath, "r");
            fgetcsv($csvFile); // 跳過標題列

            while (($row = fgetcsv($csvFile)) !== FALSE) {
                // 對應欄位
                $Seller_ID = $row[0];
                $Seller_name = $row[1];
                $Company = $row[2];
                $username = $row[3];
                $password = $row[4];
                $Phone = $row[5];
                $Email = $row[6];
                $Address = $row[7];
                $role = $row[8];

                // 檢查是否已存在，更新或新增
                $prevQuery = "SELECT * FROM `seller` WHERE `Seller_ID` = ?";
                $stmt = $link->prepare($prevQuery);
                $stmt->bind_param("s", $Seller_ID);
                $stmt->execute();
                $prevResult = $stmt->get_result();
                $stmt->close();

                if ($prevResult->num_rows > 0) {
                    $updateQuery = "UPDATE `seller` SET `Seller_name`=?, `Company`=?, `username`=?, `password`=?, `Phone`=?, `Email`=?, `Address`=?, `role`=? WHERE `Seller_ID`=?";
                    $stmt = $link->prepare($updateQuery);
                    $stmt->bind_param("sssssssss", $Seller_name, $Company, $username, $password, $Phone, $Email, $Address, $role, $Seller_ID);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $insertQuery = "INSERT INTO `seller`(`Seller_ID`, `Seller_name`, `Company`, `username`, `password`, `Phone`, `Email`, `Address`, `role`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $link->prepare($insertQuery);
                    $stmt->bind_param("sssssssss", $Seller_ID, $Seller_name, $Company, $username, $password, $Phone, $Email, $Address, $role);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            fclose($csvFile);

            // 讀取 .env 參數
            $githubToken = $_ENV['GITHUB_TOKEN'] ?? '';
            $repoOwner = $_ENV['GITHUB_REPO_OWNER'] ?? 'TYING45';
            $repoName = $_ENV['GITHUB_REPO_NAME'] ?? 'product';
            $branch = $_ENV['GITHUB_BRANCH'] ?? 'main';
            $uploadPath = 'uploads/'; // 上傳路徑，依需要調整
            $githubUsername = $repoOwner; // GitHub 使用者名稱 (user-agent)

            if (!$githubToken || !$repoOwner || !$repoName) {
                die("GitHub 設定不完整，請檢查 .env 中 GITHUB_TOKEN、GITHUB_REPO_OWNER、GITHUB_REPO_NAME");
            }

            // 拼接 GitHub API URL
            $path = $uploadPath . $csvFilename;
            $uploadUrl = "https://api.github.com/repos/TYING45/product/contents/$path";

            // 建立上傳資料
            $data = [
                "message" => "Upload CSV $csvFilename",
                "content" => base64_encode($csvContent),
                "branch" => $branch
            ];

            // 初始化 cURL 並執行 PUT 請求
            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token $githubToken",
                "User-Agent: $githubUsername",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $result = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $result = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $curlError = curl_error($ch);
    curl_close($ch);
    die("cURL 錯誤: $curlError");
}

curl_close($ch);

// 顯示 GitHub 回應內容方便除錯
if ($httpStatus != 201 && $httpStatus != 200) {
    die("GitHub 上傳失敗: HTTP $httpStatus\n回應內容: $result");
}

            if ($httpStatus != 201 && $httpStatus != 200) {
                die("GitHub 上傳失敗: $result");
            }

            // 成功後導回賣家列表頁
            header("Location: Seller.php");
            exit();
        } else {
            die("檔案上傳失敗");
        }
    } else {
        die("請上傳 CSV 檔案");
    }
}
