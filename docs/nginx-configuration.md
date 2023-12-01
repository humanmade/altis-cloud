# Nginx Configuration

Altis Cloud uses Nginx within your [web containers](./architecture.md). For advanced customization we support additional Nginx configuration via the project codebase.

**Note:** Custom configuration will only apply to traffic served by the web container, which does not include media or tachyon (see the [architecture diagram for more information](./architecture.md)). Specifically, any URLs beginning with `/uploads/` or `/tachyon/` are routed directly to [S3](./s3-storage.md) and [Tachyon](docs://media/dynamic-images.md) directly.

**Important:** Nginx configuration is a powerful low-level tool, and incorrect configuration may prohibit access to your site. Ensure that any configuration changes are carefully tested on local and pre-production environments. Altis is not responsible for downtime resulting from misconfiguration of nginx.


## Server Configuration

Custom configuration can be supplied in Nginx either within the `server {}` context, or the `http {}` context.

Nginx loads the following custom files from your project repository:

* `.config/nginx-http-additions.conf`: Loaded into the `http` context
* `.config/nginx-additions.conf`: Loaded into the `server` context

An optional suffix can be specified before the extension to split your configuration in complex cases.

Internally, the Altis nginx configuration looks like:

```
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

### Redirection

If you want to redirect a specific domain to a new domain and for some reason not able to do this at the PHP / application layer:

`.config/nginx-additions.conf`

```
if ( $host = "example.com" ) {
    rewrite ^ https://sub2.example.com$request_uri? permanent;
}
```


### Block access to files

If you have one or more local files in your repository you wish to explicitly block from being publicly accessed you may manually configure a 404 for a specific resource or filename pattern:

```
# Block access to any file entitled `config.local.yaml`.
location ~* config.local.yaml {
    deny all;
    return 404;
}
```


### Limit access based on IP address

Access to certain URLs can be limited based on IP address, using the `allow` and `deny` directives.

Any URLs limited through this manner **must not** be set as cacheable, otherwise the response will be cached at the CDN layer. If this behaviour is desired, limitations must be made at the firewall layer instead; contact support for further details.

Additionally, be careful to ensure internal systems and loopback (localhost) requests are permitted to access these URLs, as this may cause problems with functionality or may cause your site to be marked as unhealthy.

```
# Block access to /internal/ to known subnets
location /internal/ {
    # Allow known subnets.
    allow 152.37.71.106;
    allow 8.8.8.8/16;

    # Allow internal Altis systems.
    allow 172.16.0.0/12;

    # Deny access to all others.
    deny all;

    # Mark as uncacheable.
    add_header Cache-Control 'no-store, no-cache';

    # Route as per usual.
    try_files $uri $uri/ /index.php?$args;
}
```
