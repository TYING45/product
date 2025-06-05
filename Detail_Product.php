<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href= rel="stylesheet" type="text/css">
    <title>詳細資料</title>
</head>
<body>
<main>
    <p><b><font size="5">詳細資料</font></b></p>
    <form method="post" action="" enctype="multipart/form-data">
        <table id="Product">
            <th>詳細資料</th>
            <tr><td>商品ID</td><tr>
            <td><input type="text" name="商品ID" id="商品ID" required></td>
            <td>商品名稱</td>
            <td><input type="text" name="商品名稱" id="商品名稱" required></td>
            </tr>

            <tr><td>圖片</td>
            <td><input type="file" name="圖片" id="圖片" accept="image/*" required></td>
            
            <tr><td>價格</td>
            <td><input type="number" name="價格" id="價格" required></td>
            </tr>
           
            <tr><td>庫存數量</td>
                <td><input type="number" name="庫存數量" id="庫存數量" required></td>
            </tr>
            <tr><td>備註</td>
                <td><input type="text" name="備註" id="備註"></td>
            </tr> 
            <tr><td>商品簡介</td>
                <td><textarea name="商品簡介" id="商品簡介" rows="4" required></textarea></td></tr>
            <tr>
                <td colspan="2" align="center">
                    <input name="action" type="hidden" value="add">
                    <input type="submit" value="修改">
                    <input type="button" value="取消" onclick="location.href='Product.php'"> 
                   
                </td>
            </tr>
        </table>
    </form>
</main>
</body>
</html>
