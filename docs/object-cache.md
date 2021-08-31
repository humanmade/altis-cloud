# Object Caching

The Altis cloud infrastructure comes with a [Redis](https://redis.io/) service for rapid storage and retrieval of data.

Altis provides a low-level integration with WordPress's object caching system out of the box to keep your application as performant as possible. The object cache is used for the following by default:

- [Page caching via Batcache](./page-caching.md)
- WordPress object cache
- Database queries
- [WordPress transients](https://developer.wordpress.org/apis/handbook/transients/)

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

## Transients

Transients are a feature of WordPress that behave similarly to the Options API. The intention is to use the store data that should expire, or should not always be expected to be available. You might use these to cache the results of a `GET` API requests for example.

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

Stores a transient value. The `$value` can be anything and might be serialized if anything other than a scalar value is given. `$expiration` is the maximum amount of time in seconds the value should be stored for.

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

In your application code the object cache exposes a set of functions for you to use directly. It is recommended to use these functions where you have code carrying out repetitive CPU intensive processes, and in some cases for caching complex templates like menus.

These functions are a lower level interface than transients, and have the benefit of accepting cache groups and timestamps for expiry. Using cache groups can

**`wp_cache_set( string $key, mixed $data, string $group = '', ?int $expiry )`**

Cache any arbitrary value, with an optional group and expiry time. `$expiry` can be a timestamp for a future date.

**`wp_cache_replace( string $key, mixed $data, string $group = '', ?int $expiry )`**

This is the same as `wp_cache_set()` with the exception that it will not add the value to the cache if it does not already exist.

**`wp_cache_get( string $key, string $group = '', bool $force = false, &$found = null )`**

Retrieve a value from the cache. The `$force` option if true will force update the in-memory cache for the current PHP process.

If the value you need to cache can be `false` or other falsey value you can set the `$found` variable by reference and check it to see if a value was found or if the object cache does not contain a value for the key and group yet. For example:

```php
$cache = wp_cache_get( 'posts', 'my-app', true, $found );
if ( $found ) {
    return $cache;
}
```

**`wp_cache_delete( string $key, string $group = '' )`**

Deletes an item from the obejct cache.

**`wp_cache_flush( int $delay = 0 )`**

Flushes the entire object cache with an optional delay in seconds to wait.

**`wp_cache_add_non_persistent_groups( array $groups )`**

Passing a list of group names will change the object caching behaviour for those groups to avoid storing values in the persistent cache.

Use this if you need to only cache values for the current PHP process.
