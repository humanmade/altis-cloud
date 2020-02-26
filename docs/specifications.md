# Specifications

- CPU Architecture: 64 bit
- PHP Version: 7.2
- MySQl Version: 5.6, 5.7

## PHP Modules

apcu, calendar, Core, ctype, curl, date, dom, exif, fileinfo, filter, ftp, gd, gettext, hash, iconv, igbinary, intl, json, libxml, mbstring, mcrypt, memcached, msgpack, mysqli, mysqlnd, newrelic, openssl, pcntl, pcre, PDO, pdo_mysql, Phar, posix, readline, Reflection, session, shmop, SimpleXML, sockets, SPL, ssh2, standard, sysvmsg, sysvsem, sysvshm, tokenizer, v8js, wddx, xhprof, xml, xmlreader, xmlwriter, xsl, Zend OPcache, zip, zlib

# Limitations

- Upload size: 1GB (nginx is set to 50M, 1G for php files)
- php execution timeout: 60s
- Cron task execution timeout: 1 hour
