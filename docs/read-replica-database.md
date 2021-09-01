# Read-Replica Database

Altis Cloud uses multiple instances of the database to improve performance and resilience. The read replica set up uses a read only copy of the database for all `SELECT` queries and another copy to handle updates and inserts.

Support for this infrastructure is provided through the [Ludicrous DB plugin](https://github.com/stuttter/ludicrousdb) and is enabled by default.

The feature can be switched off but it is not recommended to do so:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "ludicrousdb": false
                }
            }
        }
    }
}
```
