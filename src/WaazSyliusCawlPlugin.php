<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Waaz\SyliusCawlPlugin\DependencyInjection\Compiler\PayumStoragePaymentAliaser;

final class WaazSyliusCawlPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PayumStoragePaymentAliaser());

        parent::build($container);
    }
}
