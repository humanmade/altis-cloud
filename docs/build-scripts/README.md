# Build Scripts

As part of the deployment process, Altis supports running the running of tasks to handle pre-deployment tasks such as minifying JavaScript, running webpack, compiling SASS, etc. This allows you to commit only source files into your repository, and avoid merge conflicts.

In a nutshell, Altis runs  a file called `.build-script` in the root of your project's git repository, which should install Composer dependencies and build any assets you need.

Altis will create a [default build script](https://github.com/humanmade/altis-cms/blob/master/.build-script) for you when you first install it, but you can add your own custom steps as you wish.


## How builds work

Each Altis environment is set up to track a particular GitHub repository and branch. When you push code to a tracked branch, Altis builds your commit following a process that you specify.

This build process transforms your source repository into a container, which can be deployed at a time of your choosing (or automatically deployed if you choose).

Builds are run using a "build container"; this is a container which provides a stable environment for any custom scripts you may use. You can install software tools (such as different Node/npm versions) within this container, but note that the container is reset at the end of each run. 

Some files are retained in a persistent cache between runs, but note that this may be cleared at any time and in some cases a dirty cache can cause issues with deployments. In particular changing Node/NPM versions in your build script may not be reflected until the build cache is cleared. Contact Support to have the build cache cleared.

The build process follows these steps:

1. "Setting up build" - In this step, we start the build container.
2. "Preparing build environment" - In this step, we restore any build cache, and set up systems for the build.
3. "Building application" - In this step, we run your custom build script. This is where Composer installs and other tasks happen.
4. "Cleaning up" - In this step, we save your files into a container ready for deployment, save your build cache, and reset any state necessary.

Once the build process is complete, we have a container with your built files ready for deployment to the infrastructure.


## Your build script

Your build script tells Altis any transformations you want to make to turn your source repository into the site that you deploy. Typically, this contains Composer installation commands, and any custom build tools like Webpack.

This build script is committed into your repo at `.build-script`, and is executed by a Bash shell. Altis will create a [default build script](https://github.com/humanmade/altis-cms/blob/master/.build-script) for you when you first install it, but you can add your own custom steps as you wish.

**Note:** At a minimum, your build script must install Composer dependencies in order for Altis to function. If you forget to install these, your environment may end up in a non-deployable state.

Your build script can run any commands you'd like, including installing or configuring additional build tools.


## Included build tools

The following build tools are pre-installed inside the build container for your script:

- PHP 7.4
- Composer 1.10
- Node 12.18
- npm 6.14
- Git 2.25
- [asdf][] package manager (with Node plugin)

[asdf]: https://github.com/asdf-vm/asdf

Note that [nvm](https://github.com/nvm-sh/nvm) is not installed by default because asdf provides the same functionality.

```sh
#!/bin/bash -e

# Install composer dependencies
composer install --no-dev --optimize-autoloader --apcu-autoloader

# Install Node Version Manager (nvm)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.2/install.sh | bash
. ~/.nvm/nvm.sh

# Use node version from an '.nvm' file
nvm install
```

## Example build scripts

You can use your build script to run anything you'd like. Here's some typical build scripts we see.

### Building theme assets

You may want to build CSS/JS assets for a theme from SCSS/TypeScript/JSX source files. In order to do so, your script might look like this:

```sh
#!/bin/bash -e

# Install composer dependencies
composer install --no-dev --optimize-autoloader --apcu-autoloader

# Build my-theme
cd content/themes/my-theme/
npm ci
npm run build
```

### Building multiple themes

If you're working with a larger network, you might want to build multiple assets at once.

You can apply the same technique as above, but remember that when you `cd`, any further steps will be in your new directory; if you need to `cd` again, it will be relative to the first directory, *not* the project root.

To simplify things, set a variable for the root directory, and `cd` based on this each time instead.

```sh
#!/bin/bash -e

# Install composer dependencies
composer install --no-dev --optimize-autoloader --apcu-autoloader

# Store the project root for easy cd-ing.
PROJECT_ROOT="$(pwd)"

# Build my-first-theme
cd "$PROJECT_ROOT/content/themes/my-first-theme/"
npm ci
npm run build

# Build my-second-theme
cd "$PROJECT_ROOT/content/themes/my-second-theme/"
npm ci
npm run build
```


## Advanced usage

### Alternative languages for build scripts

The build script can be written in languages other than Shell script by adding a shebang line at the top of the file.

For example to write the build script in PHP you would do the following:

```php
#!/usr/bin/env php
<?php
echo 'Starting build...';
```

And for NodeJS it would be:

```js
#!/usr/bin/env node
console.log( 'Starting build...' )
```


### Private dependencies

If you are using Composer dependencies from private repositories, [additional configuration may be required](./private-dependencies.md).


## Limits

The maximum size of your built assets, including your codebase, is 640MB. This size limit is in place to ensure that containers can be run on your application servers without exhausting the maximum disk size of the underlying machines.

If you exceed this limit, your builds may fail. Consider cleaning up unnecessary files as part of your build process (such as `node_modules` directories). If your project requires a larger size limit and cannot be reduced, the maximum size may be increased. Please contact your account manager, as this may come with additional cost.

The total elapsed time for a build cannot exceed 30 minutes. This limit cannot be changed.
