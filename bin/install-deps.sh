curl -L https://github.com/humanmade/Cavalcade/archive/907c2ec3e52bffe50a8d06ce589d1376731f1e2d.zip -o cavalcade.zip
curl -L https://github.com/humanmade/aws-ses-wp-mail/archive/ae12da33c381bf04ce31c1b520e7b62c9d2269a7.zip -o aws-ses-wp-mail.zip
curl -L https://github.com/humanmade/S3-Uploads/archive/237e73fef2cb56630afa9bb561fc121e58eb4886.zip -o s3-uploads.zip
curl -L https://github.com/pantheon-systems/wp-redis/archive/ac5f4e62a298ea784f682c3ea5dc8061aedf525e.zip -o wp-redis.zip
curl -L https://github.com/humanmade/batcache/archive/5819246c266cf517fb34c1318ff0a6d0bfd69741.zip -o batcache.zip
curl -L https://github.com/stuttter/ludicrousdb/archive/d5d27fc4e19ccfeecac627130e1ec68a800ca381.zip -o ludicrousdb.zip
curl -L https://github.com/humanmade/wordpress-pecl-memcached-object-cache/archive/d380bd4d0eabfd9f46ac47f2d04dd1bcfee73aad.zip -o wordpress-pecl-memcached-object-cache.zip

mkdir plugins/cavalcade ; tar -xvf cavalcade.zip -C plugins/cavalcade --strip-components=1
mkdir plugins/aws-ses-wp-mail ; tar -xvf aws-ses-wp-mail.zip -C plugins/aws-ses-wp-mail --strip-components=1
mkdir plugins/wp-redis ; tar -xvf wp-redis.zip -C plugins/wp-redis --strip-components=1
mkdir plugins/s3-uploads ; tar -xvf s3-uploads.zip -C plugins/s3-uploads --strip-components=1
mkdir dropins/batcache ; tar -xvf batcache.zip -C dropins/batcache --strip-components=1
mkdir dropins/ludicrousdb ; tar -xvf ludicrousdb.zip -C dropins/ludicrousdb --strip-components=1
mkdir dropins/wordpress-pecl-memcached-object-cache ; tar -xvf wordpress-pecl-memcached-object-cache.zip -C dropins/wordpress-pecl-memcached-object-cache --strip-components=1

rm -r plugins/s3-uploads/lib/aws-sdk
rm -r plugins/s3-uploads/tests
rm -r plugins/aws-ses-wp-mail/lib/aws-sdk
