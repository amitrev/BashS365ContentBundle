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

final class ContentAuthenticator
{
    private readonly string $cacheTokenKey;

    /** @var array{grant_type: string, client_id: string, client_secret: string, username: string, password: string} */
    private readonly array $authBody;

    private ?string $token = null;
    private ?int $expiresAt = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        string $username,
        string $password,
        string $clientId,
        string $clientSecret,
        private readonly int $ttlCachedToken = 2592000,
        string $projectPrefix = 's365',
    ) {
        $credentialHash = substr(md5($username.$password.$clientId.$clientSecret), 0, 8);
        $this->cacheTokenKey = $projectPrefix.'_auth_token_v3_'.$credentialHash;
        $this->authBody = [
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $username,
            'password' => $password,
        ];
    }

    public function getToken(): string
    {
        if (null !== $this->token && time() < $this->expiresAt) {
            return $this->token;
        }

        try {
            $now = time();
            /** @var array{token: string, expires_at: int} $cached */
            $cached = $this->cache->get($this->cacheTokenKey, function (ItemInterface $item) use ($now) {
                $authData = $this->fetchNewToken();

                $expiresIn = (int) ($authData['expires_in'] ?? $this->ttlCachedToken);
                $ttl = max(0, $expiresIn - 10);
                $expiresAt = $now + $ttl;
                $item->expiresAfter($ttl);

                return [
                    'token' => $authData['access_token'],
                    'expires_at' => $expiresAt,
                ];
            });

            $this->token = $cached['token'];
            $this->expiresAt = $cached['expires_at'];

            return $this->token;
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
            'body' => $this->authBody,
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
        $this->token = null;
        $this->expiresAt = null;
        $this->cache->delete($this->cacheTokenKey);
    }
}
