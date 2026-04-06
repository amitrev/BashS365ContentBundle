<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Domain\Dto;

final readonly class S365Response
{
    /**
     * @param array<string, string[]> $headers
     */
    public function __construct(
        private string $content,
        private int $statusCode,
        private array $headers = [],
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    public function toArray(): array
    {
        return json_decode($this->content, true, 512, JSON_THROW_ON_ERROR);
    }
}
