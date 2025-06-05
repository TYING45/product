<?php
// GitHub 設定（請填你自己的）
$github_owner = "TYING45";
$github_repo = "product";
$github_branch = "main";
$github_token = "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";  // ❗建議只用測試帳號或短效 PAT

// 上傳圖片到 GitHub 的函式
function uploadImageToGitHub($owner, $repo, $branch, $token, $image_tmp_path, $remote_path) {
    if (!file_exists($image_tmp_path)) {
        return [false, "❌ 暫存檔不存在"];
    }

    $content = base64_encode(file_get_contents($image_tmp_path));
    $url = "https://api.github.com/repos/$owner/$repo/contents/$remote_path";

    $data = [
        "message" => "Upload $remote_path via test script",
        "branch" => $branch,
        "content" => $content
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "User-Agent: PHP-GitHub-Upload-Test",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [false, "❌ cURL 錯誤：$curlErr"];
    }

    return [$httpCode, $response];
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>測試圖片上傳到 GitHub</title>
</head>
<body>
    <h2>測試圖片上傳到 GitHub</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="test_image" required />
        <button type="submit">上傳圖片</button>
    </form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["test_image"])) {
    $file = $_FILES["test_image"];
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "gif"];

    if (!in_array($ext, $allowed)) {
        echo "<p style='color:red;'>❌ 僅支援 jpg/jpeg/png/gif</p>";
        exit;
    }

    $new_name = uniqid() . "." . $ext;
    $remote_path = "uploads/" . $new_name;

    list($status, $result) = uploadImageToGitHub(
        $github_owner,
        $github_repo,
        $github_branch,
        $github_token,
        $file["tmp_name"],
        $remote_path
    );

    echo "<h3>📤 上傳結果</h3>";
    echo "<p><strong>HTTP 狀態碼：</strong> $status</p>";
    echo "<pre>$result</pre>";

    if ($status == 201 || $status == 200) {
        echo "<p style='color:green;'>✅ 上傳成功</p>";
        $img_url = "https://raw.githubusercontent.com/$github_owner/$github_repo/$github_branch/uploads/$new_name";
        echo "<p><img src='$img_url' alt='Uploaded image' style='max-width:300px;'></p>";
        echo "<p>圖片 URL: <a href='$img_url' target='_blank'>$img_url</a></p>";
    } else {
        echo "<p style='color:red;'>❌ 上傳失敗</p>";
    }
}
?>
</body>
</html>

