<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Infrastructure\HttpClient;

use Bash\S365ContentBundle\Domain\Exception\S365AuthenticationContentException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ContentAuthenticator
{
    private readonly string $cacheTokenKey;
    private readonly string $authSerializedBody;

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
        $credentialHash = \substr(\md5($username.$password.$clientId.$clientSecret), 0, 8);
        $this->cacheTokenKey = $projectPrefix.'_auth_token_v3_'.$credentialHash;
        $this->authSerializedBody = \http_build_query([
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $username,
            'password' => $password,
        ]);
    }

    public function getToken(): string
    {
        if (null !== $this->token && \time() < $this->expiresAt) {
            return $this->token;
        }

        try {
            $httpClient = $this->httpClient;
            $body = $this->authSerializedBody;
            $defaultTtl = $this->ttlCachedToken;

            /** @var array{token: string, expires_at: int} $cached */
            $cached = $this->cache->get($this->cacheTokenKey, static function (ItemInterface $item) use ($httpClient, $body, $defaultTtl) {
                $response = $httpClient->request('POST', '/oauth/token', [
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'body' => $body,
                ]);

                if (200 !== $response->getStatusCode()) {
                    throw new S365AuthenticationContentException('Invalid credentials or S365 API error');
                }

                /** @var array{access_token: string, expires_in?: int} $authData */
                $authData = $response->toArray();

                $expiresIn = (int) ($authData['expires_in'] ?? $defaultTtl);
                $ttl = \max(0, $expiresIn - 10);
                $expiresAt = \time() + $ttl;
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
     * @throws InvalidArgumentException
     */
    public function forceRefreshToken(): void
    {
        $this->token = null;
        $this->expiresAt = null;
        $this->cache->delete($this->cacheTokenKey);
    }
}
