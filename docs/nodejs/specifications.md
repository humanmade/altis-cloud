---
order: 100
---
# Specifications & Limitations

## Availability

Node.js is an add-on to your existing Altis platform subscription and may come with additional charges. Contact your account manager for more information about adding Node.js to your subscription.


## Runtime Environment

Your application runs inside a Docker container, built as part of the build process. This container is based on the [official `node:<version>-slim` container](https://hub.docker.com/_/node) published by Docker.

The following versions are available, and the specific version in use will depend on your configuration.

* Node.js version: 18, 20, 21

**Note:** The operating system environment is not part of the stable API, and is subject to change. We strongly recommend against using Node add-ons (native dependencies) built with node-gyp.


### Environment Variables

The following variables are available in the runtime environment:

* `ALTIS_ENVIRONMENT_NAME` - The full ID for the environment (e.g. `yourproject-dev-01`)
* `ALTIS_ENVIRONMENT_REGION` - The AWS region code for the environment
* `ALTIS_ENVIRONMENT_TYPE` - The environment type; typically one of `production`, `staging`, `development`. (Add-on environments may use custom types.)


## Build Environment

Your application builds within a Docker container, which is specifically designed for the build process and includes build tools such as Composer and npm.

### Environment Variables

The following variables are available in the build environment:

* `STACK_NAME` - The full ID for the environment being built (e.g. `yourproject-dev-01`)
* `GIT_BRANCH` - The branch of your repository being built

(Note that by design, these environment variables differ from the runtime variables.)


## Limitations

### Altis-wide limitations

* **Immutable filesystem**: Just like our other infrastructure, once your project has been built the filesystem is immutable. Your project will not be able to write or modify files while it is running. This guarantees software lifecycle controls are followed as well as ensuring security of the infrastructure.


### Build process

The following limitations apply to your build process:

* Unlike your WordPress-based application, Node.js on Altis Cloud currently only supports automatic build and deployment.


### Runtime environment

The following limitations apply to your runtime environment:

* Altis runs your project inside a Docker container, with the entrypoint set to `npm run start`. It is not possible to reconfigure this command, although you can specify this via `scripts.start` in your `package.json`.
* Altis expects your project to expose a HTTP server on port 3000. This port is not configurable, nor is the protocol.
* Node.js containers don't have direct access to other systems in your Altis Cloud environment, including your MySQL database, Elasticsearch, and caching servers (Redis). You can access data from WordPress or external APIs using the standard Node.js HTTP APIs.

The following features are not currently available for Node.js environments:

* CLI access
* Metrics
* X-Ray
