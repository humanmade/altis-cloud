# Getting Started

Getting started with Node.js on Altis Cloud is easy, no matter whether you're migrating an existing Next.js project or
starting from scratch.

## Before You Start

Before you get started, make sure you're aware of the requirements for Node.js on Altis Cloud.

**Your subscription**: Node.js is an add-on to your existing Altis environments, and is not included as a standard part
of your subscription. Contact your account manager for more information about adding Node.js to your subscription.

**Next.js only**: Node.js on Altis Cloud only officially supports Next.js projects. See
our [framework support page](./frameworks.md) for more details.

**Attached to your application**: Node.js containers are attached to your existing Altis Cloud environment, so you'll
need to [set up your WordPress-based application first](docs://getting-started/).

## Starting Your Project

### Starting a New Project

We recommend starting with the [Altis Node.js Skeleton project](https://github.com/humanmade/altis-nodejs-skeleton).
This project includes a sample Next.js project and build script, ready to be deployed to your environment.

To get started, clone this repository to a new repository you own.

### Adapting Your Existing Project

If you already have a Next.js-based project, you can easily adapt it to run on Altis Cloud.

You'll need to make sure you meet the following requirements:

- Altis runs `npm run start` to start your application; ensure your `package.json` runs the standard `next start`
  command for `scripts.start`
- Ensure Next.js runs on the default port 3000 when started
- The version of Node.js must be specified in your `package.json`'s `engines.node` configuration; either as a bare
  number (`21`) or as a major and minor version (`21.1`). Complex version constraints are not supported.

You'll also need to add a build script to your repository as documented below.

## Set up Your Build Script

You control how your project is built through the `.build-script` in your repository. This works just
like [the build script in your regular repository](../build-scripts/), but specifically for your Node.js project.

When you push new commits to your repository, Altis runs your build script inside a container, then packages up the
result, ready for deployment.

(Unlike your regular Altis project, your builds are always automatically deployed, even for production environments.)

Your build script should install your Node.js dependencies and run the Next.js build process. The build script we
include with the Node.js skeleton includes these:

```sh
npm install --omit=dev
npm run build
```

## Set the Node.js Version

Likewise, you also control the version of Node.js in use on your environment. Altis uses the `engines.node` field in
your `package.json` to decide which version of Node.js to use. You can set this to
any [supported version](./specifications.md).

**Note**: Your `engines.node` constraint must be set to either a major version (`21`) or a minor version (`21.1`). Altis
does not currently support complex version constraints.

The version of Node.js is bundled with your code when the build runs, ensuring that builds remain stable and verifiable.

## Connect Your Repository

With your repository prepared, the Altis team will be able to set up your Node.js environment for you.

Just like your regular application, each separate Node.js environment will be mapped to a branch within your repository,
so make sure you have both your GitHub repository details and desired branch handy. You'll also need to provide access
to the @humanmade-cloud user.

To request environment setup, [contact Altis support](support://new).

## Deploy Your Application

When Altis sets up your Node.js environment, they'll perform an initial deployment for you.

To deploy any changes to your application. simply push new commits to your connected branch, and Altis will
automatically build and deploy it to your environment. Unlike with your backend application, Altis currently does not
support manual builds and deployments on Node.js environments.
