# ddns

php-ddns is a PHP script that allows dynamic updates to DNS records.
This enables you to run your own dynamic DNS update system, getting a similar experience as with public services like [dyn.com][dyndns] or [noip.com][noip].
The API is compatible with the one provided by [dyn.com][dyndns-api], making it usable with most existing clients.

## DESIGN PHILOSOPHY

php-ddns is designed with simplicity in mind (KISS).
It is a lightweight solution intended primarily for small-scale installations, such as home networks, personal servers, or small businesses.
The focus is on ease of use and straightforward configuration rather than scalability or high availability.

This project does **not** aim to be an enterprise-grade solution.
Features like load balancing, redundancy, or large-scale deployments are not a priority.
Instead, php-ddns provides a minimalistic and effective way to manage dynamic DNS updates with as little overhead as possible.

If you need a highly scalable or distributed solution, you may want to consider alternative approaches, such as running a dedicated DNS service with integrated dynamic update support.
However, if you're looking for a simple, no-fuss way to update DNS records dynamically, php-ddns is a great fit.

## PREREQUISITES

To use php-ddns, you must operate your own nameserver and have control over your DNS zones, as this script relies on `nsupdate` for dynamic updates.
This requires specific configuration on the nameserver, particularly the ability to update records using authenticated DNS requests.

On the webserver, you'll need:

- A running PHP installation
- The ability to execute the `nsupdate` command

## CONFIGURATION

### NAMESERVER

The following describes a BIND9 configuration.
For other DNS servers, similar concepts apply, but the exact syntax may differ.

First, create a key to authenticate requests from the webserver.
You can generate a TSIG key using `tsig-keygen`:

```
tsig-keygen -a hmac-sha256 webserver
```

The key should be put into the configuration (e.g. `named.conf`):

```
key "webserver" {
        algorithm hmac-sha256;
        secret "XYP+vYM+xytAaAIMyKuHKy6roW6u/YD/LMN2MFuno+4=";
};
```

Also, define a zone that should be managed by this script by putting the following in `named.conf`:

```
zone "dyn.example.de" IN {
    type master;
    file "zones/dyn.example.com.zone";
    update-policy {
        deny "webserver" name dyn.example.com;
        grant "webserver" subdomain dyn.example.com A AAAA;
    };
};
```

This configuration ensures that only subdomains (e.g., `host.dyn.example.com`) can be modified, while top-level records remain protected.

Depending on your setup, you'll also want to define secondary nameservers and/or configure who is allowed to perform zone transfers, etc.

### WEBSERVER

The webserver must be able to execute `nsupdate`.
For clean URLs, you can use URL rewriting (e.g., `mod_rewrite` for Apache).
Authentication must also be properly passed to the PHP script.

For example, an **Apache + PHP-FPM** configuration might look like this:

```
    # dyn.example.com
    <Directory "/srv/http/example.com/dyndns">
         CGIPassAuth on
         RewriteEngine On
         RewriteRule update update.php [L]
         Require all granted

        <FilesMatch \.php$>
          SetHandler "proxy:unix:/run/php-fpm/dyn-example-com.sock|fcgi://localhost/"
        </FilesMatch>
    </Directory>
```

## API

The script expects HTTP requests with specific parameters. Below is an overview of the supported parameters:

| Parameter  | Description | Possible Values | Example |
|------------|-------------|----------------|----------|
| `myip` | The IP address to be assigned in the DNS record. | A valid IPv4 or IPv6 address. | `1.2.3.4` or `2001:db8::1` |
| `hostname` | The hostname to update. | A fully qualified domain name (FQDN). | `host.dyn.example.com` |
| `username` | Username for authentication. | A registered username. | `myuser` |
| `pass` | Password or token for authentication. | A valid password or token. | `mypass` |
| `type` | Type of record to be updated | `A` or `AAAA` | `A` |
| `ttl` | Time to Live of record to be updated | Value between 30 and 86400 | 300 |

### RESPONSE CODES

| HTTP Code | Message | Meaning |
|-----------|---------|---------|
| `200 OK` | `good` | Update successful |
| `200 OK` | `nxdomain` | Invalid name specified |
| `200 OK` | `badip` | Invalid IP specified |
| `200 OK` | `badtype` | Invalid record type specified |
| `200 OK` | `badprocess` | Server error occurred |
| `401 Unauthorized` | `badauth` | Authentication failed |
| `500 Internal Server Error` | | Server error occurred |

### EXAMPLE API REQUEST

A typical API request would look like this:

https://example.com/dyndns/update.php?hostname=host.dyn.example.com&myip=1.2.3.4&username=myuser&password=mypass

## THEORY OF OPERATION

php-ddns allows clients to initiate an update to a DNS record based on an HTTP request.
This works as follows:

1. A client sends an HTTP request with the necessary parameters (e.g., `myip`, `hostname`, authentication credentials) to a webserver.
2. The PHP script processes the request and verifies authentication.
3. If valid, the script generates an `nsupdate` command to send a dynamic DNS update request to the authoritative DNS server.
4. The DNS server processes the update and modifies the record accordingly.

The relevant RFCs involved in this process are: [RFC 2136][rfc2136], [RFC 2845][rfc2845] and [RFC 3007][rfc3007].

## CONTRIBUTIONS

The source code is maintained using git and lives over at [github.com][repo].
Contributions of any kind are highly welcome. The fastest way is to use pull
requests, and report bugs or submit feature requests.

In case you are looking for something to work on, you probably want to take a
look at the [issue tracker][tracker] or the `TODO` file in the project's root
directory.

## DONATIONS

[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png "Flattr This!")](https://flattr.com/submit/auto?user_id=johnpatcher&url=https://github.com/kbabioch/ddns)

[![PayPal donation](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif "PayPal")](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=karol%40babioch%2ede&lc=DE&item_name=ddns&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest)

Bitcoin: ` 1FmvKC7c9HWC3sSeWuTyhSBdjt9WxARYxd`

## LICENSE

[![GNU GPLv3](http://www.gnu.org/graphics/gplv3-127x51.png "GNU GPLv3")](http://www.gnu.org/licenses/gpl.html)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

[dyndns]: https://www.dyndns.com/
[noip]: https://www.noip.com/
[dyndns-api]: https://help.dyn.com/remote-access-api/
[github]: https://github.com/kbabioch/ddns
[tracker]: https://github.com/kbabioch/ddns/issues
[rfc2136]: https://datatracker.ietf.org/doc/html/rfc2136
[rfc2845]: https://datatracker.ietf.org/doc/html/rfc2845
[rfc3007]: https://datatracker.ietf.org/doc/html/rfc3007
