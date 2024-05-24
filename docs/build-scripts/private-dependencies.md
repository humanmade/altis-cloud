# Private Dependencies

Altis automatically configures the build server to access your primary repository (the one you provided when your first environment was created).

If you need to add additional private dependencies to your environment, further configuration will be required.

This configuration is not necessary for public repositories. If you experience permission errors with public repositories, ensure you are using a `https://host/repo.git` URL for your repository rather than the `git@host:repo` format.

GitHub is the only officially supported repository host, however it is possible to successfully use dependencies from other hosts.

**Note:** Altis Support is unable to assist with configuring access to private dependencies on third-party hosts.


## Private Composer repositories

Composer supports [authentication via HTTP Basic Auth or bearer tokens](https://getcomposer.org/doc/articles/authentication-for-private-packages.md) which can be used for your private dependencies.

In some cases, such as license keys or other unimportant tokens, you can commit these directly to your `composer.json` or `auth.json` file, per Composer's documentation.

For authentication which must be kept secret, you can use [build secrets](../variables-secrets.md) to store the authentication keys or tokens. These will be available in your build script as environment variables, which you can then use to configure Composer.

You can either set multiple secrets (one per domain) and configure them in your build script, or set a single auth variable. We recommend setting multiple variables unless you have strict security requirements.


### Multiple secrets

With multiple secrets, you set one secret for each auth token, and tell Composer to use the appropriate one for each domain you use. This requires setting the configuration for each domain in your build script, using the `composer config` command.

This is the easiest method, however does mean your secrets are temporarily stored on the filesystem, so is strictly less secure than the single secret method.

For example, if you have a private repository at `composer.example.com` which takes a bearer token of `abcd1234`:

1. Add your private authentication token as a [build secret](../variables-secrets.md)

2. In your build script, set the Composer authentication key for this domain. For example, if you set your build secret with the name `EXAMPLE_AUTH`:

   ```
   composer config bearer.composer.example.com "$EXAMPLE_AUTH"
   ```

   This must be set *before* your `composer install` command.

3. (Optional) Clean up the `auth.json` file after installation (`rm auth.json`). This must be run *after* your `composer install` command.

**Note:** `composer config` writes configuration items to the `auth.json` file in your working directory. This directory is persisted across builds as part of the build cache, so for security, you may wish to delete this file.


### Single secret

Composer provides a single `$COMPOSER_AUTH` variable to configure authentication for all external repositories, [using the same format as auth.json](https://getcomposer.org/doc/03-cli.md#composer-auth).

This is more secure, as the value is never written to the filesystem, but may be hard to manage as the complex secret value cannot be retrieved once set.

For example, if you have a private repository at `composer.example.com` which takes a bearer token of `abcd1234`, your `COMPOSER_AUTH` variable would look like:

```json
{
   "bearer": {
      "composer.example.com": "abcd1234"
   }
}
```

To use this:

1. Add this whole JSON configuration as a single [build secret](../variables-secrets.md)

2. In your build script, tell Composer to use this secret by setting `COMPOSER_AUTH`. For example, if you set your build secret with the name `EXAMPLE_AUTH`:

   ```
   export COMPOSER_AUTH="$EXAMPLE_AUTH"
   ```

   This must be set before your `composer install` command.

## GitHub

If you have dependencies in private GitHub repositories, you can use the pre-configured Altis accounts to provide access to these.

To use these repositories, provide the [@humanmade-cloud](https://github.com/humanmade-cloud) user with read access to the repository using GitHub's access management tools.


## BitBucket

If you have dependencies in private Bitbucket repositories, you will need to configure access manually.

We recommend following [Composer's configuration guide for Bitbucket repositories](https://getcomposer.org/doc/05-repositories.md#bitbucket-driver-configuration). In particular, note the following:

* Composer requires the use of `https` repository URLs for Bitbucket authentication.
* Bitbucket authentication must be set up [following the instructions for Composer](https://getcomposer.org/doc/articles/authentication-for-private-packages.md#bitbucket-oauth)
	* Note that when applying this configuration, this must be saved into the `auth.json` file within your repository; i.e. do not use the `--global` option.

If you must use `git@bitbucket.org`-style URLs (SSH URLs), you will need to follow the guide below for other Git hosts.


## Other Git hosts

For other Git hosts, you will typically need to set up SSH access keys to access these servers. This will require creation of a private key for this purpose and configuring the external service.

**Note:** You may not use the pre-configured Altis authentication keys for these purposes. Any attempt to access these keys is a violation of the terms of service, and will lead to account termination.

As Altis already provisions keys onto the build server, you will need to add a secondary set of SSH keys, and configure SSH (used by Git and Composer) to use them.

1. Generate a new SSH keypair locally. We recommend [these instructions from GitHub](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent#generating-a-new-ssh-key). Please note that this keypair **must not** have a passphrase set. This will create a new file called `id_rsa` or similar; this is your private key.

2. Add this private key as a [build secret](../variables-secrets.md)

   **Note:** If your key is longer than 4096 characters, you will not be able to store it in a build secret. Instead, you can store a shorter secret and use this to encrypt your key, then commit this file to your repository - `.config` is a convenient place to put this file.

3. Configure the Git host to provide access to your private dependencies using this key; these instructions depend on your Git host.

4. In your build script, write the private key file and set the permissions to avoid any warnings from SSH.

   For example, if your key file is stored as a secret called `BITBUCKET_KEY`:

   ```
   echo "$SECRET_BITBUCKET_KEY" > ~/.ssh/bitbucket_key
   chmod 0600 ~/.ssh/bitbucket_key
   ```

   These steps must be before your `composer install` or any Git step.

5. Register the key with SSH.

   ```
   ssh-add ~/.ssh/bitbucket_key
   ```

   This step must be before your `composer install` step, and after the key is copied into place.

   SSH will now be able to use your key to connect to your Git host.

6. (Optional) Delete the key from the filesystem to clean it up.

   ```
   rm ~/.ssh/bitbucket_key
   ```

For example, your build script may look like:

```sh
#!/bin/bash
echo "$SECRET_BITBUCKET_KEY" > ~/.ssh/bitbucket_key
chmod 0600 ~/.ssh/bitbucket_key
ssh-add ~/.ssh/bitbucket_key
composer install
```

Composer should now be able to install your private dependencies from the other Git hosts.

**Note:** Altis Support is unable to assist with configuring access to private dependencies on third-party hosts.
