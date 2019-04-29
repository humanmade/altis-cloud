# Page Caching

The majority of the pages viewed on HM Cloud will be cached to improve performance and delivery time to the user. Its necessary to take into account the page cache when developing - considerations are listed at the bottom of this page. All pages are cached by default, and the following rules excluded specific cases:

- All `POST` requests are not cached
- Any requests with cookies matching the patterns `wordpress_*`, `wp-*`, `wp_*`, `comment_*` and `hm_*`are not cached.
- Any requests with the `Authentication` HTTP header will not be cached.
- Any response with the `Cache-Control: no-cache` HTTP header will not be cached.
- Any response with no content are not cached.
- Any request to `xmlrpc.php`, `wp-cron.php` or `wp-app.php` are not cached.
- Any request to `wp-includes/js*` are not cached.

## Cache Key Calculation

Each cache key for pages follows the pattern:

```
{method}:{protocol}:{host}:{path}:{query_params}
```

## Cache Times

By default any cached page returning a 200 response will have a TTL of 300 seconds. This can be adjusted by specifying a custom `Cache-Control: max-age=x` HTTP header. The specific default response code TTLs are as follows:

- `200` 300 seconds
- `400` 10 seconds
- `404` 300 seconds
- `5XX` 300 seconds

## Developer Considerations

Any response that can be added to the page cache should not include references to any user-data in the request. This is because subsequent requests from other users will receive the same cached page. This means no use of headers such as `User-Agent`, `Cookie`, `Referer`; client IP addresses, geo-ip targeting / restrictions should be used within PHP. These can be used if the response will not be added to the page cache (such as logged in users.)

## Cache Rule Customizations

The patterns for excluded cache cookies can be modified at request from Human Made. It's recommended to name the cookies accordingly to a current exclusion pattern if you want to access them via PHP. If you have a custom cookie name, it's also possible to create new cache pools from the cookie values, so users with the custom cookie still get performance benefits of the page cache. After requesting the cookie name exception with Human Made, it's necessary to define the behavior for the custom cookie in regards to the page cache.

The follow example will make the cache key for the page unique to the `user_accepted_cooke_disclaimer` cookie being set.

```php
global $batcache;
$batcache['unique'] = [
	'accepted-cookie-disclaimer' => ! empty( $_COOKIE['user_accepted_cooke_disclaimer'] ),
];
```

## Cache Invalidation

We do not provide an automated way to invalidate page caches, as the TTL is so short. If you require a certain URL be invalidation, contact the Human Made with such requests.

## Debugging

Its not uncommon to need debug why things may or may not be served from the page cache. There's 2 response headers that indicate the cache status, and reasons:

### X-Batcache Header

Describes the status of the origin page cache:

- `HIT` The page was served from the Batcache page cache.
- `MISS` The page was not served from the cache, but has been added to the page cache for subsequent visits.
- `BYPASS` The page was not used, due to exclusion rules such as logged in users, request type (detailed above).

The specific reason for the `BYPASS` in cache is described via the `X-Batcache-Reason` HTTP header, and can be one of the following values:

- `Set-Cookie` The response set a cookie that excludes the page from the cache.
- `Auth Request` The request specified the `Authorization` HTTP header which excludes the page from the cache.
- `Cookies` The request contained cookies matching cookie exclusion rules.
- `Canceled` The backend response made a call to `batcache_cancel()` to force the response not to be cached.
- `No content` The response contained no content,
- `Bad status code` The response returned a 5XX error code.
- `Filename` The request was for an excluded filename.
- `JS Generator` The request was for a `wp-includes/js*` path which is excluded from the cache.

### X-Cache Header

Describes the status of the edge page cache from the CDN:

- `Hit from Cloudfront` the page was served from the Cloudfront edge cache.
- `Miss from Cloudfront` the page was not served from the cache, but has been added to the page cache for subsequent visits.
