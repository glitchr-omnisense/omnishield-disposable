# omnishield/disposable

The disposable e-mail domains as an Omnishield list: is this e-mail's domain a throwaway one
(mailinator.com, yopmail.com...)? The list is a file shipped in the package - the CC0 list of the
disposable-email-domains project - read once per process; a lookup calls nothing and nothing
leaves the site. A command refreshes the list from the project's repository.

## Installation

```sh
composer require omnishield/disposable
```

PHP 8.2 or later and `omnisense/omnishield`: nothing else. No HTTP client while a request runs; the
refresh uses the application's client when given one, PHP's own streams otherwise.

## Credentials

None.

## The list

`data/domains.txt` is `disposable_email_blocklist.conf` of
[disposable-email-domains/disposable-email-domains](https://github.com/disposable-email-domains/disposable-email-domains),
unchanged, at commit `1aac72a07cd89792016dd5d2056bc418d85b8f14` (2026-10-05): 9,205 domains.
Its authors dedicated it to the public domain, [CC0 1.0 Universal](https://creativecommons.org/publicdomain/zero/1.0/):
"You can copy, modify, distribute and use the work, even for commercial purposes, all without
asking permission." `data/SOURCE.md` says so beside it. The project's README is candid about what
a list can be: "We cannot guarantee all of these can still be considered disposable but we do
basic checking so chances are they were disposable at one point in time."

## The matching

The list holds second-level domains - third-level ones under a public suffix - so a domain is on
it when it, **or a domain above it**, is: `eu.mailinator.com` matches `mailinator.com`. The
e-mail's domain is lower-cased, a trailing dot dropped; single labels are never matched.

The site's lists come first: an **allowed** domain is never disposable, whatever the list says;
a **denied** one always is.

| `Reputation` | |
|---|---|
| `known` | on the deny list or the list, and not on the allow list |
| `confidence` | 100 when known: a domain is on a list, not reported now and then |
| `frequency`, `lastSeen` | 0, null |
| `reasons` | `[email, disposable]` |
| `data` | `domain`; `matched` (the part on the list) and `source` (`deny` or `list`); or `allowed` |

An identity without an e-mail is unknown.

## Options

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `list` | string, a readable file | `null`: the shipped `data/domains.txt` | no | a file of the application's: a refreshed copy; unreadable: `InvalidConfigException` |
| `allow` | list, or one comma-separated string | `[]` | no | never disposable for this site; a domain takes in its subdomains |
| `deny` | list, or one comma-separated string | `[]` | no | always disposable for this site |

`DisposableGatewayFactory` takes nothing in its constructor. The gateway also has
`isDisposable($emailOrDomain)`; `DisposableList` (`new DisposableList(?string $path)`) has
`match($domain)`, `count()`, `DisposableList::find()`, `parse()` and `refresh()`, and
`DisposableList::SOURCE` is the address it refreshes from.

## Capabilities

| | |
|---|---|
| is this identity known (`ReputationInterface::lookup()`) | yes, by the e-mail's domain |
| reads | `email` only (an address or a name are ignored) |
| a score | confidence 100 when listed, 0 otherwise |
| reports | no |
| is this token valid, is this content spam | no |
| third party, cookies | none: no call |

## Refreshing

```sh
vendor/bin/omnishield-disposable refresh [<file>]
vendor/bin/omnishield-disposable check <e-mail or domain>... [--list <file>]     # exit 1 when one is disposable
```

```php
DisposableList::refresh('/srv/app/var/disposable_domains.txt', $httpClient);   // the number of domains written
```

The latest list is downloaded from the project's `main` branch, lower-cased, sorted, comments
and blank lines dropped, and written **in one move** (a temporary file, renamed): a request that
reads it meanwhile reads the old list or the new one, never half of either. A download of fewer
than 1,000 domains is taken for broken and nothing is written. Point the gateway's `list` option
at the file; a long-running worker reads the new list when it builds the gateway again.

## Example

The shipped list, a site's allow and deny lists given as environment variables would give them
(run in omnishield's workspace on 2026-10-26, offline):

```php
<?php

require 'vendor/autoload.php';

use Omnishield\Disposable\DisposableGatewayFactory;
use Omnishield\Model\Identity;

$emails = (new DisposableGatewayFactory())->create(['allow' => 'example-school.org', 'deny' => 'throwaway.example']);
foreach (['someone@eu.mailinator.com', 'someone@gmail.com', 'pupil@example-school.org', 'x@throwaway.example', 'no-address'] as $email) {
    $reputation = $emails->lookup(new Identity(email: $email));
    printf("%-28s %s\n", $email, $reputation->known ? 'disposable: '.$reputation->data['matched'].' ('.$reputation->data['source'].')' : 'unknown '.json_encode($reputation->data));
}
```

```
someone@eu.mailinator.com    disposable: mailinator.com (list)
someone@gmail.com            unknown {"domain":"gmail.com"}
pupil@example-school.org     unknown {"domain":"example-school.org","allowed":"example-school.org"}
x@throwaway.example          disposable: throwaway.example (deny)
no-address                   unknown []
```

In Symfony:

```yaml
omnishield:
    gateways:
        emails: { factory: disposable, options: { list: '%kernel.project_dir%/var/disposable_domains.txt', allow: '%env(default::DISPOSABLE_ALLOW)%' } }
```

## Errors

A lookup throws nothing but a `\RuntimeException` when the list's file cannot be read. At
`create()`, a `list` that is not readable is an `InvalidConfigException`. `DisposableList::refresh()`:
an HTTP status other than 200, or a download of fewer than 1,000 domains, a `ProviderException`
(nothing written); no answer through PHP's streams an `UnreachableException` (through the HTTP
client: the client's `UnreachableException` for no answer, 5xx or 429); a file it cannot write a
`\RuntimeException`.

## Verified, and not

| | |
|---|---|
| The family's conformance suite (`Omnishield\Testing\GatewayContractTests`) | **green on 2026-10-25**, offline: `Tests/DisposableContractTest.php`: no request made |
| The shipped list on real domains | **done on 2026-10-07**: mailinator.com, eu.yopmail.com, guerrillamail.com, 10minutemail.com disposable; gmail.com, outlook.fr, example.org, laposte.net, proton.me not |
| The refresh | **done on 2026-10-07**: `vendor/bin/omnishield-disposable refresh` downloaded 9,205 domains with PHP's own streams, `check` read them |
| The allow and deny lists, a list of the application's | by the tests |

## Responsibility

The list is the disposable-email-domains project's, dedicated to the public domain (CC0 1.0), copied
unchanged with its source in `data/SOURCE.md`; this package is not affiliated with its
maintainers. A domain on it "was disposable at one point in time", in the project's words: a site
that refuses an address on it keeps a way for a person wrongly refused (the `allow` option, a
contact). The MIT licence covers this package's code.
