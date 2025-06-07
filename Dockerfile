# 使用官方 PHP Apache 映像
FROM php:8.2-apache

# 更新並安裝系統依賴和 PHP mysqli 擴展
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip

# 安裝 Composer（全域）
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

# 啟用 Apache rewrite 模組（若需要）
RUN a2enmod rewrite

# 複製專案檔案到容器網站根目錄
COPY . /var/www/html/

# 設定工作目錄
WORKDIR /var/www/html/

# 安裝 PHP Composer 依賴（生產環境，不安裝 dev 套件）
RUN composer install --no-dev --optimize-autoloader

# 設定檔案權限（視情況可調整）
RUN chown -R www-data:www-data /var/www/html

# 暴露 80 端口（HTTP）
EXPOSE 80

# 啟動 Apache 前台進程
CMD ["apache2-foreground"]
