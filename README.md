# Laudis Fiscal Library

[![Latest Stable Version](https://poser.pugx.org/laudis/fiscal/v)](//packagist.org/packages/laudis/fiscal)
[![Total Downloads](https://poser.pugx.org/laudis/fiscal/downloads)](//packagist.org/packages/laudis/fiscal)
[![Monthly Downloads](https://poser.pugx.org/laudis/fiscal/d/monthly)](//packagist.org/packages/laudis/fiscal)
[![Maintainability](https://api.codeclimate.com/v1/badges/1d172cb1b3dcd82f4b74/maintainability)](https://codeclimate.com/github/laudis-technologies/fiscal/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1d172cb1b3dcd82f4b74/test_coverage)](https://codeclimate.com/github/laudis-technologies/fiscal/test_coverage)
[![MIT License](https://img.shields.io/apm/l/atomic-design-ui.svg?)](https://github.com/laudis-technologies/fiscal/blob/main/LICENSE)

## Installation

Install via composer:

```bash
composer require laudis/fiscal
```

## General usage

Laudis fiscal library is essentially a client which pulls fiscal information from a database and provides classes to translate it into business logic.

### Initializing the repository

Everything starts from the repository:

```php
$repository = new \Laudis\Fiscal\FiscalRepository(new PDO(getenv('PDO_DSN'), getenv('USERNAME'), getenv('PASSWORD')));
```

### Loading indexed values

Indexed values a values that change over a period in time. Laudis fiscal interprets indexed values as essentially ranges of dates with a value and some identifying information.

```php
// The date argument can either be a string in the Y-m-d format, an integer representing a timestamp, or an object implementing \DateTimeInterface.
$values = $repository->loadIndexedValuesWithSlugs('2010-01-01', ['cpi', 'euribor', 'protected-property-car-amount-AUS']);

$euribor = $values->get('euribor'); // Returns the euribor of that day
$cpi = $values->get('cpi'); // Throws OutOfBoundsException if it wasn't found for that day
$protected = $values->get('protected-property-car-amount-AUS', null); // Will return null instead of throwing when it wasn't found on that day.
```

### Loading scales

Scales are a combination of ranges composed by rules which in turn will apply a factor on the value of each of these rules. 

Example:

Scale personal tax Belgium taxation year 2020 (yes this is real):

|from|until|fixed|percentage|
|---|---|----|---|
|0.00|13 250.00|0.00|25.00%|
|13 250.00|23 390.00|3 312.50|40.00%|
|23 390.00|40 480.00|7 368.50|45.00%|
|40 480.00|...|15 059.00|50.00%|

Laudis fiscal understands this logic which they can be loaded like this:

```php
$scale = $repository->loadScaleWithSlugs(DateTime::createFromFormat('Y-m-d', '2020-01-01'), ['personal-tax'])->first();

echo $scale->calculate(50000); // echos 13 250.00
echo $scale->calculate(8000); // echos 2000
```

Scales can further be exploited to fully explain the logic they are doing:

```php
$explanation = $scale->explain(50000);
```

### Fiscal Feature Support

| **Feature**          | **Supported?** |
|----------------------|----------------|
| Indexed Values       |  Read          |
| Scales               |  Read          |
| Mysql support        |  Yes           |

## Requirements

* PHP >= 7.4
* A mariadb/mysql database
* ext-json
* ext-pdo
* ext-pdo_mysql
