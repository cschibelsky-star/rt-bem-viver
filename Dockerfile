FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       ca-certificates curl libfreetype6-dev libjpeg62-turbo-dev libpng-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY bootstrap/source-text.part.* /tmp/rtbem-bootstrap/
RUN cat /tmp/rtbem-bootstrap/source-text.part.00 \
        /tmp/rtbem-bootstrap/source-text.part.01 \
        /tmp/rtbem-bootstrap/source-text.part.02 \
        /tmp/rtbem-bootstrap/source-text.part.03 \
        /tmp/rtbem-bootstrap/source-text.part.04 \
      | base64 -d > /tmp/rtbem-source.tar.gz \
    && tar -xzf /tmp/rtbem-source.tar.gz -C /var/www/html \
    && rm -rf /tmp/rtbem-bootstrap /tmp/rtbem-source.tar.gz

COPY overrides/ /var/www/html/
RUN php /var/www/html/tests/auth_recovery_smoke.php

RUN mkdir -p /var/www/html/assets/img \
    && for f in \
      brasao-santa-barbara.png \
      city-americana-avenida.jpg \
      city-americana-real.jpg \
      city-americana.jpg \
      city-campinas.jpg \
      city-hortolandia.jpg \
      city-santa-barbara.jpg \
      city-sumare-real.jpg \
      city-sumare.jpg \
      event-cultura.jpg \
      event-esporte.jpg \
      event-lazer.jpg \
      fallback-instagram.jpg \
      hero-bem-viver-exact.jpg \
      hero-bem-viver-site.jpg \
      hero-bem-viver.jpg \
      vitrine-ia-pro-logo-oficial.png; do \
        echo "RTBEM_ASSET:$f"; \
        curl -fsSL --retry 2 --retry-delay 1 \
          "https://rtbemviver.com.br/assets/img/$f" \
          -o "/var/www/html/assets/img/$f" \
          || { rm -f "/var/www/html/assets/img/$f"; echo "RTBEM_MISSING:$f"; }; \
      done

RUN mkdir -p /var/www/html/data /var/www/html/uploads/events /var/www/html/uploads/social /var/www/html/uploads/cities \
    && chown -R www-data:www-data /var/www/html/data /var/www/html/uploads \
    && find /var/www/html/data /var/www/html/uploads -type d -exec chmod 775 {} \; \
    && find /var/www/html/data /var/www/html/uploads -type f -exec chmod 664 {} \;

EXPOSE 80
