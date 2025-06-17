FROM php:7.4-apache

# 필요한 확장 설치
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 세션 디렉토리 생성 및 권한 설정
RUN mkdir -p /tmp/sessions && \
    chmod 777 /tmp/sessions && \
    chown www-data:www-data /tmp/sessions

# PHP 세션 설정
RUN echo "session.save_path = /tmp/sessions" > /usr/local/etc/php/conf.d/sessions.ini

# Apache mod_rewrite 활성화
RUN a2enmod rewrite

# Apache 설정 - .htaccess 허용 및 URL 리라이트 활성화
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/rewrite.conf

# 설정 활성화
RUN a2enconf rewrite

# 포트 노출
EXPOSE 80