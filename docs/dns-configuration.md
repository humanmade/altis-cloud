# DNS Configuration

Adding a new domain to Altis is a three step process.

1. **Register domain with CDN** (registration)
2. **Add verification DNS records** (verification)
3. **Add CNAME/ALIAS DNS records** (switch-over)

Note that this is only necessary for adding new top-level domains which have not already been configured. Wildcard subdomain configuration will allow you to add new subsites without requiring DNS changes.

We recommend beginning this process at least a few weeks before your desired switch-over.


## Registration (CDN configuration)

Before routing domains to Altis, your CDN configuration will need to be updated to route the domain correctly, as well as ensuring that SSL certificates are valid for your new domains.

Altis configures the CDN on your behalf, and this step must be completed by the Altis team.

To begin this process, [contact Altis support](support://new).


## Verification

Altis automatically issues SSL certificates and configures the CDN to serve these certificates to users. When adding a new domain, verification will be required to ensure that you control the domain.

The Altis team will provide you with DNS CNAME records to add for SSL verification, provided by AWS. Additionally, if you wish to send email from this domain, additional records will be provided for email verification.

These records can be added before the regular web CNAME records are added, allowing Altis to verify your domain ahead of switch-over. These will not conflict with existing records, and will have no user impact.


## Switch-over

Once Altis has been configured, the final step is to route your custom domain to your Altis environment. This is done by adding a `CNAME` DNS record for your domain or subdomains.

After these DNS changes are put into place, your site will be served by Altis; this is called the "switch-over", as your site will "switch" from any existing host to Altis.

Altis can only provide hostnames to be used in DNS records, not IP addresses, as servers are dynamically allocated based on traffic. Therefore you _must_ use CNAME, ANAME, or ALIAS records when configuring and managing your own DNS.


### CNAME records

The simplest way to route your domain to Altis is to add CNAME records for any subdomains you wish to use. For example, you may wish to use the `www.` subdomain for your main site.

The Altis team will provide you with your project-specific domain to use in CNAME records, or this can be viewed by using the "Add domain" button within the Altis Dashboard.

CNAME records are not supported on the domain apex (or "naked domain"); this is the base domain without `www.`, such as `example.com`. For apex domains, you must use either ALIAS or ANAME records as supported by your DNS provider.


### ALIAS/ANAME records

Commonly, you will want to implement a redirect from the domain apex to the subdomain, such as redirecting `example.com` to `www.example.com`.

There are three options to support this in Altis Cloud:

1. Use ANAME/ALIAS records, as supported by your DNS provider
1. Use your domain registrar to redirect the domain apex to the subdomain
1. Use Altis managed nameservers (see below)

Where possible, we recommend using ANAME/ALIAS records. These operate similarly to CNAME records, but can be used on the domain apex. However, not all registrars support these records, as they are relatively new.

You may also be able to use your registrar's redirection function. Many domain registrars support this service, and typically call it "web forwarding" or "web redirect".


## Managed nameservers

Altis can manage DNS records for you. This will require setting Altis as the authoritative nameserver for your domain, allowing us to manage verification and CNAME/ALIAS records on your behalf.

This has some advantages and disadvantages:

**Advantages**

- The domain apex can be used to either host your Altis properties or provide apex redirects such as `example.com` to `www.example.com`
- Altis Cloud can manage and verify SSL certificates on your behalf
- Altis Cloud can validate email sending domains, and manage DKIM and SPF records on your behalf

**Disadvantages**

- Any external DNS updates you want to make will need to be handled via Altis Support requests
