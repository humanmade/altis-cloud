# Search and Replace on Sync

During a database sync, Altis can automatically perform string replacements on
imported data. Mappings are applied in transit before data is stored in the
destination database. This allows environment-specific values, such as domain
names, to be updated for the target environment as part of the sync process.

Mappings are fetched from `composer.json` at the currently deployed SHA.

## Configuration

Configure mappings under `extra.altis.cloud.search-replace` in `composer.json`.

Each top-level key should be the exact ID of the environment you are syncing
**into**. Under each environment, define a set of `"find": "replace"` pairs
to apply during the database sync.

```json
{
    "extra": {
        "altis": {
            "cloud": {
                "search-replace": {
                    "example-stag-01": {
                        "example.com": "example-stag.altis.cloud"
                    },
                    "example-dev-01": {
                        "example.com": "example-dev.altis.cloud",
                        "www.example.com": "example-dev.altis.cloud"
                    }
                }
            }
        }
    }
}
```

In this example, if a database is synced into `example-stag-01`, every occurrence of
`example.com` will be replaced with `example-stag.altis.cloud`.

Likewise, if a database is synced into `example-dev-01`, both `example.com` and
`www.example.com` will be replaced with `example-dev.altis.cloud`.

## Notes

The following points apply to search-replace mappings:

- Mappings use plain string replacement. Regular expressions are not supported.
- You can define multiple find-and-replace pairs for each target environment.
- Use the exact environment ID as the mapping key.
- The mapping key always refers to the target environment, not the source environment.
- Mappings are only applied during database syncs, and are not applied to uploaded assets.
