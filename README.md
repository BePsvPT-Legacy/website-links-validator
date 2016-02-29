# Website Links Validator

[![Build Status](https://travis-ci.org/BePsvPT/website-links-validator.svg?branch=master)](https://travis-ci.org/BePsvPT/website-links-validator)
[![StyleCI](https://styleci.io/repos/52674497/shield?style=flat)](https://styleci.io/repos/52674497)

Validate links from a given website.

## Installation

```bash
composer require bepsvpt/website-links-validator
```

## Usage

```php
<?php

$result = Bepsvpt\WebsiteLinksValidator\Validator::validate('https://www.google.com/', $config = []);
```
or you can execute the validator from terminal

```bash
php validate.php
```

## Config

```
deep: how deep it should validate, default 3

timeout: the http timeout seconds, default 10.0 seconds
```
