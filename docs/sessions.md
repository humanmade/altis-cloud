# Sessions

Altis has support for PHP sessions using Redis as the storage backend, however it is not enabled by default. In order to activate PHP sessions support you need to have Redis activated as well, eg:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"cloud": {
					"redis": true,
					"sessions": true
				}
			}
		}
	}
}
```