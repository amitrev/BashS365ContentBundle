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
    /** @var array<string, string> */
    private array $defaultHeaders;

    public function __construct(
        private HttpClientInterface $httpClient,
        private ContentAuthenticator $authenticator,
        #[Target('s365_content')] private LoggerInterface $logger,
        private string $project,
        private bool $disableCache,
    ) {
        $headers = [
            'Project' => $this->project,
            'Accept' => 'application/json',
        ];

        if ($this->disableCache) {
            $headers['X-SMP-CACHE-DISABLE'] = 'true';
        }

        $this->defaultHeaders = $headers;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function forward(string $method, string $url, array $options = [], ?string $correlationId = null): S365Response
    {
        $headers = $this->defaultHeaders;
        if ($correlationId) {
            $headers['X-Correlation-ID'] = $correlationId;
        }

        $options['headers'] = [...$headers, ...($options['headers'] ?? [])];
        $options['auth_bearer'] ??= $this->authenticator->getToken();

        try {
            $response = $this->httpClient->request($method, $url, $options);

            if (401 === $response->getStatusCode()) {
                $this->authenticator->forceRefreshToken();
                $options['auth_bearer'] = $this->authenticator->getToken();
                $response = $this->httpClient->request($method, $url, $options);
            }

            return new S365Response(
                $response->getContent(false),
                $response->getStatusCode(),
                $response->getHeaders(false),
            );
        } catch (\Throwable $e) {
            $this->logger->error('S365 API Transport Error', ['url' => $url, 'correlation_id' => $correlationId, 'error' => $e->getMessage()]);
            throw new S365CommunicationException('Transport error for '.$url, 0, $e);
        }
    }
}
