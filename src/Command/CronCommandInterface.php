<?php

namespace Tarasovich\CronCommands\Command;

/**
 * @method getName() ?string
 */
interface CronCommandInterface
{

    public static function getCronInterval(): string;

}