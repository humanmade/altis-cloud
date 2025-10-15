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

The Altis team manages these protections automatically across the platform on your behalf.

## Protection against request floods & denial of service attacks

The Altis WAF provides protection against denial of service (DoS) and distributed denial of service (DDoS) attacks.

Per-IP rate limits are applied across all environments as standard, both across the entire environment as well as per-container, to
ensure individual bad actors are limited from being able to adversely affect your site. Rate limits are monitored and adjusted
across the platform depending on industry-wide threat analysis; contact Altis support for further information about the
currently-applied rate limits.

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

Advanced bypassing of our firewall, such as allowing specific IP addresses through ("whitelisting"), requires firewall
customisation.

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
