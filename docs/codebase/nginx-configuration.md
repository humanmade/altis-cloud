# Nginx Configuration

HM Cloud uses Nginx for the web server which is responsible for serving static files, and proxying requests to PHP for dynamic content. For advanced customization we support additional Nginx configuration via the project codebase.

Nginx will look for an additional file in your project repository at `.config/nginx-additions.conf` and will load it into the `server {}` context of the main configuration.

Developers can use this advanced configuration to do complex redirect, rewrites or other server-level routing.

For example, if you want to redirect a specific domain to a new domain and for some reason not able to do this at the PHP / application layer:

`.config/nginx-additions.conf`

```
if ( $host = "example.com" ) {
    rewrite ^ https://sub2.example.com$request_uri? permanent;
}
```
