FROM php:7.3-cli

COPY . /
WORKDIR /

CMD [ "php", "/src/lib/run.php" ]
