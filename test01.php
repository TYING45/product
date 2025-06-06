<?php
$token = "你的新token";
$owner = "TYING45";
$repo = "product";

$url = "https://api.github.com/user";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token $token",
    "User-Agent: PHP-script",
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP狀態碼：$httpCode\n";
echo "回應內容：\n$response\n";
?>
