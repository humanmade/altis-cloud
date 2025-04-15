# Audit Log Storage

The [Audit Log feature](docs://security/audit-log.md) provided by the Security Module uses a special append-only storage solution on
the cloud environments rather than the database to prevent any possibility of tampering.

By default, the Audit log entries in this append-only cloud storage solution are kept indefinitely and are not subject to the same
retention policies as other logs. This is to ensure that the Audit Log is always available for compliance and auditing purposes.

If you do not need this extra level of safety and assurance that your audit log is accurate you can switch to using standard
database storage for the logs:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "audit-log-to-cloudwatch": false
                }
            }
        }
    }
}
```
