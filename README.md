# quickmetrics-php

[![Build Status](https://img.shields.io/travis/jlis/quickmetrics-php/master.svg?style=flat-square)](https://travis-ci.org/jlis/quickmetrics-php)
[![StyleCI](https://styleci.io/repos/221655563/shield)](https://styleci.io/repos/221655563)

A unofficial PHP client for Quickmetrics (quickmetrics.io)

## Install

To install the client you will need to be using [Composer]([https://getcomposer.org/)
in your project. To install it please see the [docs](https://getcomposer.org/download/).

Install the client using

```bash
composer require jlis/quickmetrics-php
```

## Usage

```php
// create the client
$client = new \Jlis\Quickmetrics\Client('quickmetrics api key');

// collect the event
$client->event('event.name', 1.0, 'dimension');

// send the event to Quickmetrics.io
$client->flush();
```

You can also initialize the client with other options:

```php
$client = new \Jlis\Quickmetrics\Client('your api key', [
    'url' => 'https://someotherurl.tld',
    'max_batch_size' => 10,
    'timeout' => 5,
    'connect_timeout' => 2,
]);
```

There are the following options:

| Option            | Usage                                                                                            | Type   | Default              |
|-------------------|--------------------------------------------------------------------------------------------------|--------|----------------------|
| `url`             | The Quickmetrics API endpoint                                                                    | string | https://qckm.io/list |
| `max_batch_size`  | Amount of collected events until a batch request is triggered without the need to call `flush()` | int    | 100                  |
| `timeout`         | Number describing the timeout of the request in seconds                                          | int    | 1                    |
| `connect_timeout` | Number describing the number of seconds to wait while trying to connect to a server              | int    | 1                    |

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Dependencies are managed through composer:

```
$ composer install
```

Tests can then be run via phpunit:

```
$ vendor/bin/phpunit
```

## Thanks

Big thanks to [Billomat](https://billomat.com) which let my develop this library during my work time.

<a href="https://billomat.com" target="_blank" align="left">
    <img src="https://www.billomat.com/wp-content/uploads/2019/07/logo_300_tr_ohneslogan_1000x226.png" width="180">
</a>

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
