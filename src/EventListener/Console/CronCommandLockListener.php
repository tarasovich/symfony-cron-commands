<?php

namespace Tarasovich\CronCommands\EventListener\Console;

use Symfony\Component\Filesystem\Path;
use Tarasovich\CronCommands\Command\CronCommandInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tarasovich\CronCommands\Command\LockedCronCommandInterface;
use Tarasovich\CronCommands\Util\PlaceholdersHelper;

class CronCommandLockListener
{
    public function __construct(
        private string $projectDir,
        private string $template,
    ) {
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof LockedCronCommandInterface) {
            return;
        }

        $lockFile = $this->getLockFile($command);
        if (file_exists($lockFile)) {
            $event->disableCommand();

            $pid = file_get_contents($lockFile);

            $io = new SymfonyStyle($event->getInput(), $event->getOutput());
            $io->error(sprintf('Command "%s" already running PID: %d', $command->getName(), $pid));

            return;
        }

        file_put_contents($lockFile, getmypid());
    }

    public function onTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        if (!$command instanceof CronCommandInterface) {
            return;
        }

        $lockFile = $this->getLockFile($command);
        if ($event->getExitCode() === ConsoleCommandEvent::RETURN_CODE_DISABLED || !file_exists($lockFile)) {
            return;
        }

        unlink($lockFile);
    }

    private function getLockFile(CronCommandInterface $command): string
    {
        return Path::makeAbsolute(PlaceholdersHelper::processTemplate($this->template, $command), $this->projectDir);
    }
}