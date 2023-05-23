# Afterburner Cache

Afterburner cache is our object cache drop-in that replaces the existing PHP extension with a high-performing
alternative to turbocharge your WordPress performance.

It works by maintaining a local in-memory object cache, shared across multiple PHP threads, but synchronised with the
main [Redis object cache](./object-cache.md). This reduces the overhead of calls to Redis for the most frequently used
cache items, and in particular, speeds up the WordPress bootstrap process at the start of each request.

The extension keeps in sync with the Redis object cache using PubSub notifications and multi-value data transfers to
maintain the correct state efficiently.

## Safety first features

Should the Redis server disconnect or be unavailable for a period, the local cache will be cleared to prevent
data race conditions.

## Installation

To enable Afterburner, contact [Altis Cloud Support](https://dashboard.altis-dxp.com/#/support) and ask for it to be
enabled for your instance.
