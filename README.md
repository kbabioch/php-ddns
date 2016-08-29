# ddns

ddns is a PHP script allowing for updates to DNS records. This allows you
to run your own dynamic DNS update system similar to public ones like 
[dyndns.com][dyndns], [noip.com][noip], etc. The API is based on the one
provided by [dyndns.com][dyndns-api] itself, so it should be compatible with
most clients out there.

KISS ... intended for small installations ...

## PREREQUISITES

Run own nameserver, i.e. be in control of your zones.

## API

Parameters:

 - myip
 - hostname
 - ...

## THEORY OF OPERATION

After some basic option and argument parsing, otca sets up a suitable
temporary environment for the `ca(1)` command. It then generates and self-signs
a certificate for the CA, handing over the appropriate options. Afterwards
a [certificate signing request][csr] for the server and client is generated
using OpenSSL's `req(1)` command. These CSRs are then signed by the previously
created CA using the `ca(1)` command once more. After some conversions (see
`pkcs12(1)`), the certificates and keys are moved into the specified output
directory. Then the temporary scratch space is removed, including the CA's
private key. This, in essence, renders the CA useless, which is the point of
this concept.

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

