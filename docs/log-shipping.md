# Log Shipping

Log Shipping sends a copy of your access logs to an S3 bucket you own on a regular basis.

**Optional Add-On**: Log Shipping is available as an optional add-on to your Altis Cloud
subscription, available on any plan. Contact your account manager for more information
about pricing.

## How it works

Log Shipping automatically copies your CloudFront access logs to your own S3 bucket using
S3 Event Notifications. When new log files are created, they are automatically copied to
your destination bucket under the path
`s3://<your-bucket>/<environment-name>/cloudfront/...`.

The logs are delivered in CloudFront's standard format and include all HTTP requests made
to your application, including request details, response codes, and timing information.

## Use Cases

Log Shipping is commonly used for:

- **Compliance**: Meet regulatory requirements for log retention and audit trails
- **Security analysis**: Monitor access patterns and detect potential security threats
- **Performance monitoring**: Analyze traffic patterns and optimize application performance
- **Business intelligence**: Track user behavior and site usage for business insights
- **Data archival**: Maintain long-term historical records of site activity

## Lifecycle Management

You are responsible for managing the lifecycle of the log files in your S3 bucket,
including storage costs, retention policies, and access patterns. Consider setting up S3
lifecycle rules to automatically transition older logs to cheaper storage classes or
delete them after your required retention period.

## Configuration

Log Shipping requires configuration of your S3 bucket. The feature is not enabled by
default and requires setup by the Altis team.

When requesting Log Shipping setup, provide your destination S3 bucket name.

You will need to grant Altis write access to your bucket. We will provide the required
AWS details.

Contact support or your account manager to enable Log Shipping for your environment.
