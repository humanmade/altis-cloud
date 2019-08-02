# Build Scripts

As part of the deployment process, Altis supports running the running of tasks to handle pre-deployment tasks such as minifying JavaScript, running webpack, compiling SASS, etc. The specific build process is defined in a file `.build-script` in the root of your project's git repository.

At a minimum the build script should install the composer dependencies:

```
composer install
```

## Build script language

The build script can be written in languages other than shell script by adding a shebang line at the top of the file.

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


## Build Script Environment Specification

The build script is run in an integration environment, not in the context of the application servers. The following packages are available to your script:

- PHP 7.2
- Composer
- NodeJS 8.11
- NPM 5.6
- Git 2.7
