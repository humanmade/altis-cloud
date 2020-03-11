# CDN Purge

The majority of the requests, data served by Altis will be cached on the CDN to improve performance and delivery time to the user. In some situations you may need to invalidate specific URLs on the CDN. You can add your own CDN purge rule directly in PHP using `Altis\Cloud\purge_cdn_paths` helper function.

## Automatic media purge rule

By default, Altis will not remove uploaded media from the CDN cache when deleting attachments. You can enable this beahviour from the project `composer.json`. The example below shows the default configuration:

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

Set `"cdn-media-purge": true` to enabled cdn purging of media on when attachments are deleted
