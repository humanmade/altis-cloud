# Performance Expectations

## CDN Response Times

Altis Cloud serves [static files](./static-file-caching.md) and the [page cache](./page-caching.md) from the CDN. In cases when static files and pages are already cached on the CDN, response times for client requests are typically between 10 milliseconds and 30 milliseconds.

Response times can depend on the quality of connection from the client. See [CDN](./cdn/README.md) for details on Edge Cache locations.

## Origin Cache Response Times

The origin cache (also called Batcache) is documented in [page cache](./page-caching.md). Response times for Batcache HIT requests are typically between 30 milliseconds and 60 milliseconds.

## Origin Response Times

Origin response times when PHP / WordPress dynamically generates a page can vary significantly depending on the site's implementation, including which plugins are used, the active WordPress theme, how many database queries are used, etc. The included [development tools](docs://dev-tools/) help with visibility of these statistics.

We're provided the following guidelines for page generation time to help teams better understand the typical "base" response times, based on our real-world experience.

These figures may not be illustrative of all projects, but can provide a guideline for optimisation.

Page Generation Type | Average (milliseconds) | 90th Percentile (milliseconds)
-- | -- | --
New WordPress Install + `Twenty Twenty Two` theme homepage. | 150 | 450
