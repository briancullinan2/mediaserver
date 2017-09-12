FROM ubuntu:latest

RUN sed 's/main$/main universe/' -i /etc/apt/sources.list
RUN apt-get update
RUN apt-get upgrade -y

RUN apt-get -y install apt-utils git apache2 php-fpm php-cli curl php-curl php-pear php-mbstring vim supervisor libapache2-mod-php php-mysql php-mcrypt php-mysqlnd php-pdo php-common php-gd php-xml php-apcu git php-zip zip unzip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN rm -rf /var/www/html/index.html
RUN a2enmod rewrite
RUN a2enmod php7.0

ENV SYMFONY__DATABASE__HOST mediaserverdb
RUN export SYMFONY__DATABASE__HOST=mediaserverdb

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid
ENV APACHE_RUN_DIR /var/run/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
RUN echo "export SYMFONY__DATABASE__HOST=mediaserverdb" >> /etc/apache2/envvars
RUN sed -i "s/^;date.timezone =$/date.timezone = \"America\/Phoenix\"/" /etc/php/*/fpm/php.ini
RUN sed -i "s/^;date.timezone =$/date.timezone = \"America\/Phoenix\"/" /etc/php/*/cli/php.ini

RUN mkdir -p $APACHE_RUN_DIR $APACHE_LOCK_DIR $APACHE_LOG_DIR

# add cron to run every minute
#RUN echo "* * * * * root /var/www/studysauce3/cron.sh" >> /etc/crontab && \
#    chmod a+x /var/www/studysauce3/cron.sh && \
run echo "127.0.0.1  mediaserver.com" >> /etc/hosts && \
    echo "127.0.0.1  test.mediaserver.com" >> /etc/hosts && \
    echo "<Directory \"/var/www\">\nAllowOverride All\n</Directory>" >> /etc/apache2/apache2.conf

ADD . /var/www/
RUN rm -R /var/www/html
ADD supervisord.conf /etc/supervisor/conf.d/supervisord.conf
ADD slim-apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www
VOLUME /var/www

RUN echo "ServerName mediaserver" >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["/usr/bin/supervisord"]

