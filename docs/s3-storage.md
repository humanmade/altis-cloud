# S3 Storage

Within the scalable multi-server architecture of Altis, sites cannot use the server filesystem to store artifacts, as they will not be shared between different instances.

Altis seamlessly replaces the uploads location within WordPress with a remote [Amazon S3-based](https://aws.amazon.com/s3/) storage solution.


## How Files Work

In typical WordPress configurations, sites are served from a single machine, with a writable filesystem. When users upload files, WordPress places these files within the `wp-content/uploads/` directory.

On Altis, the filesystem is not writable to ensure servers can easily be [scaled](./architecture.md), as well as for security reasons. Writing files directly to a single machine's filesystem would cause these files to "disappear" when servers are replaced or scaled.

Instead, Altis replaces the upload directory location in WordPress with a location within your environment's S3 bucket. This is done through the `wp_upload_dir()` function, which is set to an `s3://` location - Altis installs a `s3` [stream wrapper](https://www.php.net/manual/en/class.streamwrapper.php) to seamlessly handle these URLs.

Within the [CDN configuration](./cdn/), the `/uploads/` URL is routed directly to the S3 data store, skipping the application servers. The [Tachyon dynamic image service](docs://media/dynamic-images.md) also accesses the S3 data store directly.

In most cases, this integration occurs automatically and seamlessly with no impact upon existing code.


## Compatibility

In some cases, compatibility issues may occur when code attempts to access files directly. As the upload location is set to an `s3://` URL and implemented within stream wrappers, code using PHP extensions which is not compatible with stream wrappers may fail.

The underlying S3 storage resembles a filesystem within WordPress, but is not exactly the same. Operations using file directory listing may fail or have performance issues. This is common with plugins implementing backup processes, which will not work properly with the S3-based storage; use the [built-in backup functionality instead](./backups.md).

Files stored in S3 are stored completely separately to the executable code within Altis, implementing a [write-xor-execute (W^X) filesystem](https://en.wikipedia.org/wiki/W%5EX). This ensures that even if an exploit of your site allows users to upload executable files (i.e. `.php` files), they cannot use this to gain access to your server(s). Some plugins may require the ability to write executable files (such as for template caching), and these files will not work properly with the S3-based storage; use the [object cache functionality instead](./object-cache.md).


## Configuration

Storage behaviour is generally not user configurable, and is handled automatically for you by the Cloud module.

**Note:** Any changes to the storage configuration are considered to void your warranty, except as directed by the Altis team. Adjusting any configuration may cause catastrophic errors in your environments.

The integrated S3 behaviour can be disabled by setting `modules.cloud.s3-uploads` to false:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "s3-uploads": false
                }
            }
        }
    }
}
```
