<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\CorePersistence\DependencyInjection;

use Ibexa\Contracts\CorePersistence\Gateway\GatewayInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class IbexaCorePersistenceExtension extends Extension
{
    public const TAG_DOCTRINE_GATEWAY = 'ibexa.core.persistence.doctrine_gateway';

    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');

        $container->registerForAutoconfiguration(GatewayInterface::class)
            ->addTag(self::TAG_DOCTRINE_GATEWAY);
    }
}
