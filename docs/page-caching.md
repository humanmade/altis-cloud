# Page Caching

The majority of the pages viewed on Altis will be cached to improve performance and delivery time to the user. It is necessary to take into account the page cache when developing - considerations are listed at the bottom of this page.

There are 2 levels of page caching:

- CDN caching, served from [regional cache nodes](docs://cloud/cdn/)
- PHP caching, served from PHP using [Batcache](https://github.com/humanmade/batcache)

The CDN cache is the fastest cache, and has typical response times around 20ms. If a page cannot be served from the CDN cache, it may fall back to the Batcache cache. This is served from PHP on the application server, and has typical response times around 100-200ms.

All pages are cached by default, with the following exclusions:

- All `POST` requests are not cached
- Any requests with cookies matching the patterns `wordpress_*`, `wp-*`, `wp_*`, `comment_*`, `hm_*` and  `altis_*` are not cached.
- Any requests with the `Authentication` HTTP header will not be cached.
- Any response with the `Cache-Control: no-cache` HTTP header will not be cached.
- Any response with no content is not cached.
- Any request to `xmlrpc.php`, `wp-cron.php` or `wp-app.php` are not cached.
- Any request to `wp-includes/js*` are not cached.

**Note:** Page caching is disabled on local environments, and due to non-production environments requiring authentication, will likely not be active during testing and QA. See the [configuration](#configuration) section below for testing instructions.


## Cache Key Calculation

Each page is stored within cache storage as identified by a cache key. The cache key follows the pattern:

```
{method}:{protocol}:{host}:{path}:{query_params}
```

Query parameters can be [customized](#customizations) to ignore certain query parameters which do not effect the page content.

**Note:** The cache key for the CDN cache is fixed, and query parameters can not be ignored within the CDN cache. The CDN cache key cannot be changed.


## Time-to-live and maximum age

Cacheable pages have default time-to-live (TTL) values within the cache based on the response code:

- Success responses (`200`): 300 seconds (5 minutes)
- Client errors (`400`, except `404`): 10 seconds
- Not found errors (`404`): 300 seconds (5 minutes)
- Server errors (`5XX`): 300 seconds (5 minutes)

Time-to-live values should be balanced between achieving high cache hit rates and ensuring fresh content is served.

This can be adjusted by sending a [`Cache-Control: max-age=x` HTTP header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#response_directives) within your response, which will override any defaults.

For example, to specify a lifetime of one hour for a specific page:

```php
header( 'Cache-Control: max-age=' . HOUR_IN_SECONDS );
```

(Note that headers should be sent before any output occurs on the page.)


## Developer Considerations

Any cacheable page should not include references to any user-data in the request. This is because subsequent requests from other users will receive the same cached page.

This means headers such as `User-Agent`, `Referer`, or the client IP (`$_SERVER['REMOTE_IP']`) will not be accurate for users viewing the cached content. Content should not be varied based on these attributes; use [client-side personalization with Experience Blocks](docs://analytics/analytics/native/experience-blocks/) instead, which is fully compatible with page caches.

Non-cacheable requests (such as logged-in users) can safely use these headers.

**Note:** The User-Agent header is set to a static value by Altis and cannot be used server-side for feature detection.


## Customizations

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
							"utm_term",
							"mc_cid",
							"mc_eid",
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

**Note:** These customizations do not apply to the CDN cache, only within Batcache.


### Ignored Query String Parameters

Some query string parameters have no effect on the content or output of the page. You can improve your cache hit rate by adding these parameters to the ignore list, which will instruct Batcache to treat the different URLs as equivalent.

The page cache will filter these parameters out when generating the cache key as specified in the [Cache Key Calculation](#cache-key-calculation) section above, and will cause the cache key to be shared across more pages, improving your cache hit rate.


### Headers

If you need to generate different content on the server side based on an HTTP request header, you can add those to the `unique-headers` property. This will add those header values into the key calculation.

For example in conjunction with [geo targeting](./geo-targeting.md) you could add `Cloudfront-Viewer-Country` to vary the generated page cache key.

**Note:** This will not change the value on the CDN cache. Enterprise customers can contact Altis support to apply customisations at the CDN level.


### Cookies

If the presence of a particular cookie means that the generated page should be unique you add the cookie name to the `unique-cookies` property. It's recommended to name the cookies accordingly to a current exclusion pattern if you want to access them via PHP eg. `wp_*`.

**Note:** This will not change the value on the CDN cache. Enterprise customers can contact Altis support to apply customisations at the CDN level.


### Unique keys

If the above configurations don't meet your needs you can add your own cache vary keys directly in PHP. The recommended place to do this is in a file under the `.config/` directory in a file included via `.config/load.php` to ensure it runs early enough.

The following example will make the cache key for the page unique to the current codebase commit hash.

```php
global $batcache;
$batcache['unique']['revision'] = Altis\get_environment_codebase_revision();
```


## Cache Invalidation

Caches are automatically invalidated when their time-to-live expires.

The CDN cache can be [manually purged](https://docs.altis-dxp.com/cloud/cdn/purge/) through a function call. This should be used sparingly, as it will negatively affect cache hit rates, and incurs additional stress on the CDN system. This functionality is monitored, and excessive use may be considered a violation of your terms of service.

It is not currently possible to clear Batcache cache values manually. As Batcache cache values are stored within the object cache, the object cache can be cleared if Batcache needs to be cleared, however this also must be used sparingly.


## Debugging

Altis sends response headers indicating the cache status, allowing you to debug the cache behaviour.

Since Altis operates a layered cache system, the CDN cache status (`X-Cache`) supersedes the Batcache status (`X-Batcache`).

### X-Cache Header

Describes the status of the edge page cache from the CDN:

- `Hit from Cloudfront` - the page was served from the regional edge cache by the CDN.
- `Miss from Cloudfront` - the page was not served from the CDN cache, but may have been added to the page cache for subsequent visits.

Additionally, the `x-amz-cf-pop` header indicates the CloudFront point-of-presence (POP) being accessed. The first three characters indicate which CloudFront edge the response was served from.

### X-Batcache Header

Describes the status of the origin page cache:

- `HIT` - The page was served from the Batcache page cache.
- `MISS` - The page was not served from the cache, but has been added to the page cache for subsequent visits.
- `BYPASS` - The page was not used, due to exclusion rules such as logged in users, request type (detailed above).

**Note:** The `X-Batcache` header may indicate a `MISS` on pages with `X-Cache: Hit from Cloudfront`. This indicates the page was served from the CDN cache, and the original request which was cached by the CDN *also* missed the Batcache cache.

The specific reason for the `BYPASS` in cache is described via the `X-Batcache-Reason` HTTP header, and can be one of the following values:

- `Set-Cookie` - The response set a cookie that excludes the page from the cache.
- `Auth Request` - The request specified the `Authorization` HTTP header which excludes the page from the cache.
- `Cookies` - The request contained cookies matching cookie exclusion rules.
- `Canceled` - The backend response made a call to `batcache_cancel()` to force the response not to be cached.
- `No content` - The response contained no content,
- `Bad status code` - The response returned a 5XX error code.
- `Filename` - The request was for an excluded filename.
- `JS Generator` - The request was for a `wp-includes/js*` path which is excluded from the cache.


### Configuration

PHP page caching can be toggled using the Altis config. By default Batcache is active for cloud environments but not on local environments.

This can be toggled by changing the following setting to `true` or `false`:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"cloud": {
					"batcache": false
				}
			}
		}
	}
}
```

For developing cache customizations locally, you may wish to enable Batcache for local environments:

```json
{
	"extra": {
		"altis": {
			"environments": {
				"local": {
					"modules": {
						"cloud": {
							"batcache": true
						}
					}
				}
			}
		}
	}
}
```
