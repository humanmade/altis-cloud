# Page Caching

The majority of the pages viewed on Altis will be cached to improve performance and delivery time to the user. It is necessary to take into account the page cache when developing - considerations are listed at the bottom of this page. All pages are cached by default, and the following rules excluded specific cases:

- All `POST` requests are not cached
- Any requests with cookies matching the patterns `wordpress_*`, `wp-*`, `wp_*`, `comment_*` and `hm_*` are not cached.
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

The page cache can be customised via your `composer.json`. The example below shows the default configuration:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"cloud": {
					"page-cache": {
						"ignored-query-string-params": [
							"utm_campaign",
							"utm_medium",
							"utm_source",
							"utm_content",
							"fbclid",
							"_ga"
						],
						"unique-headers": [],
						"unique-cookies": []
					}
				}
			}
		}
	}
}
```

### Ignored Query String Parameters

Some query string parameters have no effect on the content or output of the page. You can make the page cache and site more efficient by including all of these to an ignore list. The page cache will filter these out when generating the cache key as specified in the [Cache Key Calculation](#cache-key-calculation) section above.

### Headers

If you need to generate different content on the server side based on an HTTP request header you can add those to the `unique-headers` property. For example in conjunction with [geo targeting](./geo-targeting.md) you could add `Cloudfront-Viewer-Country` to vary the generated page cache key.

### Cookies

If the presence of a particular cookie means that the generated page should be unique you add the cookie name to the `unique-cookies` property. It's recommended to name the cookies accordingly to a current exclusion pattern if you want to access them via PHP eg. `wp_*`.

If you need a custom cookie name to be excluded at the CloudFront level the patterns can be modified at request by Altis support.

### Custom Rules

If the above configurations don't meet your needs you can add your own cache vary keys directly in PHP. The recommended place to do this is in a file under the `.config/` directory in a file included via `.config/load.php` to ensure it runs early enough.

The following example will make the cache key for the page unique to the current codebase commit hash.

```php
global $batcache;
$batcache['unique']['revision'] = Altis\get_environment_codebase_revision();
```

## Cache Invalidation

We do not provide an automated way to invalidate page caches, as the TTL is so short. If you require a certain URL be invalidation, contact Altis support with such requests.

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
