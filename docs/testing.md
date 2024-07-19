# Testing

In order to ensure the performance, reliability, and security of your site, you may wish to perform testing against your Altis
environments. Altis permits penetration testing and load testing against your site, with notice provided.

## Load Testing

Load testing is typically used to ensure hosting infrastructure is able to meet demand. On Altis, your server infrastructure
automatically scales to meet demand, so load testing is not typically required. Automatic scaling is assisted by our engineers
rightsizing your environments when you onboard to the platform, as well as continual tuning and adjustment based on our assessment.

While small-scale load testing may be conducted against your production environment, these requests will count towards your billable
page views. Large-scale load testing may be detected by our [web application firewall](./firewall.md) as a potential denial of
service (DoS) attack, and automatically mitigated.

Altis can provide a production-like environment specifically for load testing. Rate limits and denial of service (DoS) mitigation
can be disabled specifically for testing purposes. This environment may come with additional charges. Contact Altis support to
organize a load testing environment.

Before performing any load testing, contact Altis support at least one week in advance of any testing with the following details:

- Environments under test
- Types of test taking place
- Testing dates
- Test origin, including supplier and IP addresses where possible
- Contact information
- Expected size of test (total requests)
- Expected peak load of test (requests per minute)

Note that load testing should not be performed against non-production environments, as these environments have different scaling
characteristics compared to production environments. In particular, stricter limits and maximums are imposed, as well as different
algorithms for how auto-scaling applies.

DDoS simulations and network stress tests may not be performed unless explicitly approved by Altis.

## Penetration Testing

Penetration testing ("pen testing") can be performed against your environments in order to test the security of your codebase.

Altis also performs penetration testing against the platform as a whole on a yearly basis, including an out-of-the-box configuration
of Altis. A copy of our latest penetration testing report is available upon request.

We recommend performing penetration testing against non-production environments (such as staging) where possible, to avoid affecting
live user traffic. Ensure your non-production environment is up-to-date with your production branches before conducting these tests,
to ensure your tests are representative.

Testing of the following is permitted with prior notice:

- Application security (including XSS, CSRF)
- Input fuzzing
- Privacy and data egress tests

The following is not permitted at any time:

- Testing infrastructure not dedicated exclusively to you, such as the Altis Dashboard
- DNS zone walking
- Denial of Service (DoS) or Distributed Denial of Service (DDoS) simulations
- Port, protocol, or request flooding

Testing is also subject to the [AWS Penetration Testing requirements](https://aws.amazon.com/security/penetration-testing/).

Before performing any penetration testing, contact Altis support at least one week in advance of any testing with the following
details:

- Environments under test
- Types of test taking place
- Testing dates
- Test origin, including supplier and IP addresses where possible
- Contact information

This information ensures Altis can respond correctly to any incidents occurring as a result of your test.

Note that [the Altis firewall](./firewall.md) will remain active throughout your test, as it is an active part of mitigating
threats. Customers with [firewall customisations](./firewall.md#firewall-customizations) may request bypass of certain rules for
testing purposes.
