FROM mysql:latest

ENV MYSQL_ROOT_PASSWORD mediaserver2017

#COPY ./studysauce-03-20-2017-prod.sql.gz /tmp/
#RUN gunzip /tmp/studysauce-03-20-2017-prod.sql.gz
RUN echo "CREATE DATABASE mediaserver; GRANT ALL ON mediaserver.* TO 'study2'@'%' IDENTIFIED BY 'itekIO^#(1234'; USE mediaserver;" > /docker-entrypoint-initdb.d/data-dump.sql
#RUN cat /tmp/studysauce-03-20-2017-prod.sql >> /docker-entrypoint-initdb.d/data-dump.sql

