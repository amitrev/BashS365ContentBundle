<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle\Domain\HttpClient;

use Bash\S365ContentBundle\Domain\Dto\S365Response;

interface ContentClientInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function forward(string $method, string $url, array $options = [], ?string $correlationId = null): S365Response;
}
