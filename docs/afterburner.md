# Afterburner

Altis Afterburner is our PHP extension designed specifically to turbocharge your
WordPress performance on Altis Cloud.

## Afterburner Cache

Afterburner cache is our object cache drop-in that replaces the existing PHP
extension with a high-performing
alternative to turbocharge your WordPress performance.

It works by maintaining a local in-memory object cache, shared across multiple
PHP threads, but synchronised with the main [Redis object cache](./object-cache.md). 
This reduces the overhead of calls to Redis for the most frequently used cache 
items, and in particular, speeds up the WordPress bootstrap process at the 
start of each request.

The extension keeps in sync with the Redis object cache using PubSub
notifications and multi-value data transfers to
maintain the correct state efficiently.

## Safety first features

Should the Redis server disconnect or be unavailable for a period, the local
cache will be cleared to prevent data race conditions.

## Activation

To activate Afterburner on your environment, 
[create a support ticket](support://new) with the request. It requires Altis 16.0.4+ to work as expected, as this contains Batcache 1.5.2's compatibility fixes.
