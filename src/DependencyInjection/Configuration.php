<?php

/*
 * This file is part of the FiveLab Migrator package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

declare(strict_types = 1);

namespace FiveLab\Component\Migrator\DependencyInjection;

use FiveLab\Component\Migrator\Factory\NativeMigrationFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('fivelab_migrator');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->append($this->getMigratorsNode())
            ->end();

        return $treeBuilder;
    }

    private function getMigratorsNode(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('migrations')
            ->beforeNormalization()
                ->ifArray()
                ->then(static function (array $entry): array {
                    $normalized = [];

                    foreach ($entry as $key => $value) {
                        $value['group'] = $value['group'] ?? $key;
                        $normalized[$key] = $value;
                    }

                    return $normalized;
                })
            ->end();

        /** @var NodeBuilder $childNode */
        $childNode = $node
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children();

        $childNode
            ->scalarNode('group')
                ->info('The unique group for migrator.')
                ->defaultValue(null)
            ->end()

            ->scalarNode('path')
                ->info('The path to migrations.')
                ->isRequired()
            ->end()

            ->scalarNode('factory')
                ->info('The service id of factory for create migrations.')
                ->defaultValue(NativeMigrationFactory::class)
            ->end()

            ->scalarNode('history')
                ->info('The service id of migration history.')
                ->isRequired()
            ->end();

        return $node;
    }
}
