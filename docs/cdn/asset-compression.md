# Compression & Optimisation
Altis automatically compresses and optimises data served by your site using industry-standard methods, across static assets and dynamic pages.

### What and why compress assets?

Compression reduces page load times by reducing overall bandwidth usage, which can also improve SEO performance and as Lighthouse scores. Altis automatically compresses assets on the server, which are uncompressed by the visitor's browser.

Compression algorithms are selected automatically based on browser support and response types. In some cases, compressing responses may lead to slower response times (for example, dynamic uncachable responses with higher levels of compression), and Altis may dynamically change or disable compression for these responses.

Compression is applied to static assets (such as JS and CSS files within your codebase), uploaded assets (such as images), and dynamic HTML responses.

### How do we compress assets?

Assets are compressed using GZIP and is done via NGINX. When they’re sent as a response to a request that asset will also be cached at the CDN for faster delivery of that assets to subsequent requests.

The [MIME types](https://en.wikipedia.org/wiki/Media_type) we compress are listed below.

`text/plain, text/css, text/html, application/json, application/javascript, application/x-javascript, text/xml, application/xml, application/xml+rss, text/javascript, application/x-font-ttf, font/opentype, application/vnd.ms-fontobject, image/svg+xml`

Images are compressed, optimised, and cached separately, through support for [dynamic images](https://docs.altis-dxp.com/media/dynamic-images/). Optimised and resized images are created on the fly, and then cached at the CDN layer for a year.

For browsers which support it, images will be automatically converted to [WebP](https://developers.google.com/speed/webp/) format.

### What about Brotli compression?

It can provide a meaningful boost to compression for non-image assets like javascript or CSS. Brotli compression is only partially supported, meaning it can only be used on-the-fly by at the CDN, and not for `text/html` MIME Types and [not all browsers support Brotli](https://docs.w3cub.com/browser_support_tables/brotli).

Brotli support on Altis is currently available on an opt-in basis. To enable Brotli on your application, contact [Altis Support](https://docs.altis-dxp.com/guides/getting-help-with-altis/).

### Further reading:

For more information about some of the services and concepts talked about above, see the following articles.

[https://docs.altis-dxp.com/cloud/cdn/](https://docs.altis-dxp.com/cloud/cdn/)

[https://docs.altis-dxp.com/cloud/static-file-caching/](https://docs.altis-dxp.com/cloud/static-file-caching/)

[https://docs.altis-dxp.com/cloud/page-caching/](https://docs.altis-dxp.com/cloud/page-caching/)

[https://docs.altis-dxp.com/media/dynamic-images/](https://docs.altis-dxp.com/media/dynamic-images/)