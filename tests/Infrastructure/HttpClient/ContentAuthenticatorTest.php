<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Tests\Infrastructure\HttpClient;

use Bash\S365ContentBundle\Domain\Exception\S365AuthenticationContentException;
use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ContentAuthenticatorTest extends TestCase
{
    public function test_get_token_caches_successfully(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'access_token' => 'test_token',
            'expires_in' => 3600,
        ]);

        $httpClient->expects($this->once())
        ->method('request')
            ->willReturn($response);

        $authenticator = new ContentAuthenticator(
            $httpClient,
            new ArrayAdapter(),
            'https://api.test', 'user', 'pass', 'id', 'secret',
        );

        $token1 = $authenticator->getToken();
        $token2 = $authenticator->getToken();

        $this->assertEquals('test_token', $token1);
        $this->assertEquals($token1, $token2);
    }

    public function test_throws_exception_on_auth_failure(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(401);

        $httpClient->method('request')->willReturn($response);

        $authenticator = new ContentAuthenticator(
            $httpClient, new ArrayAdapter(), 'url', 'u', 'p', 'id', 's',
        );

        $this->expectException(S365AuthenticationContentException::class);
        $authenticator->getToken();
    }
}
