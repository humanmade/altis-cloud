# Supported Frameworks

Node.js on Altis Cloud only officially supports the [Next.js framework](https://nextjs.org/) at this time.

We're constantly evaluating additional frameworks to add support for - if you have any
suggestions, [let us know via the roadmap](https://dashboard.altis-dxp.com/roadmap). You can additionally run
[other frameworks](#other-frameworks) via a compatible `npm start` command, but please note this is not
officially supported.

## Next.js

Altis natively supports the [Next.js framework](https://nextjs.org/) with out-of-the-box integration.

We recommend starting with the [Altis Node.js Skeleton project](https://github.com/humanmade/altis-nodejs-skeleton),
which includes all the pieces you'll need to get started with Next.js.

### Link Prefetching

We recommend disabling [link prefetching](https://nextjs.org/docs/app/api-reference/components/link#prefetch) for less
frequently used links in Next.js.

```jsx
<Link
	href="/blog"
	prefetch={ false }
>
	Latest News
</Link>
```

Link prefetching causes unnecessary network requests for many users, and each prefetch request will count towards your
billable page views. Consider carefully whether prefetching is necessary for the user experience, and enable it only on
a case-by-case basis rather than as your default.

## Other Frameworks

**Support:** Please note that other frameworks are not supported by Altis at this time. Only use other frameworks if you
are willing to maintain and debug the solution yourself.

Node.js on Altis Cloud is designed to support standard Node.js behaviours, and many tools will work out of the box.
Here's the key facts to be aware of:

- **Self-contained builds**: Your [build pipeline](./builds.md) needs to produce a fully-complete, standalone directory
  with no other dependencies. This build is packaged as a Docker container with minimal system packages and tools.
- **Immutable builds**: Just like our other infrastructure, once created, builds are immutable, and your project will
  not be able to write or modify files while it is running.
- **Node add-ons**: We strongly recommend against using Node add-ons (native dependencies) built with node-gyp, as the
  underlying server architecture is not part of our stable API. We also cannot guarantee that builds are run on the same
  architecture as the app servers.
- **Execution**: Altis runs your project inside Docker containers, with the entry point set to `npm run start`. Your
  server should run on this command and expose an HTTP server on port 3000. No other ports are exposed or available.
