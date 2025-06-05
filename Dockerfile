# 使用官方 PHP + Apache 映像
FROM php:8.2-apache

# 複製所有專案檔案到 Apache 網頁根目錄
COPY . /var/www/html/

# 啟用 Apache 的 rewrite 模組（如有用 .htaccess）
RUN a2enmod rewrite
