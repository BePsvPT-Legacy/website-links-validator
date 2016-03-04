# Website Links Validator

[![Build Status](https://travis-ci.org/BePsvPT/website-links-validator.svg?branch=master)](https://travis-ci.org/BePsvPT/website-links-validator)
[![codecov.io](https://codecov.io/github/BePsvPT/website-links-validator/coverage.svg?branch=master)](https://codecov.io/github/BePsvPT/website-links-validator?branch=master)
[![StyleCI](https://styleci.io/repos/52674497/shield?style=flat)](https://styleci.io/repos/52674497)

Validate links from a given website.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Config](#config)
- [Testing](#testing)
- [License](#license)

## Installation

```bash
composer require bepsvpt/website-links-validator
```

## Usage

```php
<?php

$result = Bepsvpt\WebsiteLinksValidator\Validator::validate('https://www.google.com/', $config = []);
```

or you can execute the validator from terminal, the result will save to result.json

```bash
php validate.php
```

## Config

|   name  |         description         | default value |
|:-------:|-----------------------------|:-------------:|
|   deep  | how deep it should validate |       3       |
| timeout | the http timeout seconds    |      10.0     |

## Testing

Note: The testing need a web server on localhost:8000 to complete the testing, the easiest way is using php built-in web server

```bash
php -S localhost:8000 -t ./tests/public
```

After service a web server, using phpunit to run the testing

```bash
phpunit
```

## License

Laravel Security Header is licensed under [The MIT License (MIT)](LICENSE).
