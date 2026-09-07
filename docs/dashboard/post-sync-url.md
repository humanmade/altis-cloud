# Post-Sync URL

After a database sync, Altis runs a cache flush and any configured
[post-sync actions](docs://core/cli-command/#wp-altis-post-sync) against the
destination environment. By default these run against the environment's
internal domain root, which is the correct site for single site installs and for
multisite installs whose main site is at the domain root.

On subdirectory multisite installs the domain root is not always a registered
site. Where the main site lives under a path such as `/blog/`, WP-CLI cannot
bootstrap WordPress at the root and these steps fail. Configure an explicit URL
for those environments.

The URL is fetched from `composer.json` at the currently deployed SHA, alongside
[search and replace mappings](./search-replace.md).

## Configuration

Configure the URL under `extra.altis.cloud.post-sync` in `composer.json`.

Each top-level key should be the **exact** ID of the environment you are syncing
**into**. Under each environment, set `url` to the full URL, including scheme, of
a site that exists in that environment's database once the sync has completed.

```json
{
    "extra": {
        "altis": {
            "cloud": {
                "search-replace": {
                    "example-stag-01": {
                        "example.com": "example-stag.altis.cloud"
                    }
                },
                "post-sync": {
                    "example-stag-01": {
                        "url": "https://example-stag.altis.cloud/blog/"
                    }
                }
            }
        }
    }
}
```

In this example, a sync into `example-stag-01` runs its cache flush and
post-sync actions against `https://example-stag.altis.cloud/blog/` rather than
the domain root.

The URL must match the destination environment's data as it exists **after** the
sync, so it should reflect any
[search and replace mappings](./search-replace.md) applied during the import.

## Default behaviour

If no URL is configured for the destination environment, or `composer.json`
cannot be read, these steps run against the environment's internal domain root.
Environments that do not need an override require no configuration.
