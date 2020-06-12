# Email Delivery

Altis Cloud can handle vast amounts of email sending, with very good deliverability. Email sending is automatically integrated via a custom `wp_mail` override, therefore developers and plugins don't need to make any extra provisions for sending email on Cloud. This integration is controlled via `aws-ses`.

## Domain Verification

All email delivery on Cloud using `aws-ses` must be sent from email addresses that have their domain verified. Contact support to start the process of verifying your domain for email sending.

Once you have requested a domain for verification, you will be provided DNS entries to add to your DNS Server, or Human Made will configure it if we already control DNS for your domain.

It's also required to configure the code base to send emails from the domain. This can be done by setting the `altis.modules.cloud.email-from-address` setting.

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

Any email address can be used as long as they are all from a verified sending domain. Multiple sending domains can also be used, just get each one verified.

## Email Sending Region

In the event of migrating from one region to another or setting up AWS SES in a region other than the one your application is hosted in you may need to override the default region.

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

## Email Logging

See [Accessing Logs](./logs.md) for details on access send and failure logs for email.
