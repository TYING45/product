<?php
$dir = __DIR__ . "\\uploads";

echo "C:\xampp\htdocs\product\uploads：$dir<br>";

if (!is_dir($dir)) {
    echo "❌ uploads 資料夾不存在，正在嘗試建立...<br>";
    if (mkdir($dir, 0777, true)) {
        echo "✅ 成功建立 uploads 資料夾<br>";
    } else {
        echo "❌ 建立 uploads 資料夾失敗，請手動建立<br>";
        exit;
    }
}

// 接下來測試是否能寫入
$testfile = $dir . "\\test_" . uniqid() . ".txt";
if (file_put_contents($testfile, "test") === false) {
    echo "❌ 無法寫入 uploads 資料夾，請檢查權限<br>";
} else {
    echo "✅ 成功寫入 uploads 資料夾<br>";
    unlink($testfile);
}
?>

