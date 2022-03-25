# Nginx Configuration

Altis Cloud uses Nginx within your [web containers](./architecture.md). For advanced customization we support additional Nginx configuration via the project codebase.

**Note:** Custom configuration will only apply to traffic served by the web container, which does not include media or tachyon (see the [architecture diagram for more information](./architecture.md)). Specifically, any URLs beginning with `/uploads/` or `/tachyon/` are routed directly to [S3](./s3-storage.md) and [Tachyon](docs://media/dynamic-images.md) directly.


## Providing Configuration

Nginx will look for an additional file in your project repository at `.config/nginx-additions.conf` and will load it into the `server {}` context of the main configuration. You can also use filename suffixes to split your configuration in complex cases.

Internally, the Altis Nginx configuration looks like:

```
server {
    listen 80;
    root /usr/src/app;

    include /usr/src/app/.config/nginx-additions*.conf;

    location / {
        # ...
    }

    # ...
}
```


## Examples

Developers can use this advanced configuration to do complex redirect, rewrites or other server-level routing.

For example, if you want to redirect a specific domain to a new domain and for some reason not able to do this at the PHP / application layer:

`.config/nginx-additions.conf`

```
if ( $host = "example.com" ) {
    rewrite ^ https://sub2.example.com$request_uri? permanent;
}
```

Alternatively, if you have one or more local files in your repository you wish to explicitly block from being publicly accessed you may manually configure a 404 for a specific resource or filename pattern:

```
# Block access to any file entitled `config.local.yaml`.
location ~* config.local.yaml {
    deny all;
    return 404;
}
```
