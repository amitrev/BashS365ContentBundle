<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Infrastructure\HttpClient;

use Bash\S365ContentBundle\Domain\Exception\S365AuthenticationContentException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ContentAuthenticator
{
    private string $cacheTokenKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $username,
        private string $password,
        private string $clientId,
        private string $clientSecret,
        private int $ttlCachedToken = 2592000,
        string $projectPrefix = 's365',
    ) {
        $this->cacheTokenKey = $projectPrefix.'_auth_token';
    }

    public function getToken(): string
    {
        try {
            return $this->cache->get($this->cacheTokenKey, function (ItemInterface $item) {
                $authData = $this->fetchNewToken();

                $expiresIn = (int) ($authData['expires_in'] ?? $this->ttlCachedToken);
                $item->expiresAfter(max(0, $expiresIn - 10));

                return $authData['access_token'];
            });
        } catch (\Throwable $e) {
            throw new S365AuthenticationContentException('Could not retrieve S365 token', 0, $e);
        }
    }

    /**
     * @return array{access_token: string, expires_in?: int}
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface
     */
    private function fetchNewToken(): array
    {
        $response = $this->httpClient->request('POST', '/oauth/token', [
            'body' => [
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $this->username,
                'password' => $this->password,
            ],
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new S365AuthenticationContentException('Invalid credentials or S365 API error');
        }

        /** @var array{access_token: string, expires_in?: int} $data */
        $data = $response->toArray();

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function forceRefreshToken(): void
    {
        $this->cache->delete($this->cacheTokenKey);
    }
}
