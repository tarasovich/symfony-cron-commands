<?php

namespace Tarasovich\CronCommands\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cron_commands');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('locks')
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('template')->defaultValue('%kernel.project_dir%/var/run/{command_dashes}.{env}.lock')->end()
                    ->end()
                ->end()
                ->arrayNode('linux_config_generation')
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->arrayNode('templates')
                            ->children()
                                ->scalarNode('task')->defaultValue('{interval} {user} php {bin} --env={env} {command} {logging}')->end()
                                ->scalarNode('log_filename')->defaultValue('>> {command_dashes}.{env}.log')->end()
                            ->end()
                        ->end()
                        ->arrayNode('default_options')
                            ->children()
                                ->scalarNode('bin')->defaultValue('%kernel.project_dir%/bin/console')->end()
                                ->scalarNode('logs')->defaultValue(null)->end()
                                ->scalarNode('output')->defaultValue(null)->end()
                                ->scalarNode('user')->defaultValue('{current_user}')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
