# HTTPS

Unless there are specifics reasons against it, we recommend all projects use HTTPS to encrypt traffic between Cloud and end-users. We can register and manage HTTPS certificates which cover all your domains, or you can provide the Cloud team with your HTTPS certificates. Unless you are planning to use an Extended Validation certificate, we recommend Human Made register the certificates.

If you wish to bring your own HTTP certificate, you must provide a single certificate to cover all domains for your project. That can be a wildcard certificate, or additional domain names provided in the SAN (Subject Alternative Names) field.

In cases where Human Made registers HTTPS certificates, we will manage auto-renewals on a yearly basis and will provide your team with DNS verification records to validate the certificate request. All certificates are RSA 2048-bit with signature algorithm `SHA256WITHRSA` by the root CA "Amazon Root CA 1".

HTTPS on Cloud requires user-agents support SNI (Server Name Indication). The following protocols are supported:

- TLS 1.2
- TLS 1.1
- TLS 1.0
