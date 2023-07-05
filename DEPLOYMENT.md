# WebAlign deployment guide
## OKD
### Image
WebAlign uses PHP for IAM code-token exchange. Suggested image is `PHP`, the version that is in use in the actual deployment is https://github.com/sclorg/s2i-php-container/blob/master/7.1 (not available any more, 7.3 is the oldest one still supported at the time of writing)
### HTTPS
Both IAM and the Collaboratory environment mandates securing the route. Actual deployment uses the default "Edge" flavour.
### OIDC configuration
Configuration details are taken from environment variables. With the exception of `ebrains_secret_wa` they are not considered sensitive, but one may find it simpler to put all of them into secure storage for the sake of uniformity.
* `ebrains_id_wa=<client-id>`
* `ebrains_secret_wa=<client-secret>`
* `ebrains_redirect_wa=<actual-host>/startpage.php`
* `ebrains_auth=https://iam.ebrains.eu/auth/realms/hbp/protocol/openid-connect/auth`
* `ebrains_token=https://iam.ebrains.eu/auth/realms/hbp/protocol/openid-connect/token`

### Collab app registration
WebAlign launches with its `index.php` file, which can then be omitted.
## Docker
Image is in the `webalign` project, https://docker-registry.ebrains.eu/harbor/projects/95  
It still requires securing the route, which falls outside the scope of this document.  
Environment variables and app registration are same as above.