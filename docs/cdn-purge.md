# CDN Purge

The majority of the pages, media viewed on Altis will be cached on CDN to improve performance and delivery time to the user. You can add your own CDN purge rule directly in PHP using `Altis\Cloud\purge_cdn_path` helper function.   

## Automatic media purge rule

By default, Altis will not remove media from CDN on delete attachment. You can enable it from the project `composer.json`. The example below shows the default configuration:

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

Set `"cdn-media-purge": true` to initiate cdn purge for media on delete attachment.
