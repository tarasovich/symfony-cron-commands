<?php

namespace Tarasovich\CronCommands\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Tarasovich\CronCommands\Util\PlaceholdersHelper;

class GenerateLinuxConfigCommand extends Command
{
    protected array $templates = [
        'task' => null,
        'log_filename' => null,
    ];

    protected array $defaultOptions = [
        'bin' => null,
        'logs' => null,
        'user' => null,
        'output' => null,
    ];

    public function __construct(
        private iterable $cronCommands,
        array $templates,
        array $defaultOptions = [],
    ) {
        foreach ($templates as $template => $value) {
            if (!array_key_exists($template, $this->templates)) {
                return;
            }

            $this->templates[$template] = $value;
        }

        foreach ($defaultOptions as $option => $value) {
            if (!array_key_exists($option, $this->defaultOptions)) {
                return;
            }

            if (is_string($value)) {
                $value = strtr($value, [
                    '{current_user}' => get_current_user(),
                    '{env}' => $_ENV['APP_ENV'],
                ]);
            }

            $this->defaultOptions[$option] = $value;
        }

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('bin', 'b', InputOption::VALUE_REQUIRED, 'Console bin path', $this->defaultOptions['bin']);
        $this->addOption('logs', 'l', InputOption::VALUE_REQUIRED, 'Logs directory (leave empty to disable)', $this->defaultOptions['logs']);
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output config path (leave empty to stdout)', $this->defaultOptions['output']);
        $this->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User will run commands', $this->defaultOptions['user']);
    }

    /**
     * @throws \ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = $this->getOptions($input);

        $io = new SymfonyStyle($input, $output);
        $io->title('Options:');

        $io->writeln('commands: ' . $commandsCount = $this->cronCommands->count());
        foreach ($options as $option => $value) {
            if ($value === null) {
                continue;
            }

            $io->writeln($option . ': ' . $value);
        }

        if (!$commandsCount) {
            return self::SUCCESS;
        }

        $lineBreak = "\n";
        $content = '# Symfony cron commands' . $lineBreak;
        foreach ($this->cronCommands as $command) {
            /** @var CronCommandInterface|Command $command */
            if (!empty($options['logs']) && $command instanceof LoggedCronCommandInterface) {
                $logging = '>> ' . $this->getLogFile($command, $options);

            } else {
                $logging = '';
            }

            $task = PlaceholdersHelper::processTemplate($this->templates['task'], $command, [
                '{user}' => $options['user'],
                '{bin}' => $options['bin'],
                '{logging}' => $logging,
            ]);

            $content .= trim($task) . $lineBreak;
        }

        $io->title('Generated content:');
        $io->writeln($content);
        $io->newLine();

        if (!empty($options['output'])) {
            file_put_contents($options['output'], $content);

            $io->title('Written to:');
            $io->writeln($options['output']);
            $io->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * @throws \ErrorException
     */
    protected function getOptions(InputInterface $input): array
    {
        $options = [];
        foreach (array_keys($this->defaultOptions) as $option) {
            $value = $input->getOption($option);
            if (empty($value) || $value === 'false') {
                $value = null;

                if (in_array($option, ['bin', 'user'])) {
                    throw new \ErrorException(sprintf('"%s" option is required', $option));
                }
            } elseif (in_array($option, ['bin', 'logs', 'output'])) {
                $value = Path::makeAbsolute($value, getcwd());
            }

            $options[$option] = $value;
        }

        if (!file_exists($options['bin']) || !is_file($options['bin'])) {
            throw new \ErrorException(sprintf('Console bin path: "%s" is not existed', $options['bin']));
        }

        if (!empty($options['output'])) {
            if (!file_exists($options['output'])) {
                $outputDir = pathinfo($options['output'], PATHINFO_DIRNAME);
                if (!file_exists($outputDir)) {
                    if (!mkdir($outputDir, umask(), true)) {
                        throw new \ErrorException(sprintf('Can not create output directory "%s"', $outputDir));
                    }
                } elseif (!is_dir($outputDir)) {
                    throw new \ErrorException(sprintf('"%s" is not directory', $outputDir));
                }

                if (!touch($options['output'])) {
                    throw new \ErrorException(sprintf('Can not create an output file "%s"', $options['output']));
                }
            }

            if (!is_file($options['output'])) {
                throw new \ErrorException(sprintf('Output path "%s" is not a file', $options['output']));
            }

            if (!is_writable($options['output'])) {
                throw new \ErrorException(sprintf('Output path "%s" is not writable', $options['output']));
            }
        }

        return $options;
    }

    /**
     * @throws \ErrorException
     */
    protected function getLogFile(LoggedCronCommandInterface $command, array $options): string
    {
        $logsDir = $options['logs'];

        if (!file_exists($logsDir) && !mkdir($logsDir, umask(), true)) {
            throw new \ErrorException(sprintf('Can not create logs directory "%s".', $logsDir));
        }

        if (!is_dir($logsDir)) {
            throw new \ErrorException(sprintf('"%s" is not a directory.', $logsDir));
        }

        $logFile = $logsDir . DIRECTORY_SEPARATOR . PlaceholdersHelper::processTemplate($this->templates['log_filename'], $command);
        if (!file_exists($logFile)) {
            if (!touch($logFile)) {
                throw new \ErrorException(sprintf('Can not create log file "%s" for "%s" command', $logFile, $command->getName()));
            }
        } elseif (!is_file($logFile)) {
            throw new \ErrorException(sprintf('Log path "%s" for "%s" command is exists, but not a file.', $logFile, $command->getName()));
        }

        if (!is_writable($logFile)) {
            throw new \ErrorException(sprintf('Log file "%s" for "%s" command is not writable.', $logFile, $command->getName()));
        }

//        if ($options['user'] === get_current_user() && !is_writable($logsDir)) {
//            throw new \ErrorException(sprintf('Directory "%s" is not writable by "%s" user.', $logsDir, $options['user']));
//        }

        return $logFile;
    }
}
