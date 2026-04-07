<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Tests\Infrastructure\HttpClient;

use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentAuthenticator;
use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ContentClientTest extends TestCase
{
    public function test_forward_includes_correlation_id_header(): void
    {
        // Auth http client stub (returns token)
        $authHttpClient = $this->createMock(HttpClientInterface::class);

        $authResponse = $this->createMock(ResponseInterface::class);
        $authResponse->method('getStatusCode')->willReturn(200);
        $authResponse->method('toArray')->willReturn(['access_token' => 'token123', 'expires_in' => 3600]);

        $authHttpClient->method('request')->with('POST', $this->stringContains('/oauth/token'))->willReturn($authResponse);

        // Content http client mock: we assert this one gets the X-Correlation-ID header
        $contentHttpClient = $this->createMock(HttpClientInterface::class);

        $contentResponse = $this->createMock(ResponseInterface::class);
        $contentResponse->method('getStatusCode')->willReturn(200);
        $contentResponse->method('getContent')->willReturn('{"ok":true}');
        $contentResponse->method('getHeaders')->willReturn(['x-custom' => ['v']]);

        $contentHttpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'endpoint',
                $this->callback(function ($options) {
                    return isset($options['headers']['X-Correlation-ID']) && 'cid-1' === $options['headers']['X-Correlation-ID'];
                }),
            )
            ->willReturn($contentResponse);

        $cache = new ArrayAdapter();

        // Use a real ContentAuthenticator instance (not mocking final class)
        $authenticator = new ContentAuthenticator(
            $authHttpClient,
            $cache,
            'user', 'pass', 'id', 'secret',
        );

        $logger = $this->createMock(LoggerInterface::class);

        $client = new ContentClient($contentHttpClient, $authenticator, $logger, 'proj', false);

        $s365Response = $client->forward('GET', 'endpoint', [], 'cid-1');

        $this->assertSame('{"ok":true}', $s365Response->getContent());
    }
}
