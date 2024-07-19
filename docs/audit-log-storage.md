# Audit Log Storage

The [Audit Log feature](docs://security/audit-log.md) provided by the Security Module uses a special append-only storage solution on
the cloud environments rather than the database to prevent any possibility of tampering.

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
