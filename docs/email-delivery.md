# Email Delivery

Altis environments are pre-configured to send email through the `wp_mail()` API.

Altis uses [Amazon SES](https://aws.amazon.com/ses/) to send email, ensuring high deliverability rates.

**Before your environment can send any email, you must verify any domains you are sending email from.**


## Domain Verification

All email delivery must be sent from email addresses that have their domain verified. Altis automatically verifies your internal domains (`*.altis.cloud` for email sending), but custom domains including your production domain must be verified through DNS records.

Email verification is typically handled when [adding domains and configuring DNS](./dns-configuration.md), but in some cases, may need to be verified afterwards.

Contact support to start the process of verifying your domain for email sending. Once you have requested a domain for verification, you will be provided DNS entries to add to your DNS Server.

You also need to set your email sender address within your Altis configuration. This can be done by setting the `altis.modules.cloud.email-from-address` setting.

```json
{
	"modules": {
		"cloud": {
			"email-from-address": "webmaster@mydomainname.com"
		}
	}
}
```

For advanced configuration of the sending email address for outbound mail, use the `wp_mail_from` filter to change the value at runtime:

```php
add_filter( 'wp_mail_from', function ( $email ) {
	return 'no-reply@mydomainname.com';
} );
```

To configure the "from name" of outbound emails by using the `wp_mail_from_name` filter:

```php
add_filter( 'wp_mail_from_name', function ( $name ) {
	return 'Joe Bloggs';
} );
```

Any email address can be used, provided it uses a verified sending domain. Multiple sending domains can be used once verified.


## Email Logging

Email logs are [available within the Altis Dashboard](./dashboard/logs.md) containing information about sent email and deliverability.


## Acceptable Usage

Altis can send transactional email (e.g. signup requests, ecommerce receipts, automated alerts) or marketing email (e.g. newsletters). Altis email systems can handle large volumes of email as necessary, and prior authorisation is not necessary for these uses.

Sending unsoliticed email (spam) is not permitted and may lead to account termination.

Email deliverability and bounce rates are your responsibility. The Altis team monitors your bounce rate and may require changes to your codebase if they determine you are exceeding reasonable use of the email services. Failure to rectify may lead to account termination.


## Email Sending Region

Altis automatically uses the most appropriate region to send email from depending on your [origin location](./origin-locations.md).

The Altis team may direct you to change email region if migrating from an existing setup, or based on availability of email services. Do not change this configuration unless directed to do so by Altis.

To configure the sending region set the `email-region` configuration option to a valid AWS region code as shown in the following example. (By default this is set to `false`, which uses the default region.)

```json
{
	"modules": {
		"cloud": {
			"email-region": "us-east-1"
		}
	}
}
```


## Disabling SES

**Note:** Disabling the built-in email configuration is considered to void your warranty, except as directed by the Altis team.

The AWS SES integration can be switched off if you intend to use an alternative email delivery service using the following configuration:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "aws-ses-wp-mail": false
                }
            }
        }
    }
}
```