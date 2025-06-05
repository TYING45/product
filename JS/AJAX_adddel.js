function updateStock(button, change) {
    const input = button.parentElement.querySelector('.stock-input');
    const currentValue = parseInt(input.value);
    const newValue = Math.max(0, currentValue + change);
    input.value = newValue;

    const productId = button.getAttribute('data-id');

    // AJAX 請求更新庫存
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_stock.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            console.log('库存更新成功');
        }
    };
    xhr.send(`id=${productId}&stock=${newValue}`);
}