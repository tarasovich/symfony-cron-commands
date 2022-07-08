
Symfony Cron Commands Bundle
============================

Tools to declare symfony commands as cron tasks (Linux cron config generation and command lock listener)

##### Interfaces:
* CronCommandInterface - general cron command, you need to declare getCronInterval(): string
* LockedCronCommandInterface - enables lock listener on the command
* LoggedCronCommandInterface - enables output redirection for the command in linux config generation
* LockedLoggedCronCommandInterface - enables lock listener and output redirection


Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require tarasovich/symfony-cron-commands
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Tarasovich\CronCommands\CronCommandsBundle::class => ['all' => true],
];
```