# Health checks

Altis uses health checks to determine whether servers are healthy or need replacing, and whether minimum requirements such as PHP
being available are met before applications are deployed.

**Note:** Any changes to the health check configuration are considered to void your warranty, except as directed by the Altis team.
Adjusting any configuration may cause catastrophic errors in your environments.

## API

There are 2 health check endpoints. If any health check fails the response code will be `500`, otherwise it will be `200`.

**`/__instance_healthcheck`**

Used during deployments to ensure containers meet the minimum requirements for running an application. By default this checks that
PHP is available.

**`/__healthcheck`**

Used for application level health checks. By default these are:

- PHP is running
- Database is available
- Object cache is available
- Elasticsearch is available
- Sites are indexed in Elasticsearch
- Cavalcade is available
- Cron jobs are running

### Response Format

By default each health check URL will show some HTML output detailing the checks and their status. To get the data in JSON format
use one of the following options:

- Send an `Accept` header in the request with the value `application/json`
- Append the query string `?_accept=json`

### CLI Command

A CLI command is also available for the application health check:

```shell
wp healthcheck run [--format=json]
```

## Extending Health checks

Custom health checks can be added to the default list using filters. The health checks are a keyed array of checks with the value
being the result. Any non `true` value counts as a failed health check. Typically an error message should be provided as the
alternative value to `true`, however `false` will also work.

**`altis_instance_healthchecks : array`**

Filters the instance health checks. These run early, before WordPress has loaded, so only core PHP functions and auto-loaded code
installed via Composer is available.

**`altis_healthchecks : array`**

Filters the application level health checks. These are run after WordPress and all plugins have loaded. For example:

```php
add_filter( 'altis_healthchecks', function ( $checks ) {
    global $wpdb;
    $checks['custom-db-table-exists'] = in_array( $wpdb->base_prefix . 'custom', $wpdb->tables, true );
    return $checks;
} );
```

## Configuration

Health check behaviour is generally not user configurable, and is handled automatically for you by the Cloud module.

To disable the health checks, set `modules.cloud.healthcheck` to false:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "healthcheck": false
                }
            }
        }
    }
}
```
