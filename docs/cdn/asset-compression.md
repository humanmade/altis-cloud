# Compression & Optimisation

Altis automatically compresses and optimises data served by your site using industry-standard methods, across static assets and
dynamic pages.

Compression reduces page load times by reducing overall bandwidth usage, which can also improve SEO performance and as Lighthouse
scores. Altis automatically compresses assets on the server, which are uncompressed by the visitor's browser.

Compression algorithms are selected automatically based on browser support and response types. In some cases, compressing responses
may lead to slower response times (for example, dynamic responses with higher levels of compression that can't be cached), and
Altis may dynamically change or disable compression for these responses.

Compression is applied to static assets (such as JS and CSS files within your codebase), uploaded assets (such as images), and
dynamic HTML responses.

## How do we compress assets?

Assets are compressed using gzip for browsers which indicate support for it. This includes all modern browsers on desktop and
mobile platforms. Like other responses, compressed assets are cached on the CDN for faster delivery to subsequent requests.

We compress the following static assets (based on [MIME type](https://en.wikipedia.org/wiki/Media_type)):

* Plain text (`text/plain`)
* CSS files (`text/css`)
* HTML files (`text/html`)
* JavaScript & JSON files (`application/json`, `application/javascript`, `application/x-javascript`, `text/javascript`)
* XML files (including RSS & SVG) (`text/xml`, `application/xml`, `application/xml+rss`, `image/svg+xml`)
* Font files (`application/x-font-ttf`, `font/opentype`, `application/vnd.ms-fontobject`)

Images are compressed, optimised, and cached separately, through support
for [dynamic images](https://docs.altis-dxp.com/media/dynamic-images/). Optimised and resized images are created on the fly, and
then cached at the CDN layer for a year.

For browsers which support it, images will be automatically converted to [WebP](https://developers.google.com/speed/webp/) format.

## What about Brotli compression?

[Brotli](https://en.wikipedia.org/wiki/Brotli) is a modern compression algorithm developed by Google to replace gzip with higher
compression ratios. [Support for Brotli is available in all modern browsers](https://caniuse.com/brotli), constituting ~96% of
users. Brotli can provide a meaningful boost to compression for non-image assets like JavaScript or CSS.

Altis has partial support for Brotli at the CDN layer. Support for dynamic (`text/html`) responses is not currently available.

Brotli support on Altis is currently available on an opt-in basis. To enable Brotli on your application,
contact [Altis Support](https://docs.altis-dxp.com/guides/getting-help-with-altis/).
