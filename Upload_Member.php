<?php
require 'vendor/autoload.php';
include("sql_php.php");

use Dotenv\Dotenv;

// 載入 .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 當使用者提交表單
if (isset($_POST['output'])) {
    $csvMimes = [
        'text/x-comma-separated-values', 'text/comma-separated-values',
        'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv',
        'text/x-csv', 'text/csv', 'application/csv', 'application/excel',
        'application/vnd.msexcel', 'text/plain'
    ];

    if (!empty($_FILES["fileUpload"]["name"]) && in_array($_FILES["fileUpload"]["type"], $csvMimes)) {
        if (is_uploaded_file($_FILES["fileUpload"]["tmp_name"])) {
            $csvFilePath = $_FILES["fileUpload"]["tmp_name"];
            $csvFilename = time() . "_" . basename($_FILES["fileUpload"]["name"]);
            $csvContent = file_get_contents($csvFilePath);

            // 讀取 CSV 並寫入資料庫
            $csvFile = fopen($csvFilePath, "r");
            fgetcsv($csvFile); // 跳過標題列

            while (($row = fgetcsv($csvFile)) !== FALSE) {
                list($Member_ID, $Member_name, $password,$Phone, $Email, $Address) = $row;

                $stmt = $link->prepare("SELECT * FROM `member` WHERE `Member_ID` = ?");
                $stmt->bind_param("s", $Member_ID);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0) {
                    $stmt = $link->prepare("UPDATE `member` SET `Member_name`=?, `password`=?, `Phone`=?, `Email`=?, `Address`=? WHERE `Member_ID`=?");
                    $stmt->bind_param("ssssss", $Member_name, $password, $Phone, $Email, $Address, $Member_ID);
                } else {
                    $stmt = $link->prepare("INSERT INTO `member`(`Member_ID`, `Member_name`,`password`, `Phone`, `Email`, `Address`) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $Member_ID, $Member_name, $password, $Phone, $Email, $Address);
                }
                $stmt->execute();
                $stmt->close();
            }
            fclose($csvFile);

            // GitHub 設定
            $token = $_ENV['GITHUB_TOKEN'] ?? '';
            $owner = $_ENV['GITHUB_REPO_OWNER'] ?? 'TYING45';
            $repo = $_ENV['GITHUB_REPO_NAME'] ?? 'product';
            $branch = $_ENV['GITHUB_BRANCH'] ?? 'main';
            $path = 'uploads/member/' . $csvFilename;

            $uploadUrl = "https://api.github.com/repos/$owner/$repo/contents/$path";

            $data = [
                "message" => "Upload CSV $csvFilename",
                "content" => base64_encode($csvContent),
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

            // 成功後跳轉
                header("Location: Member.php");
                exit;
        } else {
            die("檔案上傳失敗");
        }
    } else {
        die("請上傳有效的 CSV 檔案");
    }
}
?>
