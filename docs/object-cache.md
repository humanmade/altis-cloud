# Object Caching

Altis Cloud environments come with a [Redis](https://redis.io/) service for rapid storage and retrieval of data.

Altis provides a low-level integration with WordPress's object caching system out of the box to help your application
perform as well as possible. The object cache is used for the following by default:

- [Page caching via Batcache](./page-caching.md)
- WordPress object cache
- Database queries
- [WordPress transients](https://developer.wordpress.org/apis/handbook/transients/)

The object cache is generally used to "offload" work from the [database servers](./database.md), using constant-time lookup rather
than complex relational queries.

## Transients

Transients are a feature of WordPress that behave similarly to the Options API. The intention is to use the store data that should
expire, or should not always be expected to be available. You might use these to cache the results of a `GET` API requests for
example.

It is important to note that a transient's expiry time is the _maximum_ amount time it can be cached for.

An example use case might look like the following:

```php
function fetch_posts() : ?array {
    $cache = get_transient( 'external-posts' );
    if ( ! empty( $cache ) ) {
        return $cache;
    }

    $response = wp_remote_get( 'https://api.tumblr.com/v2/blog/staff.tumblr.com/posts' );

    if ( is_wp_error( $response ) ) {
        return null;
    }

    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return null;
    }

    $result = json_decode( wp_remote_retrieve_response_body( $response ) );

    set_transient( 'external-posts', $result, HOUR_IN_SECONDS );

    return $result;
}
```

**`set_transient( string $name, mixed $value, int $expiration )`**

Stores a transient value. The `$value` can be anything and might be serialized if anything other than a scalar value is
given. `$expiration` is the maximum amount of time in seconds the value should be stored for.

**`get_transient( string $name )`**

Retrieves a transient value.

**`set_site_transient( string $name, mixed $value, int $expiration )`**

Network level version of `set_transient()`.

**`get_site_transient( string $name )`**

Network level version of `get_transient()`.

**`delete_transient( string $name )`**

Deletes a transient by name.

**`delete_site_transient( string $name )`**

Network level version of `delete_transient()`.

## Object Caching Functions

In your application code the object cache exposes a set of functions for you to use directly. It is recommended to use these
functions where you have code carrying out repetitive CPU intensive processes, and in some cases for caching complex templates like
menus.

These functions are a lower level interface than transients, and have the benefit of accepting cache groups and timestamps for
expiry. Using cache groups can

**`wp_cache_set( string $key, mixed $data, string $group = '', ?int $expiry )`**

Cache any arbitrary value, with an optional group and expiry time. `$expiry` can be a timestamp for a future date.

**`wp_cache_replace( string $key, mixed $data, string $group = '', ?int $expiry )`**

This is the same as `wp_cache_set()` with the exception that it will not add the value to the cache if it does not already exist.

**`wp_cache_get( string $key, string $group = '', bool $force = false, &$found = null )`**

Retrieve a value from the cache. The `$force` option if true will force update the in-memory cache for the current PHP process.

If the value you need to cache can be `false` or other false-y value you can set the `$found` variable by reference and check it to
see if a value was found or if the object cache does not contain a value for the key and group yet. For example:

```php
$cache = wp_cache_get( 'posts', 'my-app', true, $found );
if ( $found ) {
    return $cache;
}
```

**`wp_cache_delete( string $key, string $group = '' )`**

Deletes an item from the object cache.

**`wp_cache_flush( int $delay = 0 )`**

Flushes the entire object cache with an optional delay in seconds to wait.

**`wp_cache_add_non_persistent_groups( array $groups )`**

Passing a list of group names will change the object caching behaviour for those groups to avoid storing values in the persistent
cache.

Use this if you need to only cache values for the current PHP process.

## Scaling and Performance

While Redis is a type of database like [the MySQL store](./database.md), it has vastly different performance and scaling
characteristics. The Altis team manages the Redis server size based on traffic, CPU usage, subscription, and other metrics.

On environment tiers with high-availability, a hot-standby replica is established in a different availability zone to protect
against underlying hardware or data center failures.

Use of Redis via the transient or object cache APIs (as detailed below) generally uses Redis as a key-value store, which serves to
improve performance as lookup in Redis can be constant-time (O(1)).

Unlike the primary database, writes to the Redis data store can safely be made from the frontend and will perform well Note that as
with any component of your codebase, care should still be taken to ensure your code performs well at scale. Excessive writes to the
object cache may require increasing the infrastructure provisioned for your environment, and additional charges may apply if you
significantly exceed typical usage.

## Common Performance Problems

The most common problems we experience with environments when scaling Redis are **excessive eviction** and **network link saturation
**.

### Excessive Eviction

Excessive eviction occurs when Redis has too many values stored compared to the available memory. Redis will automatically manage
entries using the `allkeys-lfu` eviction policy, which removes the least-frequently-used values.

When storing many values, it's possible for frequently used items to be evicted, as Redis isn't able to hold all the items in memory
at once. This will cause caches to expire almost immediately, and can lead to massive performance problems or excessive database
usage.

Some amount of eviction is normal, particularly when using key-based caching (e.g. including version numbers or timestamps in the
key). WordPress uses key-based caching in some places, so some eviction will always occur.

The Altis team monitors your eviction rate and will alert you if it exceeds typical values. Information about your eviction rate can
be provided on request to Altis support.

### Network Link Saturation

While Altis manages your infrastructure for you and scales environments appropriately for your usage, it's possible to saturate the
network link between the application servers and the cache servers even with relatively low usage.

Storing excessively large values in the object cache which are frequently access can lead to saturation. This can occur when storing
large values in a single, autoloaded key, such as large serialized arrays. (Typically, this only occurs for values under 100MB in
size.)

While there are some cases where storing large values in Redis makes sense, generally this usage should be avoided. Instead, use
functions like `wp_cache_get_multiple()` when dealing with many independent values, or store blobs directly
in [S3](./s3-storage.md).

The Altis team monitors your network link bandwidth and will alert you if it exceeds typical values. In order to ensure the
stability of your environment, Altis may require you to redesign your object cache usage if you continually saturate network links.

## Configuration

Object cache behaviour is generally not user configurable, and is handled automatically for you by the Cloud module.

**Note:** Any changes to the cache configuration are considered to void your warranty, except as directed by the Altis team.
Adjusting any configuration may cause catastrophic errors in your environments.

The Redis integration can be toggled using the following configuration:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "redis": false
                }
            }
        }
    }
}
```
