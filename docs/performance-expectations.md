# Performance Expectations

## CDN Response Times

Altis Cloud serves [static files](./static-file-caching.md) and the [page cache](./page-caching.md) from the CDN. In cases when static files and pages are already cached on the CDN, response times for client requests are typically between 10 miliseconds and 30 miliseconds. This can depend on the quality of connection from the client. See [CDN](./cdn/README.md) for details on Edge Cache locations.

## Origin Cache Response Times

The origin cache (also called Batcache) is documented in [page cache](./page-caching.md). Response times for Batcache HIT requests are typically between 30 miliseconds and 60 miliseconds.

## Origin Response Times

Origin response times when PHP / WordPress dynmically generate a page can vary dignificantly depending on the site's implementation (plugins used, WordPress theme, database queries etc). We're provided the following guidelines for page generation time to help teams better understand the typical "base" response times. These figures may not be illustritive of all projects given how many factors dictate the performance of WordPress based sites.

|Page Generation Type|Average (miliseconds)|90th Percentile (miliseconds)|
|--|--|--|
|New WordPress Install + `Twenty Twenty Two` theme homepage.|150|450|
