# Accessing Logs

You have access to several types of logging from sites on Altis have following logs available:

- PHP Error log (two week retention)
- Nginx Error log (two week retention)
- WordPress Cron task logs (via Cavalcade, indefinite retention)
- Email logs (one month retention)
- Access logs (via a custom REST API endpoint, indefinite retention)

The logs can be viewed via [Altis Dashboard](./README.md) under a site's Logs tab.

![Screenshot of PHP log details](../assets/logs.png)

The date range can be specified for all logs types. Some log types will also support additional filtering such as cron logs.

![Screenshot of a logs filter being applied](../assets/logs-with-filter.png)

## Common PHP-FPM warnings

You may occasionally see PHP-FPM warnings similar to the following in the PHP error logs:

```text
server reached pm.max_children setting, consider raising it
```

This warning is expected on Altis Cloud and does not indicate that your application needs a PHP-FPM configuration change.
Altis Cloud manages request capacity by automatically scaling web containers horizontally, rather than by increasing the number of
PHP-FPM workers within a single container. In most cases, this log entry can be ignored unless it correlates with user-visible
performance issues or other application errors.

## Log Delivery

Log delivery to CloudWatch can be switched off, however this is not recommended as logs will not be available in the Altis
Dashboard.

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "php-errors-to-cloudwatch": false
                }
            }
        }
    }
}
```
