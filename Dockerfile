FROM php:8.2-apache

# 安裝 mysqli 擴展與依賴
RUN docker-php-ext-install mysqli

# 複製專案檔案到網站根目錄
COPY . /var/www/html/

# 啟用 Apache rewrite 模組（如果有需要）
RUN a2enmod rewrite
