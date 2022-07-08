<?php

namespace Tarasovich\CronCommands\Util;

use Tarasovich\CronCommands\Command\CronCommandInterface;

class PlaceholdersHelper
{

    public static function getCommandPlaceholders(?CronCommandInterface $command): array
    {
        return [
            '{interval}' => $command ? $command::getCronInterval() : '* * * * *',
            '{current_user}' => get_current_user(),
            '{user}' => get_current_user(),
            '{env}' => $_ENV['APP_ENV'],
            '{command}' => $command ? $command->getName() : 'test',
            '{command_dashes}' => $command ? preg_replace('~-+~', '-', preg_replace('~[^\w]+~', '-', $command->getName())) : 'test',
        ];
    }

    public static function processTemplate(string $template, ?CronCommandInterface $command, array $placeholders = []): string
    {
        $placeholders = array_merge(self::getCommandPlaceholders($command), $placeholders);

        return strtr($template, $placeholders);
    }
}