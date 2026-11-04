# Builds

Each time you push to an environment's tracked branch, Altis builds your commit using your [build script](../build-scripts/README.md).
You can see recent builds and deploys on an environment's Release page in [Altis Dashboard](./README.md).

## Cancelling a build

If a build was started by mistake, or you have pushed a fix and don't want to wait for an older build to finish, you can cancel a
build that is still in progress.

1. In [Altis Dashboard](./README.md), open the environment's Release page. Click the environment in the sidebar and choose
   "Release", or click "Release" on the environment's card on the Overview page.
2. Find the build that is in progress. Its row opens automatically and shows the build steps.
3. Click "Cancel build".

![Screenshot of an in-progress build with the Cancel build button](../assets/cancel-build-button.png)

While Altis stops the build, the button reads "Cancelling…".

![Screenshot of a build being cancelled](../assets/cancel-build-cancelling.png)

Once the build has stopped, it is marked as "Cancelled". Cancelled builds stay in the build history so you can see what happened,
but they can't be deployed. To build the commit again, push a new commit to the branch.

![Screenshot of a cancelled build in the build history](../assets/cancel-build-cancelled.png)

**Note:** The build is cancelled as soon as you click the button. There is no confirmation step.

Only builds that are still in progress can be cancelled. Once a build has finished, it can't be cancelled, and cancelling a build
does not affect the code that is currently deployed.

Cancelling builds needs the same access as performing deployments: the Developer or Admin role. See
[Teams](./teams.md#developer) for more information.
