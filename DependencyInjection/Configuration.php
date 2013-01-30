<?php

namespace Zveen\TwitterBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('zveen_twitter');

        $rootNode
            ->children()
            ->scalarNode('class')->defaultValue('Zveen\TwitterBundle\Services\Twitter')->end()
            ->scalarNode('consumerKey')->isRequired()->end()
            ->scalarNode('consumerSecret')->isRequired()->end()
            ->scalarNode('requestTokenUrl')->defaultValue('https://api.twitter.com/oauth/request_token')->end()
            ->scalarNode('accessTokenUrl')->defaultValue('https://api.twitter.com/oauth/access_token')->end()
            ->scalarNode('authUrl')->defaultValue('https://api.twitter.com/oauth/authorize')->end()
            ->scalarNode('debug')->defaultValue(false)->end()
            ->scalarNode('checkSSL')->defaultValue(false)->end()
            ->end();


        return $treeBuilder;
    }
}
