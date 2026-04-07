<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Tests\Integration;

use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentAuthenticator;
use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ContentClientIntegrationTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    public function test_full_auth_and_request_flow(): void
    {
        $apiUrl = 'https://api.test';

        $responses = [
            new MockResponse((string) json_encode(['access_token' => 'int-token', 'expires_in' => 3600], JSON_THROW_ON_ERROR)),
            new MockResponse('{"data":true}', ['http_code' => 200]),
        ];

        $httpClient = new MockHttpClient($responses, $apiUrl);

        $cache = new ArrayAdapter();
        $logger = $this->createMock(LoggerInterface::class);

        $authenticator = new ContentAuthenticator($httpClient, $cache, $apiUrl, 'u', 'p', 'id', 'secret');
        $client = new ContentClient($httpClient, $authenticator, $logger, $apiUrl, 'proj', false);

        $response = $client->forward('GET', 'articles');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('data', $response->getContent());

        $this->assertSame(2, $httpClient->getRequestsCount());
    }
}
