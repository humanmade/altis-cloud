# Performance Considerations

For performance reasons, there are several WordPress functions, arguments and practices that are either highly discouraged or will be rejected when code reviewed.

## WordPress Functions

There are several areas in WordPress that _allow_ for poorly performing code.

### WP_Query

When using `WP_Query`, `get_posts`, `get_children` or other wrapping functions, you should avoid using the following parameters:

- `meta_value`: Any `meta_query` that uses a `meta_value` clause should not be used. WordPress doesn't have a MySQL index on the `meta_value` field, so query times can be very long. Consider storing flags via the existence of a meta key (by using `EXISTS` in `meta_compare`), storing lookup values in the `meta_key`.
- `showposts => -1` (or similar): Never make unbounded `WP_Query` instances. Even in CLI scripts it's best to paginate requests.
- `s`: Using the in-build `WP_Query` is very slow, though this parameter is OK to use if you have Elasticsearch enabled.

### attachment_url_to_postid

This function uses a `meta_value` query internally, avoid using it wherever possible. If you need to, make sure you cache the results in a long-lived object cache item.

## Remote Requests

Avoid making remote requests on any page render, or other idempotent GET request. Code that does so will typically be rejected at code review time. Parts of the page render that require data from a remote resource should use background tasks to push the remote data to a long-lived object cache item.

Background tasks can be created using the functions `wp_schedule_single_event()` or `wp_schedule_event()`.

The following example shows how to schedule a task to run an hour after `wp_schedule_single_event()` is called:

```php
function do_this_in_an_hour( $arg1, $arg2, $arg3 ) {
    // Do something
}

add_action( 'my_new_event', 'do_this_in_an_hour', 10, 3 );

// put this line inside a function,
// presumably in response to something the user does
// otherwise it will schedule a new event on every page visit

wp_schedule_single_event( time() + 3600, 'my_new_event', array( $arg1, $arg2, $arg3 ) );

// time() + 3600 = one hour from now.
```

More information can be found in the WordPress developer documentation for creating [one time events](https://developer.wordpress.org/reference/functions/wp_schedule_single_event/) and [recurring events](https://developer.wordpress.org/reference/functions/wp_schedule_event/).

## SQL Queries

Avoid SQL `UPDATE` or `INSERT` queries on any page render or other idempotent request. SQL updates should only be done via admin requests or background tasks.
