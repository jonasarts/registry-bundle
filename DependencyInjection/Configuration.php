<?php

/*
 * This file is part of the Registry bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\RegistryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ja_registry');

        $rootNode
            ->children()
                ->arrayNode('globals')
                    ->addDefaultsIfNotSet()
                    ->children()
                        // mode
                        ->enumNode('mode')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->values(array('doctrine', 'redis'))
                            ->defaultValue('doctrine')
                        ->end()
                        // default registry keys yaml file
                        ->scalarNode('defaultkeys')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        // key name delimiter (for registry keys concatenation)
                        ->scalarNode('delimiter')
                            ->cannotBeEmpty()
                            ->defaultValue('/')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('redis')
                    ->addDefaultsIfNotSet()
                    ->children()
                        // prefix
                        ->scalarNode('prefix')
                            ->defaultValue('registry')
                        ->end()
                        // (prefix) key name delimiter (for redis keys concatenation)
                        ->scalarNode('delimiter')
                            ->cannotBeEmpty()
                            ->defaultValue(':')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
