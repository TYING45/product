<?php
require 'vendor/autoload.php';
include("sql_php.php");
include_once 'Uplaod_Seller.html';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
            $csvFilename = basename($_FILES["fileUpload"]["name"]);
            $csvContent = file_get_contents($csvFilePath);

            // 讀取 CSV 並寫入資料庫
            $csvFile = fopen($csvFilePath, "r");
            fgetcsv($csvFile); // 跳過標題

            while (($row = fgetcsv($csvFile)) !== FALSE) {
                $Seller_ID = $row[0];
                $Seller_name = $row[1]; 
                $Company = $row[2];  
                $username = $row[3];
                $password = $row[4];
                $Phone = $row[5];
                $Email = $row[6];
                $Address = $row[7];
                $role = $row[8];

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
                    $insertQuery = "INSERT INTO `seller`(`Seller_ID`, `Seller_name`, `Company`, `username`, `password`, `Phone`, `Email`, `Address`, `role`) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $link->prepare($insertQuery);
                    $stmt->bind_param("sssssssss", $Seller_ID, $Seller_name, $Company, $username, $password, $Phone, $Email, $Address, $role);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            fclose($csvFile);

            $githubToken = $_ENV['GITHUB_TOKEN'];
            $repo = $_ENV['GITHUB_REPO'];
            $branch = $_ENV['GITHUB_BRANCH'];
            $path = $_ENV['GITHUB_PATH'] . $csvFilename;

            $uploadUrl = "https://api.github.com/repos/TYING45/product/contents/$path";

            $data = [
                "message" => "Upload CSV $csvFilename",
                "content" => base64_encode($csvContent),
                "branch" => $branch
            ];

            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token $githubToken",
                "User-Agent: ".$_ENV['GITHUB_USERNAME'],
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $result = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpStatus != 201 && $httpStatus != 200) {
                die("GitHub 上傳失敗: $result");
            }

            header("Location: Seller.php");
            exit();
        } else {
            die("檔案上傳失敗");
        }
    } else {
        die("請上傳 CSV 檔案");
    }
}
