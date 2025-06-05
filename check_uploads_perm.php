<?php
$dir = __DIR__ . "/uploads";

echo "檔案夾路徑：$dir<br>";

// 檢查 uploads 資料夾是否存在
if (!is_dir($dir)) {
    echo "❌ uploads 資料夾不存在<br>";
} else {
    echo "✅ uploads 資料夾存在<br>";
    
    // 顯示該資料夾權限（八進位）
    $perms = substr(sprintf('%o', fileperms($dir)), -4);
    echo "權限（octal）：$perms<br>";

    // 顯示擁有者與群組名稱
    if (function_exists('posix_getpwuid')) {
        $owner_info = posix_getpwuid(fileowner($dir));
        $group_info = posix_getgrgid(filegroup($dir));
        echo "擁有者：".$owner_info['name']."<br>";
        echo "群組：".$group_info['name']."<br>";
    } else {
        echo "無法取得擁有者與群組資訊（Windows 環境或不支援 posix 函式）<br>";
    }

    // 測試是否可寫入
    $testfile = $dir . "/test_" . uniqid() . ".txt";
    $result = @file_put_contents($testfile, "test");
    if ($result === false) {
        echo "❌ 無法在 uploads 資料夾內寫入檔案<br>";
    } else {
        echo "✅ 可在 uploads 資料夾寫入檔案<br>";
        unlink($testfile);
    }
}
?>
