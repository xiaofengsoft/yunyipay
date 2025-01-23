# 使用官方的 PHP 镜像作为基础镜像
FROM php:8.2-cli

# 更换为阿里云的 Debian 镜像源
RUN echo "deb http://mirrors.aliyun.com/debian/ bookworm main non-free contrib" > /etc/apt/sources.list && \
    echo "deb-src http://mirrors.aliyun.com/debian/ bookworm main non-free contrib" >> /etc/apt/sources.list

# 安装必要的扩展
RUN apt-get update && apt-get install -y --fix-missing \
    git \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install zip intl pdo_mysql mysqli

# 安装 Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug mysqli

# 配置 Xdebug
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# 设置工作目录
WORKDIR /var/www/html

# 安装 Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install

# 暴露端口 9004 (Xdebug)
EXPOSE 9004

# 启动 PHP 内置服务器
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
