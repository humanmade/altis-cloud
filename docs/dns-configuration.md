# DNS Configuration

To route a custom domain to the Altis platform you will need to configure DNS records. Once your account is created and you are ready to configure DNS, Altis Support will provide you a project-specific domain to use in a CNAME record. This will be in the form `[project].altis.cloud`. For example:

```
www.example.com CNAME my-project.altis.cloud
```

Altis can only provide hostnames to be used in DNS records, not IP addresses. Therefore you _must_ use CNAME records when configuring and managing your own DNS. CNAME records are not supported on the domain apex (or "naked domain") such as `example.com`.

All Altis properties must be hosted on a subdomain, such as `www.` unless using Altis managed nameservers.

## Domain Apex

It's common to implement a redirect from the domain apex to the subdomain, such as redirecting `example.com` to `www.example.com`. There are two options to support this in Altis Cloud:

1. Use Altis managed nameservers (see below)
1. Use your domain registrar or self-hosted service to redirect the domain apex to the subdomain. Many domain registrars support this service, and typically call it "web forwarding" or "web redirect".

## Managed Nameservers

Altis can manage the DNS records for you, by being the authoratative nameserver for your domain. This has some advantages and disadvantages:

**Advantages**

- The domain apex can be used to either host your Altis properties or provide apex redirects such as `example.com` to `www.example.com`
- Altis Cloud can manage and verify SSL certificates on your behalf
- Altis Cloud can validate email sending domains, and manage DKIM and SPF records on your behalf

**Disadvantages**

- Any external DNS updates you want to make will need to be handled via Altis Support requests
