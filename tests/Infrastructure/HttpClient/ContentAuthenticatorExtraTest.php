<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Tests\Infrastructure\HttpClient;

use Bash\S365ContentBundle\Domain\Exception\S365AuthenticationContentException;
use Bash\S365ContentBundle\Infrastructure\HttpClient\ContentAuthenticator;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ContentAuthenticatorExtraTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function test_force_refresh_token_triggers_refetch(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $response1 = $this->createMock(ResponseInterface::class);
        $response1->method('getStatusCode')->willReturn(200);
        $response1->method('toArray')->willReturn([
            'access_token' => 'first_token',
            'expires_in' => 3600,
        ]);

        $response2 = $this->createMock(ResponseInterface::class);
        $response2->method('getStatusCode')->willReturn(200);
        $response2->method('toArray')->willReturn([
            'access_token' => 'second_token',
            'expires_in' => 3600,
        ]);

        $httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($response1, $response2);

        $cache = new ArrayAdapter();

        $authenticator = new ContentAuthenticator(
            $httpClient,
            $cache,
            'https://api.test', 'user', 'pass', 'id', 'secret',
        );

        $token1 = $authenticator->getToken();

        // force refresh and expect new token
        $authenticator->forceRefreshToken();
        $token2 = $authenticator->getToken();

        $this->assertSame('first_token', $token1);
        $this->assertSame('second_token', $token2);
    }

    public function test_token_refresh_before_expiry_calls_fetch_again(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $response1 = $this->createMock(ResponseInterface::class);
        $response1->method('getStatusCode')->willReturn(200);
        // small expires_in to cause immediate expiry in cache
        $response1->method('toArray')->willReturn([
            'access_token' => 'short_lived',
            'expires_in' => 1,
        ]);

        $response2 = $this->createMock(ResponseInterface::class);
        $response2->method('getStatusCode')->willReturn(200);
        $response2->method('toArray')->willReturn([
            'access_token' => 'refreshed_token',
            'expires_in' => 3600,
        ]);

        $httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($response1, $response2);

        $cache = new ArrayAdapter();

        $authenticator = new ContentAuthenticator(
            $httpClient,
            $cache,
            'https://api.test', 'user', 'pass', 'id', 'secret',
        );

        $token1 = $authenticator->getToken();
        // Because expires_in is small and the implementation subtracts 10 seconds,
        // the token will be considered expired and a subsequent call should fetch again.
        $token2 = $authenticator->getToken();

        $this->assertSame('short_lived', $token1);
        $this->assertSame('refreshed_token', $token2);
    }

    public function test_cache_get_exceptions_are_wrapped(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $cache = $this->createMock(\Symfony\Contracts\Cache\CacheInterface::class);
        $cache->method('get')->will($this->throwException(new \RuntimeException('cache failure')));

        $authenticator = new ContentAuthenticator(
            $httpClient,
            $cache,
            'https://api.test', 'user', 'pass', 'id', 'secret',
        );

        $this->expectException(S365AuthenticationContentException::class);

        $authenticator->getToken();
    }
}
