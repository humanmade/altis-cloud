# Static File Caching

All static files on Altis Cloud are cached on the CDN for a long period of time. This ensures the best performance when downloading static assets such as images, JavaScript and CSS files. Due to the aggressive caching times, when files are changed it is necessary reference the files by a new URL that will cause a different version to be saved on the CDN.

Because query strings are part of the cache key when the file is cached on the CDN, in many cases it's simplest to reference the file with a changed `?version` query parameter. This behavior is automatic when providing the `$version` parameter to the `wp_enqueue_script` and `wp_enqueue_style` functions.

```php
wp_enqueue_script( 'my-theme', get_stylesheet_uri(), [], '2019-05-4-1' );
```

This will enqueue and output a `<script>` tag with a URL appended with `?ver=2019-05-4-1`. Every time the `style.css` is changed, the `$version` parameter needs to be updated.

The `$version` parameter can be defined dynamically, though should be optimized to only change when the file has changed. To reduce disk IO, you should not read the file at runtime to generate a version, such as `filemtime` or `md5_file`. Using Webpack or other build-time workflows to build assets on deploy will provide unique filenames that can provide hard coded values.
