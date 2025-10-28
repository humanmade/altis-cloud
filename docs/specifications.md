---
order: 100
---
# Specifications

- CPU Architecture: 64-bit, x86

The following versions are available, and the specific version in use will depend on your configuration.

- PHP Version: 8.4, 8.3, 8.2, 8.1  see [Altis compatibility chart](docs://guides/updating-php/#altis-compatibility-chart)
- MySQL Version: 8.0 (compatible; using AWS Aurora)

## PHP Modules

<!-- vale Vale.Spelling = NO -->
<!-- vale Vale.Terms = NO -->
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
- msgpack
- mysqli
- mysqlnd
- openssl
- pcntl
- pcre
- PDO
- pdo_mysql
- pdo_sqlite
- Phar
- posix
- random
- readline
- redis
- Reflection
- session
- shmop
- SimpleXML
- soap
- sockets
- sodium
- SPL
- sqlite3
- ssh2
- standard
- sysvmsg
- sysvsem
- sysvshm
- tokenizer
- v8js
- xml
- xmlreader
- xmlwriter
- xsl
- Zend OPcache
- zip
- zlib
<!-- vale Vale.Terms = YES -->
<!-- vale Vale.Spelling = YES -->

## Limitations

- Maximum upload size: 1GB (1GB for media upload endpoints, and 50MB for all other requests)
- PHP execution timeout: 60s
- Cron task execution timeout: 1 hour
