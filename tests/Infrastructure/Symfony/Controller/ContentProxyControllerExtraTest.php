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

class ContentProxyControllerExtraTest extends TestCase
{
    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function test_post_body_is_forwarded(): void
    {
        $mockClient = $this->createMock(ContentClientInterface::class);

        $mockClient->expects($this->once())
            ->method('forward')
            ->with(
                'POST',
                'endpoint',
                $this->callback(function ($options) {
                    return isset($options['body'], $options['headers']['Content-Type']) && '{"foo":"bar"}' === $options['body'];
                }),
            )
            ->willReturn(new S365Response('{"ok":true}', 201, ['x-custom' => ['v']]));

        $controller = new ContentProxyController($mockClient);

        $request = Request::create('/any', 'POST', [], [], [], [], '{"foo":"bar"}');
        $request->headers->set('Content-Type', 'application/json');

        $response = $controller($request, 'endpoint');

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('{"ok":true}', $response->getContent());
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function test_query_parameters_are_forwarded(): void
    {
        $mockClient = $this->createMock(ContentClientInterface::class);

        $mockClient->expects($this->once())
            ->method('forward')
            ->with(
                'GET',
                'endpoint',
                $this->callback(function ($options) {
                    return isset($options['query']['a']) && 'b' === $options['query']['a'];
                }),
            )
            ->willReturn(new S365Response('{"ok":true}', 200, []));

        $controller = new ContentProxyController($mockClient);

        $request = Request::create('/any', 'GET', ['a' => 'b']);

        $response = $controller($request, 'endpoint');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_put_and_delete_methods_forwarded(): void
    {
        $mockClient = $this->createMock(ContentClientInterface::class);

        // We don't use withConsecutive(); instead assert call count and return values in sequence
        $mockClient->expects($this->exactly(2))
            ->method('forward')
            ->willReturnOnConsecutiveCalls(
                new S365Response('{"ok":true}', 204, []),
                new S365Response('{"ok":true}', 204, []),
            );

        $controller = new ContentProxyController($mockClient);

        $requestPut = Request::create('/any', 'PUT');
        $requestDelete = Request::create('/any', 'DELETE');

        $controller($requestPut, 'endpoint');
        $response = $controller($requestDelete, 'endpoint');

        $this->assertEquals(204, $response->getStatusCode());
    }
}
