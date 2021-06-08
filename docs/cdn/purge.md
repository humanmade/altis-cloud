# Purging Cache on the CDN

The majority of the requests, data served by Altis will be cached on the CDN to improve performance and delivery time to the user. In some situations you may need to invalidate specific URLs on the CDN. You can add your own CDN purge rule directly in PHP using `Altis\Cloud\purge_cdn_paths()` helper function.

```php
use Altis\Cloud\purge_cdn_paths;

purge_cdn_paths( [
   '/sample-page/*',
] );
```

Note that purging paths should be kept to only essential items as purging more than 1000 paths per month will incur overage charges.

**Important note:** Due to AWS limitations, you **cannot** purge large numbers of paths at once. Do not call this function in bulk; contact the Altis Cloud team if you need to perform large numbers of invalidations. The `purge_cdn_paths()` function is limited to 10 wildcards or 2000 static paths.

## Automatic media purge rule

By default, Altis will not remove uploaded media from the CDN cache when deleting attachments. You can enable this behavior from the project's configuration in `composer.json`. The example below shows the default configuration:

```json
{
   "extra": {
      "altis": {
         "modules": {
            "cloud": {
               "cdn-media-purge": false
            }
         }
      }
   }
}
```

Set `"cdn-media-purge": true` to enable purging media files from the CDN when attachments are deleted.
