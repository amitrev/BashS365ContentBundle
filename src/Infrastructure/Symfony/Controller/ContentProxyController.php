<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Infrastructure\Symfony\Controller;

use Bash\S365ContentBundle\Domain\Exception\S365ContentException;
use Bash\S365ContentBundle\Domain\HttpClient\ContentClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

#[AsController]
final readonly class ContentProxyController
{
    private const STRIP_HEADERS = [
        'content-encoding' => 1,
        'transfer-encoding' => 1,
        'content-length' => 1,
    ];

    private const METHODS_WITHOUT_BODY = [
        'GET' => 1,
        'HEAD' => 1,
    ];

    public function __construct(
        private ContentClientInterface $contentClient,
    ) {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function __invoke(Request $request, string $endpoint): Response
    {
        if (str_starts_with($endpoint, 'oauth') || str_contains($endpoint, '..')) {
            throw new S365ContentException('Invalid or restricted endpoint');
        }

        $method = $request->getMethod();
        $headers = [
            'Content-Type' => $request->headers->get('Content-Type', 'application/json'),
        ];

        if ($correlationId = $request->headers->get('X-Correlation-ID')) {
            $headers['X-Correlation-ID'] = $correlationId;
        }

        $options = [
            'headers' => $headers,
            'query' => $request->query->all(),
        ];

        if (!isset(self::METHODS_WITHOUT_BODY[$method])) {
            $options['body'] = static fn () => $request->getContent(true);
        }

        $s365Response = $this->contentClient->forward(
            $method,
            $endpoint,
            $options,
        );

        return new Response(
            $s365Response->getContent(),
            $s365Response->getStatusCode(),
            $this->filterHeaders($s365Response->getHeaders()),
        );
    }

    /**
     * @param array<string, string[]> $headers
     *
     * @return array<string, string[]>
     */
    private function filterHeaders(array $headers): array
    {
        return array_diff_key($headers, self::STRIP_HEADERS);
    }
}
