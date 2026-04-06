<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Tests\Infrastructure\Symfony\Controller;

use Bash\S365ContentBundle\Domain\Dto\S365Response;
use Bash\S365ContentBundle\Domain\HttpClient\ContentClientInterface;
use Bash\S365ContentBundle\Infrastructure\Symfony\Controller\ContentProxyController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class ContentProxyControllerTest extends TestCase
{
    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function test_proxy_controller_filters_headers(): void
    {
        $mockClient = $this->createMock(ContentClientInterface::class);
        $s365Response = new S365Response(
            '{"success":true}',
            200,
            ['content-encoding' => ['gzip'], 'x-custom' => ['value']],
        );

        $mockClient->method('forward')->willReturn($s365Response);

        $controller = new ContentProxyController($mockClient);

        $request = Request::create('/any-path', 'GET');
        $response = $controller($request, 'test-endpoint');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->headers->has('content-encoding'));
        $this->assertTrue($response->headers->has('x-custom'));
    }
}
