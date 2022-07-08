
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

### Step 3: Configure the Bundle

Then configure the bundle by creating `config/packages/cron_commands.yaml`: 

```yaml
# config/packages/cron_commands.yaml

cron_commands:
  locks:
    enabled: true # Enable lock listener
    template: 'var/run/{command_dashes}.{env}.lock' # Lock file name template relative to project dir or absolute

  linux_config_generation:
    enabled: true # Enable linux config generation command
    templates:
      task: '{interval} {user} php {bin} --env={env} {command} {logging}' # Task template
      log_filename: '{command_dashes}.{env}.log' # Log file name template
    default_options: # Default command options
      bin: '%kernel.project_dir%/bin/console'
      logs: '%kernel.logs_dir%'
      output: '%kernel.project_dir%/var/tmp/self-serve-cron.conf'
      user: '{current_user}'
```