<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('s365_content');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('base_url')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('client_id')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('client_secret')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('project')->isRequired()->cannotBeEmpty()->end()
            ->integerNode('ttl_token')->defaultValue(2592000)->end()
            ->booleanNode('disable_cache')->defaultFalse()->end()
            ->end();

        return $treeBuilder;
    }
}
