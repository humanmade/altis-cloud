# Log Shipping

Log Shipping sends a copy of your access logs to an S3 bucket you own on a regular basis.

**Optional Add-On**: Log Shipping is available as an optional add-on to your Altis Cloud subscription, available on any plan. Contact your account manager for more information about pricing.

## How it works

Log Shipping automatically copies your CloudFront access logs to your own S3 bucket using S3 Event Notifications. When new log files are created, they are automatically copied to your destination bucket under the path `s3://<your-bucket>/<environment-name>/cloudfront/...`.

The logs are delivered in CloudFront's standard format and include all HTTP requests made to your application, including request details, response codes, and timing information.

## Use Cases

Log Shipping is commonly used for:

- **Compliance**: Meet regulatory requirements for log retention and audit trails
- **Security analysis**: Monitor access patterns and detect potential security threats
- **Performance monitoring**: Analyze traffic patterns and optimize application performance
- **Business intelligence**: Track user behavior and site usage for business insights
- **Data archival**: Maintain long-term historical records of site activity

## S3 Bucket Permissions

You must create a bucket policy that allows Altis to write log files to your destination bucket. The Altis service uses the following role:

- **Altis Account ID**: `577418818413`
- **Role Name**: `<environment-name>-log-shipping-s3-events`
- **Principal ARN**: `arn:aws:iam::577418818413:role/<environment-name>-log-shipping-s3-events`

Replace `<DEST_BUCKET>` with your bucket name and `<environment-name>` with your Altis environment name:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowAltisToWriteCloudFrontLogs",
      "Effect": "Allow",
      "Principal": {
        "AWS": "arn:aws:iam::577418818413:role/<environment-name>-log-shipping-s3-events"
      },
      "Action": [
        "s3:PutObject"
      ],
      "Resource": "arn:aws:s3:::<DEST_BUCKET>/<environment-name>/cloudfront/*"
    }
  ]
}
```

## KMS Key Support

If your S3 bucket enforces server-side encryption with AWS KMS, you can provide your own KMS key for enhanced security.

If you want to enforce KMS encryption on all objects in your bucket:

```json
{
  "Sid": "RequireKmsOnWrites",
  "Effect": "Deny",
  "Principal": "*",
  "Action": "s3:PutObject",
  "Resource": "arn:aws:s3:::<DEST_BUCKET>/*",
  "Condition": {
    "StringNotEquals": {
      "s3:x-amz-server-side-encryption": "aws:kms"
    }
  }
}
```

Your KMS key must allow the Altis role to encrypt data. Add this statement to your KMS key policy:

```json
{
  "Sid": "AllowAltisRoleUseOfKey",
  "Effect": "Allow",
  "Principal": {
    "AWS": "arn:aws:iam::577418818413:role/<environment-name>-log-shipping-s3-events"
  },
  "Action": [
    "kms:Encrypt",
    "kms:GenerateDataKey*"
  ],
  "Resource": "*"
}
```

## Lifecycle Management

You are responsible for managing the lifecycle of the log files in your S3 bucket, including storage costs, retention policies, and access patterns. Consider setting up S3 lifecycle rules to automatically transition older logs to cheaper storage classes or delete them after your required retention period.

## Configuration

Log Shipping requires configuration of your S3 bucket. The feature is not enabled by default and requires setup by the Altis team.

When requesting Log Shipping setup, provide your destination S3 bucket name and KMS key ARN (if using customer-managed encryption).

Contact support or your account manager to enable Log Shipping for your environment.
