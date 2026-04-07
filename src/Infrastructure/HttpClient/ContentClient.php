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
    public function __construct(
        private HttpClientInterface $httpClient,
        private ContentAuthenticator $authenticator,
        #[Target('s365_content')] private LoggerInterface $logger,
        private string $project,
        private bool $disableCache,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function forward(string $method, string $url, array $options = [], ?string $correlationId = null): S365Response
    {
        $defaultOptions = [
            'auth_bearer' => $this->authenticator->getToken(),
            'headers' => [
                'Project' => $this->project,
                'Accept' => 'application/json',
            ],
        ];

        if ($this->disableCache) {
            $defaultOptions['headers']['X-SMP-CACHE-DISABLE'] = 'true';
        }

        if ($correlationId) {
            $defaultOptions['headers']['X-Correlation-ID'] = $correlationId;
        }

        $finalOptions = array_merge_recursive($defaultOptions, $options);

        try {
            $response = $this->httpClient->request($method, $url, $finalOptions);

            if (401 === $response->getStatusCode()) {
                $this->authenticator->forceRefreshToken();
                $finalOptions['auth_bearer'] = $this->authenticator->getToken();
                $response = $this->httpClient->request($method, $url, $finalOptions);
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
