# Variables and Secrets

For information you don't want to store in your codebase, Altis provides the variables and secrets functionality. This allows you to store values in secure, encrypted storage.

Variables and secrets are managed per environment in the Altis Dashboard. Head to the Settings > Variables & Secrets page to view and manage them for your application and build.


## How variables and secrets work

When you enter a variable or secret into the Altis Dashboard and save it, Altis stores these in a service called [Parameter Store](https://aws.amazon.com/systems-manager/features/). Each item has a name, a value, and whether it's secret or not.

Variables are stored and retrieved in plain-text, allowing you to view their value within the Altis Dashboard. These are handy for runtime feature flags or other behaviour you might want to change quickly, without requiring a full rebuild of your codebase.

Secrets are stored as encrypted data, backed by the same hardware security module (HSM) used to encrypt your database password and other data-at-rest. The Altis Dashboard only has the ability to write these values, and cannot read them back out, so the Dashboard will instead display `••••••`.

Variables and secrets are pulled at run-time directly into memory, and are never stored on the filesystem. For application secrets, your application server (running PHP) pulls the current value down when the container starts up; changes apply on your next deploy. For build secrets, the build process pulls the current values down at the start of the build.

Internally, variables and secrets are stored in a versioned system. If you ever accidentally update a variable or secret, Altis Support can restore the prior value - but cannot access encrypted secret values directly.


## Application variables and secrets

Application variables and secrets are available to your application - that is, your PHP codebase. Altis transparently loads and decrypts values before your application loads, so both variables and secrets will be returned as plain-text strings.

You might want to use these for API keys used for external services, or runtime configuration like feature flags.

Altis provides a convenient API to get these values, and you should use this API any time you need to use the value:

```php
/**
 * Get the value of a variable or secret.
 *
 * This includes both secrets and non-secret variables.
 *
 * @param string $name Name of the variable or secret. Must match the name in the Altis Dashboard exactly.
 * @param mixed $default Default value to return if not set (such as in local environments).
 * @return string|null Value as a string if set, null otherwise.
 */
Altis\get_variable( string $name ) : ?string;
```

**Note:** The current implementation uses environment variables, but this is not considered a stable API, and may change. Use the provided PHP API to ensure future compatibility.

Changes to any values apply when the application container is next launched - this is typically **at your next deploy**, so we recommend redeploying right after you change any values.


## Build variables and secrets

Build variables and secrets are available to your [build script](./build-scripts/README.md). Altis transparently loads and decrypts values before running your build script, so both variables and secrets will be available as plain-text strings.

You might want to use these for authentication tokens for [private dependencies](./build-scripts/private-dependencies.md) or debugging flags for your build.

Both variables and secrets are available to your build script as environment variables:

* Variables are available with the `VAR_` prefix - a build variable named `MYNAME` will be available as `$VAR_MYNAME` in your script.
* Secrets are available with the `SECRET_` prefix - a build secret named `MYNAME` will be available as `$SECRET_MYNAME` in your script.

Changes to values apply immediately, and will affect your next build.


## Limitations

The following limtiations apply:

* Names must consist of uppercase letters, numbers, or underscore characters only, and must begin with a letter.
* Names must be less than 100 characters long.
* Values must be less than 4096 bytes long.
* A limit of 100 variables and secrets per type (application or build) applies.
