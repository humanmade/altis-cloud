# Nginx Configuration

Altis Cloud uses Nginx within your [web containers](./architecture.md). For advanced customization we support additional Nginx
configuration via the project codebase.

**Note:** Custom configuration will only apply to traffic served by the web container, which does not include media or Tachyon (see
the [architecture diagram for more information](./architecture.md)). Specifically, any URLs beginning with `/uploads/`
or `/tachyon/` are routed directly to [S3](./s3-storage.md) and [Tachyon](docs://media/dynamic-images.md) directly.

## Server Configuration

Custom configuration can be supplied in Nginx either within the `server {}` context, or the `http {}` context.

Nginx loads the following custom files from your project repository:

- `.config/nginx-http-additions.conf`: Loaded into the `http` context
- `.config/nginx-additions.conf`: Loaded into the `server` context

An optional suffix can be specified before the extension to split your configuration in complex cases.

Internally, the Altis nginx configuration looks like:

```nginxconf
http {
    include /usr/src/app/.config/nginx-http-additions*.conf;

    # Other nginx setup:
    include /etc/nginx/mime.types;
    # ...

    server {
        listen 80;
        root /usr/src/app;

        include /usr/src/app/.config/nginx-additions*.conf;

        location / {
            # ...
        }

        # Other location blocks:
        # ...
    }
}
```

## Examples

Developers can use this advanced configuration to do complex redirect, rewrites or other server-level routing.

For example, if you want to redirect a specific domain to a new domain and for some reason not able to do this at the PHP /
application layer:

`.config/nginx-additions.conf`

```nginxconf
if ( $host = "example.com" ) {
    rewrite ^ https://sub2.example.com$request_uri? permanent;
}
```

Alternatively, if you have one or more local files in your repository you wish to explicitly block from being publicly accessed you
may manually configure a 404 for a specific resource or filename pattern:

```nginxconf
# Block access to any file entitled `config.local.yaml`.
location ~* config.local.yaml {
    deny all;
    return 404;
}
```

If you wanted to allow a normally blocked location to be accessed, you could use a `location` block to override the default. For
example, Altis blocks all urls beginning with a `.` as these are often sensitive files you do not want to be accessed.

But one use case might be to allow access to specific `.well-known` urls. For example, to allow `Webfinger` requests you would
add the following location block.

```nginxconf
location ~ /\.well-known/webfinger {
    try_files $uri /index.php$is_args$args;
}
```

Note: this is added as a regular expression match `~` to ensure it overrides the default blocking rule.
See the [nginx documentation](https://nginx.org/en/docs/http/ngx_http_core_module.html#location) for details on location matching.
