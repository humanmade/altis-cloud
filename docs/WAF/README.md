# Web Application Firewall

Altis provides a robust, battle-tested web application firewall (WAF), which provides protection against exploits and attacks. The
Altis WAF is built on AWS technologies, including [AWS WAF](https://aws.amazon.com/waf/)
and [AWS Shield Advanced](https://aws.amazon.com/shield/).

Our firewall has protected customers against active attacks, including HTTP request floods up to 1 million requests per second,
sustained over hours.

## Protection against exploits

The Altis WAF includes protection against web exploits, including Cross-Site Scripting (XSS), SQL injections (SQLi), and other known
inputs. It also includes protection against access to private files accidentally published in your codebase, including .sql and .sh
files.

Our WAF blocks known-bad actors based on IP address and behaviour, using public reputation lists including AWS' IP reputation list and
managed rules for known attack patterns. We also block access to certain sensitive files and system paths, and at an application level
[access to the XML-RPC API (as set in your configuration)](docs://cms/xmlrpc/).

The Altis team proactively manages these protections on your behalf, including updating rules in response to newly discovered
vulnerabilities in WordPress and commonly used plugins.

## Protection against request floods & denial of service attacks

The Altis WAF provides protection against denial of service (DoS) and distributed denial of service (DDoS) attacks through a
multi-layered approach built on AWS WAF and AWS Shield Advanced.

DDoS protection operates at multiple network layers:

- **Layers 3 & 4** (network and transport): Managed automatically through AWS WAF, providing protection against volumetric and
  protocol-based attacks.
- **Layer 7** (application): Managed through WAF rules, and where necessary, through collaboration with the AWS Shield Response
  Team (SRT).

Per-IP rate limits are applied at multiple tiers across all environments as standard:

- **CDN-level rate limits** apply across the entire environment, tuned by our engineers based on your traffic patterns. Contact Altis
  support if you need these adjusted.
- **Per-container rate limits** restrict the rate of requests to dynamic pages (PHP) on each application container.
- **Sensitive page rate limits** apply stricter controls to login and administration pages.

These rate limits are monitored and adjusted across the platform depending on industry-wide threat analysis; contact Altis support
for further information about the currently-applied rate limits.

Altis also includes advanced DDoS protection as standard for our customers. We actively monitor all environments for attacks, with
automated interventions and mitigation. Our engineers are on-call 24/7 to respond if necessary, and we work with the AWS Shield
Response Team (SRT) where needed to mitigate internet-scale attacks.

For customers who experience persistent DDoS attacks, Altis will work with your team to put additional mitigation in place. For
example, stricter rate limits on backend requests (such as `/wp-admin`) may be put in place.

## What if my IP address gets blocked?

Our system operates automatically to detect and mitigate threats. In some cases, this can lead to legitimate users being blocked.

If you believe your IP address has been blocked accidentally, contact Altis support, who can investigate why an IP address may be
blocked.

For customers with additional DDoS mitigation in place, legitimate backend users (such as editors and site admins) may be blocked
at a higher rate than normal. As part of organizing additional mitigation, Altis engineers can relax these mitigation for your
users. (Note that regular firewall rules may still apply.)

You can now self-manage allow lists to allow specific IP addresses through the firewall.

## Monitoring & alerting

Altis maintains a global team of cloud engineers on-call with multiple tiers of coverage, 24/7/365.

We use a wide range of alerts covering both internal and external metrics:

- **Internal metrics**: CPU usage, memory usage, disk space, scaling behaviour, and network throughput.
- **External metrics**: Error rates experienced by customers, including server error thresholds that trigger alerts when a
  significant proportion of requests are failing.

Additionally, urgent support tickets filed via the Altis Dashboard or the emergency email address trigger an alert to on-call
engineers immediately.

## Incident response

When alerts are triggered, our engineers are notified through a tiered escalation system. If the primary on-call engineer does not
acknowledge the alert promptly, it is automatically escalated through secondary and tertiary on-call engineers, and ultimately to
engineering leadership.

Once an alert is validated, engineers begin our incident management process:

1. An incident is created and categorised.
2. Your listed contact emails are notified immediately when an incident is created.
3. Engineers provide regular updates throughout the incident.
4. After resolution, an incident report is provided.
5. Where necessary, a root cause analysis is undertaken and shared.

## Firewall customizations

Our firewall is maintained as standard across our platform, and for most customers, you'll never need to worry about your site. Our
platform also provides a variety of options for limiting access further, such
as [custom nginx configuration](./nginx-configuration.md).

For customers with advanced use cases, the firewall can be customized at an environment level. This includes allowing specific IP
addresses to bypass the firewall, custom blocking rules such as VPN requirements for backend access, and the use of third-party
origins.

Firewall customizations come with a degree of risk, and are carefully managed by our team in conjunction with your requirements. For
example, allowing a specific IP address to bypass these protections may increase the chance of your infrastructure being
overwhelmed. During the initial setup and testing of customizations, the Altis team will work with you to tune the configuration to
manage risk.

Firewall customizations are a non-standard feature of Altis, and may come with additional charges. Contact Altis support to inquire
further.
