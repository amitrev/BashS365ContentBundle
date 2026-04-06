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

        $s365Response = $this->contentClient->forward(
            $request->getMethod(),
            $endpoint,
            [
                'body' => $request->getContent(),
                'headers' => [
                    'Content-Type' => $request->headers->get('Content-Type', 'application/json'),
                    'X-Correlation-ID' => $request->headers->get('X-Correlation-ID'),
                ],
                'query' => $request->query->all(),
            ],
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
        unset(
            $headers['content-encoding'],
            $headers['transfer-encoding'],
            $headers['content-length'],
        );

        return $headers;
    }
}
