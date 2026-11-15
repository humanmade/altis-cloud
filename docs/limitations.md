---
order: 90
---

# Limitations

There are several design decisions, typically for performance and security that impose some limitations on developers who may be
used to certain practices. We've tried to document practical limitations of Altis, with rationale to assist developers in writing
more bug free, optimized code on Altis.

## Filesystem Not Writable

The root filesystem outside of the uploads directory (returned by `wp_upload_dir()`) is not writable on Altis. It's also possible to
use `sys_get_temp_dir()` to write to the temporary filesystem when needed. This is to protect code-base integrity, and prevent
malicious code being able to adjust the code base in the event of a security breach.

As such, plugin and WordPress Core updates can not be performed from within the WordPress Admin, and should always instead be made
via the GitHub repository as a code change.

## Uploads Directory Stored Remotely

On Altis the uploads directory is a remote stream wrapper, this means you should not perform filesystem reads excessively. For
example, no page render should cause a filesystem read. Store any details you need to render a page about a file in the database.

## User Agent Not Available

Due to caching rules, the User Agent is not detectable from PHP. Any User Agent specific requirements (such as targeting mobile
devices) can be enabled by Human Made to provide device-type headers, or such checks should be performed in JavaScript.

This also means the `wp_is_mobile` function will not work as intended, and should not be used.

## Referrer-Dependent Content and Page Caching

The HTTP `Referer` header is forwarded to PHP when supplied by the browser and the request reaches the backend. It is not part of
the page-cache key, so cacheable responses must not vary based on it. Non-cacheable requests, such as those from logged-in users,
can use the header, but browsers may omit it or limit its contents according to their referrer policy.

For referrer-dependent behaviour on cached pages, pass the required value explicitly as a `GET` parameter that is included in the
cache key, or use a non-cacheable `POST` request. See [Page Caching](./page-caching.md#developer-considerations).

## Web Requests to PHP Files

Direct access to PHP files is not allowed on Altis, outside of `/wp-admin/*`. For example, a request
for `https://example.com/wp-content/plugins/my-plugin.php` will fail. All requests for PHP should be routed via WordPress' rewrite
rules or similar.

## Execution Time Limit

All web requests are subject to a 60-second maximum execution. Anything that requires more time than this should be offloaded to a
background cron task using `wp_schedule_single_event()` or similar. See [Scheduled tasks](./scheduled-tasks.md) for more
information.

## No Custom PHP Modules

We do not support requests for custom PHP modules. The PHP modules we have installed are listed on
the [specifications](./specifications.md) page. Contact Human Made to discuss the inclusion of other modules in this list.

## `WP_Filesystem` Not Supported

The `WP_filesystem` WordPress API is not currently supported on Altis, this is typically only used in plugin / WordPress Core
updates which should instead be performed by GitHub Pull Requests.
