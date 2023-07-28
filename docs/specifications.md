---
order: 100
---
# Specifications

- CPU Architecture: 64-bit, x86

The following versions are available, and the specific version in use will depend on your configuration.

- PHP Version: 7.4, 8.0, 8.1, 8.2  see [Altis compatibility chart](docs://guides/updating-php/#altis-compatibility-chart)
- MySQL Version: 5.7 (compatible; using AWS Aurora)

## PHP Modules

- apcu
- calendar
- Core
- ctype
- curl
- date
- dom
- excimer
- exif
- fileinfo
- filter
- ftp
- gd
- gettext
- hash
- iconv
- igbinary
- imagick
- intl
- json
- libxml
- mbstring
- mcrypt
- memcached
- msgpack
- mysqli
- mysqlnd
- newrelic
- openssl
- pcntl
- pcre
- PDO
- pdo_mysql
- Phar
- posix
- readline
- redis
- Reflection
- session
- shmop
- SimpleXML
- soap
- sockets
- SPL
- ssh2
- standard
- sysvmsg
- sysvsem
- sysvshm
- tokenizer
- v8js (experimental)
- wddx
- xml
- xmlreader
- xmlwriter
- xsl
- Zend OPcache
- zip
- zlib

# Limitations

- Maximum upload size: 1GB (1GB for media upload endpoints, and 50MB for all other requests)
- php execution timeout: 60s
- Cron task execution timeout: 1 hour
