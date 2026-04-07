<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\DependencyInjection;

use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentAuthenticator;
use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentClient;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class S365ContentExtension extends Extension
{
    public function getAlias(): string
    {
        return 's365_content';
    }

    /**
     * @param array<string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    /**
     * @param array<string, mixed>[] $configs
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition(ContentClient::class);
        $definition->setArgument('$project', $config['project']);
        $definition->setArgument('$disableCache', $config['disable_cache']);

        $authDefinition = $container->getDefinition(ContentAuthenticator::class);
        $authDefinition->setArgument('$username', $config['username']);
        $authDefinition->setArgument('$password', $config['password']);
        $authDefinition->setArgument('$clientId', $config['client_id']);
        $authDefinition->setArgument('$clientSecret', $config['client_secret']);
        $authDefinition->setArgument('$ttlCachedToken', $config['ttl_token']);
    }
}
