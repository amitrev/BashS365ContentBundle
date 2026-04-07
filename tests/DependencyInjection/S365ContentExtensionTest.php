<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Tests\DependencyInjection;

use Bash\S365ContentBundle\DependencyInjection\S365ContentExtension;
use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class S365ContentExtensionTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function test_extension_loads_services(): void
    {
        $container = new ContainerBuilder();

        $extension = new S365ContentExtension();

        $config = [
            'base_url' => 'https://api',
            'username' => 'u',
            'password' => 'p',
            'client_id' => 'id',
            'client_secret' => 's',
            'project' => 'proj',
        ];

        $extension->load([$config], $container);

        $this->assertTrue($container->hasDefinition(ContentClient::class));
    }
}
