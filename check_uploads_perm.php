$dir = __DIR__ . "\\uploads";  // Windows 用反斜線也沒問題

if (!is_dir($dir)) {
    echo "❌ uploads 資料夾不存在<br>";
} else {
    $testfile = $dir . "\\test_" . uniqid() . ".txt";
    if (file_put_contents($testfile, "test") === false) {
        echo "❌ 無法寫入 uploads 資料夾，請檢查資料夾權限<br>";
    } else {
        echo "✅ 成功寫入 uploads 資料夾<br>";
        unlink($testfile);
    }
}
