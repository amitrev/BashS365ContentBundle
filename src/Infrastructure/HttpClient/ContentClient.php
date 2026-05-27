<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Infrastructure\HttpClient;

use Bash\S365ContentBundle\Domain\Dto\S365Response;
use Bash\S365ContentBundle\Domain\Exception\S365CommunicationException;
use Bash\S365ContentBundle\Domain\HttpClient\ContentClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ContentClient implements ContentClientInterface
{
    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private ContentAuthenticator $authenticator,
        #[Target('s365_content')] private LoggerInterface $logger,
        string $project,
        bool $disableCache,
    ) {
        $headers = [
            'Project' => $project,
            'Accept' => 'application/json',
        ];

        if ($disableCache) {
            $headers['X-SMP-CACHE-DISABLE'] = 'true';
        }

        $this->httpClient = $httpClient->withOptions(['headers' => $headers]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function forward(string $method, string $url, array $options = [], ?string $correlationId = null): S365Response
    {
        $correlationId ??= $options['headers']['X-Correlation-ID'] ?? null;

        if (null !== $correlationId) {
            $options['headers']['X-Correlation-ID'] = $correlationId;
        }

        $options['auth_bearer'] ??= $this->authenticator->getToken();

        try {
            $response = $this->httpClient->request($method, $url, $options);

            if (401 === $response->getStatusCode()) {
                $this->authenticator->forceRefreshToken();
                $options['auth_bearer'] = $this->authenticator->getToken();
                $response = $this->httpClient->request($method, $url, $options);
            }

            return new S365Response(
                content: $response->getContent(false),
                statusCode: $response->getStatusCode(),
                headers: $response->getHeaders(false),
            );
        } catch (\Throwable $e) {
            $this->logger->error('S365 API Transport Error', [
                'url' => $url,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            throw new S365CommunicationException('Transport error for '.$url, 0, $e);
        }
    }
}
