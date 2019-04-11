# Accessing Logs

You have access to several types of logging from sites on HM Cloud have following logs available:

- PHP Error log
- Nginx Error log
- WordPress Cron task logs (via Cavalcade)
- Email logs
- Access logs (via a custom REST API endpoint)

The logs can be viewed via [Vantage](./vantage.md) under a site's Logs tab.

![](https://joehoyle-captured.s3.amazonaws.com/GTKPP2Yh.png)

The date range can be specified for all logs types. Some log types will also support additional filtering such as cron logs.

![](https://joehoyle-captured.s3.amazonaws.com/sJ84jvK9.png)

## Downloading Access Logs

It is possible to download access logs from the Vantage REST API, this is not currently provided by the Vantage UI due to vast amount of data to display. As such it's typically better to download access logs for a time range, and do further local processing to find the type of requests you need information for.

```
@todo add REST API cURL request.
```

