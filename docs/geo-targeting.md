# Geo Targeting / GeoIP

Altis supports geolocation targeting by default. Developers can make use of the `Cloudfront-Viewer-Country` HTTP Header to serve content on a per-country basis.

## Cache Variants

When Geo Targeting is enabled, all [page caches](page-caching.md) will be stored per country. 

## Example

The following is an example to show posts to a user that have a tag matching the viewer's country:


```php
$posts = new WP_Query( [
	'tag' => sanitize_text_field( $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] ),
] );
```
