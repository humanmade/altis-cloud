# Asset Compression Strategy

### What and why compress assets?

For faster load speeds of your page, static assets are compressed at server, and then uncompressed by an end users browser. This is a contributing factor to SEO scores, but generally good practice to help reduce overall bandwidth usage. 

Those static assets include .js or CSS files, images, and also the generated HTML files of your site.

### How do we compress assets?

Assets are compressed using GZIP and is done via NGINX. When they’re sent as a response to a request they’ll also then be cached at the CDN for faster delivery of that assets to subsequent requests.

The [MIME types](https://en.wikipedia.org/wiki/Media_type) we compress are listed below.

`text/plain, text/css, text/html, application/json, application/javascript, application/x-javascript, text/xml, application/xml, application/xml+rss, text/javascript, application/x-font-ttf, font/opentype, application/vnd.ms-fontobject, image/svg+xml`

Images are compressed and cached separately, as we leverage [Dynamic Image Resizing](https://docs.altis-dxp.com/media/dynamic-images/), the Images are created on the fly, and then cached at the CDN layer for serving the image. Images will attempt to be served via `[WebP](https://developers.google.com/speed/webp/)` format if the browser supports it, falling back to the image default format.

### I’ve heard of Brotli compression, and doesn’t it offer better compression?

It can provide a meaningful boost to compression for non-image assets like javascript or CSS. But support for Brotli compression is only partially supported.

If you’re do want to use Brotli for compression we can enable Brotli for on-the-fly compression at the CDN layer, but with the exception of the MIME type of `text/html` which will have to be compressed with GZIP, due to the dynamic nature of the HTML generation.

### Further reading:

For more information about some of the services and concepts talked about above, see the following articles.

[https://docs.altis-dxp.com/cloud/cdn/](https://docs.altis-dxp.com/cloud/cdn/)

[https://docs.altis-dxp.com/cloud/static-file-caching/](https://docs.altis-dxp.com/cloud/static-file-caching/)

[https://docs.altis-dxp.com/cloud/page-caching/](https://docs.altis-dxp.com/cloud/page-caching/)

[https://docs.altis-dxp.com/media/dynamic-images/](https://docs.altis-dxp.com/media/dynamic-images/)