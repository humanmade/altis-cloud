# Performance Considerations

For performance reasons, there are several WordPress functions, arguments and practices that are either highly discouraged or will be rejected when code reviewed.

## WordPress Functions

There are several areas in WordPress that _allow_ for poorly performing code.

### WP_Query

When using `WP_Query`, `get_posts`, `get_children` or other wrapping functions, you should avoid using the following parameters:

- `meta_value`: Any `meta_query` that uses a `meta_value` clause should not be used. WordPress doesn't have a MySQL index on the `meta_value` field, so query times can be very long. Consider storing flags via the existence of a meta key (by using `EXISTS` in `meta_compare`), storing lookup values in the `meta_key`.
- `showposts => -1` (or similar): Never make unbounded `WP_Query` instances.
- `s`: Using the in-build `WP_Query` is very slow, though this parameter is OK to use if you have Elasticsearch enabled.

### attachment_url_to_postid

This function uses a `meta_value` query internally, avoid using it wherever possible. If you need to, make sure you cache the results in a long-lived object cache item.

## Remote Requests

Avoid making remote requests on any page render, or other idempotent GET request. Code that does so will typically be rejected at code review time. Parts of the page render that require data from a remote resource should use background tasks to push the remote data to a long-lived object cache item.

## SQL Queries

Avoid SQL `UPDATE` or `INSERT` queries on any page render or other idempotent request. SQL updates should only be done via admin requests or background tasks.

