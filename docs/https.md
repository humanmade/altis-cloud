# HTTPS Support

Altis automatically provisions, maintains, and renews TLS certificates for your domains. TLS configuration is managed by Altis, including the use of secure algorithms.

Domains are validated for SSL provisioning as part of the [DNS configuration process](../dns-configuration.md) when adding a new domain.

Certificates issued by Altis use RSA 2048-bit keys, signed by the Amazon certificate authority.

Altis Cloud requires user-agents support SNI (Server Name Indication). The following protocols are currently supported:

- TLS 1.3
- TLS 1.2


## Custom TLS configuration

To meet internal compliance requirements, the use of more limited TLS configuration may be required. Customers with firewall customization packages may request custom TLS configuration.

To request a change to your TLS configuration, contact support with the requested protocol configuration. A full list of supported protocols is available [in the Amazon CloudFront documentation](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/secure-connections-supported-viewer-protocols-ciphers.html). (Altis uses the `TLSv1.2_2021` configuration by default.)


## Custom TLS/SSL certificates

Customers on Mid or Enterprise environment tiers may provide custom certificates for their domains. Altis will manually install these certificates to your CDN distribution, however provisioning and renewal of the certificates remains the customer's responsibility.

Altis recommends against using custom certificates unless required for compliance purposes, as this requires manual management of your certificate and cannot be automatically renewed.

Certificates can be provided along with the private key directly, or Altis can issue a Certificate Signing Request (CSR).


### Requirements

Altis uses the [Amazon CloudFront CDN](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/cnames-and-https-requirements.html), and custom certificates must meet the requirements of CloudFront. In particular:

* ECDSA certificates are supported (256-bit keys only)
* RSA certificates are supported (1024-bit or 2048-bit keys only)
* Provided certificates must include the full certificate chain (including imermediate and root certificates)


### Altis-provided Certificate Signing Request

To request a CSR from Altis, contact support with the following details:

* Subject Name details, including:
	* Organization
	* Department
	* Country
	* Email address
* Common Name
* Subject Alternate Names

Please note that the SAN field must include the internal Altis domain for your environment (i.e. `[env].altis.cloud`).

Once Altis has provided the CSR, the generated certificate should be provided in PEM format (.cer) in the same ticket. Please allow up to a week for the verification and installation of custom certificates.

The certificate must be compatible with the provided CSR; you can verify this by confirming the output of the following commands matches:

```sh
openssl req -noout -modulus -in <path to CSR> | openssl md5
openssl x509 -noout -modulus -in <path to Certificate> | openssl md5
```


### Certificate and Key

To provide a custom certificate without using an Altis-provided CSR, contact support with the following:

* Private key
* Main certificate
* Full certificate chain, including intermediate certificates and root certificate

Please allow up to a week for the verification and installation of custom certificates.
