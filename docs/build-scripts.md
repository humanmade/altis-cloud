# Build Scripts

As part of the deployment process, HM Cloud supports running the running of tasks to handle pre-deployment tasks such as minifying JavaScript, running webpack, compiling SASS, etc. The specific build process is defined in a file `.build-script` in the root of your project's git repository.


## Build Script Environment Specification

The build script is run in an integration environment, not in the context of the application servers. The following packages are available to your script:

- PHP 7.2
- NodeJS 8.11
- NPM 5.6
- Git 2.7
