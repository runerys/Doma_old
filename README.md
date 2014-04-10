Doma
====

Customizing the Doma Map Archive by Mats Troeng (http://www.matstroeng.se/doma/).

Baseline is v3.0.5

Deployed to Microsoft Azure as a Web Site at http://domarunerys.azurewebsites.net/src/.

Using automatic Git deployment and configuration values from application settings in the portal.

## Implemented features
- Show image file size
- Abort image upload when exeeding file size limit
- Send email via SMTP (our webserver was blocked)
- RSS: Filter out maps by tagging dicipline field
- RSS: Override filter with secret parameter value

## Planned features
- Allow private user interaction. Today, login is blocked.