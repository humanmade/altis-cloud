# Private Dependencies

Altis automatically configures the build server to access your primary repository (the one you provided when your first environment was created).

If you need to add additional private dependencies to your environment, further configuration will be required.

This configuration is not necessary for public repositories. If you experience permission errors with public repositories, ensure you are using a `https://host/repo.git` URL for your repository rather than the `git@host:repo` format.

GitHub is the only officially supported repository host, however it is possible to successfully use dependencies from other hosts.

**Note:** Altis Support is unable to assist with configuring access to private dependencies on third-party hosts.


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

2. Commit this private key to your primary repository; `.config` is a convenient place to put this file.

3. Configure the Git host to provide access to your private dependencies using this key; these instructions depend on your Git host.

4. In your build script, copy the private key file and set the permissions to avoid any warnings from SSH. (Copying the file ensures you avoid potential conflicts from the repository.)

   For example, if your key file is at `.config/bitbucket_key`:

   ```
   cp .config/bitbucket_key ~/.ssh/bitbucket_key
   chmod 0600 ~/.ssh/bitbucket_key
   ```

   These steps must be before your `composer install` step.

5. Register the key with SSH.

   ```
   ssh-add ~/.ssh/bitbucket_key
   ```

   This step must be before your `composer install` step, and after the key is copied into place.

   SSH will now be able to use your key to connect to your Git host.

For example, your build script may look like:

```sh
#!/bin/bash
cp .config/bitbucket_key ~/.ssh/bitbucket_key
chmod 0600 ~/.ssh/bitbucket_key
ssh-add ~/.ssh/bitbucket_key
composer install
```

Composer should now be able to install your private dependencies from the other Git hosts.

**Note:** Altis Support is unable to assist with configuring access to private dependencies on third-party hosts.
