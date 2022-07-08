<?php

namespace Tarasovich\CronCommands\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Path;
use Tarasovich\CronCommands\Command\CronCommandInterface;
use Tarasovich\CronCommands\Command\LockedCronCommandInterface;
use Tarasovich\CronCommands\Util\PlaceholdersHelper;

final class CronCommandsExtension extends Extension implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'cron_commands';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->loadServices($container);

        $container->registerForAutoconfiguration(CronCommandInterface::class)
            ->addTag('tarasovich.cron.command');

        $container->registerForAutoconfiguration(LockedCronCommandInterface::class)
            ->addTag('tarasovich.cron.command_locked');

        if (!$config['linux_config_generation']['enabled']) {
            $container->removeDefinition('tarasovich.cron_commands.command.generate_config');
        } else {
            $container->getDefinition('tarasovich.cron_commands.command.generate_config')
                ->replaceArgument('$templates', $config['linux_config_generation']['templates'])
                ->replaceArgument('$defaultOptions', $config['linux_config_generation']['default_options']);
            ;
        }

        if (!$config['locks']['enabled']) {
            $container->removeDefinition('tarasovich.cron_commands.event_listener.command_lock');
        } else {
            $container->getDefinition('tarasovich.cron_commands.event_listener.command_lock')
                ->replaceArgument('$template', $config['locks']['template'])
            ;
        }
    }

    protected function loadServices(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('tarasovich.cron_commands.event_listener.command_lock')) {
            return;
        }

        $lockedCommands = $container->findTaggedServiceIds('tarasovich.cron.command_locked');
        if (!count($lockedCommands)) {
            $container->removeDefinition('tarasovich.cron_commands.event_listener.command_lock');
            return;
        }

        $lockTemplate = $container->getDefinition('tarasovich.cron_commands.event_listener.command_lock')
            ->getArgument('$template');
        $locksDir = pathinfo(Path::makeAbsolute(
            PlaceholdersHelper::processTemplate($lockTemplate, null),
            $container->getParameter('kernel.project_dir')
        ), PATHINFO_DIRNAME);
        if (!file_exists($locksDir)) {
            if (!mkdir($locksDir, umask(), true)) {
                throw new \ErrorException(sprintf('Can not create locks directory "%s"', $locksDir));
            }
        } elseif (!is_dir($locksDir)) {
            throw new \ErrorException(sprintf('Locks path "%s" is not directory', $locksDir));
        }

        if (!is_writable($locksDir)) {
            throw new \ErrorException(sprintf('Locks directory "%s" is not writable', $locksDir));
        }
    }
}