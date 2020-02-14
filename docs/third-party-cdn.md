# Third Party CDN Usage

In some situations it may be required to use your own Content Delivery Network (CDN) to serve the Altis powered website or service. Below are some examples that may necessitate this:

- Many web properties are served from a single domain name, whereby you need to control routing. A sub-section of the URL namespace may route to Altis, in which case it's not possible to route all traffic to Altis' CDN for a single domain name.
- There may be compliance requirements or company-wide mandated use of a specific CDN vendor.

In these cases, Altis Cloud can be configured to serve traffic to your CDN via an origin-pull setup. Altis does not support an origin-push CDN setup. Reach out to [Altis Cloud Support](https://dashboard.altis-dxp.com/#/support) for assistance in third party CDN integration as it requires specific manual work by the Altis team.

## CDN Configuration

Altis Cloud will provide origin DNS names for each Cloud environment (`development`, `staging` and `production`) to set as the Origin-Pull upstream for your CDN. For example, this could be `development.example.altis.cloud`. The CDN should be configured with the logic described below, in whatever format or system the CDN uses. See below for special routes `/tachyon/*` and `/uploads/*`.

### Cookies

All cookies matching the pattern `wordpress_*`, `wp-*`, `wp_*`, `comment_*` and `hm_*` should be forwarded to the Altis Cloud origin.

### URL Query Parameters

All URL query parameters should be forwarded to the Altis Cloud origin.

### HTTP Headers

The following HTTP headers should be forwarded: `Host`, `Origin`, `Authorization`.

### Caching Configuration

The CDN caching rules should be set to respect the origin's `Cache-Control` HTTP header. For example, if an origin response includes the header `Cache-Control: max-age=300`, the CDN should cache the response for 300 seconds.

The following type of requests should be set to skip the CDN cache entirely:

- All `POST`, `PUT`, `PATCH` or `DELETE` requests.
- Any requests with cookies as described above.
- Any requests with the `Authentication` HTTP header.
- Any response with the `Cache-Control: no-cache` HTTP header.

The cache key should be based on the following string: `{http method}:{protocol}:{host}:{path}:{query_params}`.

## `/uploads/*` and `/tachyon/*` Requests

For all requests sent to `/uploads/*` and `/tachyon/*`, the above rules can be optimized to increase the cache-hit ratio and improve performance for users.

- Cookies: All cookies can be removed from the origin request
- Headers: Only the `Host` header needs to be forwarded to the origin.
- URL Query Parameters: All url query parameters should still be forwarded.
- Caching Configuration: The `Cache-Control` response header from the origin should be respected, and no further exceptions are required.

## WebP Support on `/tachyon/*` Requests

Altis Cloud supports serving the [WebP](https://en.wikipedia.org/wiki/WebP) image format to supported browsers. Support for WebP is detected by inspecting the `Accept` HTTP request header. When using a third party CDN, detection for WebP support should be implemented on the CDN edge node, and the `X-WebP` HTTP header should be set to `1` or `0` in the `tachyon/*` origin request. As a reference implementation, WebP detection can be achieved with the follow pseudo code:

```
webPSupport = request.headers.Accept.indexOf( 'image/webp' ) > -1
```

The WebP Support should be included in the cache key calculation on the CDN, so WebP responses are cached/stored separately. Failure to do so will result in browsers receiving WebP formatted images that do not support it.

## Access Restriction

When using a third party CDN, access to the Altis Cloud environment may need to be restricted to the CDN, and not directly publicly available. If this is required, provide the [CIDR](https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing) IP Address ranges of your CDN origin request servers to Altis Cloud Support to set up access restriction accordingly.
