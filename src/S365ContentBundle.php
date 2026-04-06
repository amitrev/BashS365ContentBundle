<?php

declare(strict_types=1);

namespace Bash\S365ContentBundle;

use Bash\S365ContentBundle\DependencyInjection\S365ContentExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class S365ContentBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new S365ContentExtension();
    }
}
