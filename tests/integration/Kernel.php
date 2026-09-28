<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\CorePersistence;

use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Ibexa\Bundle\CorePersistence\IbexaCorePersistenceBundle;
use Ibexa\Bundle\DoctrineMigrations\IbexaDoctrineMigrationsBundle;
use Ibexa\Bundle\Test\Core\IbexaTestCoreBundle;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use Ibexa\Contracts\Test\Core\IbexaTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class Kernel extends IbexaTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new IbexaTestCoreBundle();

        yield new IbexaCorePersistenceBundle();

        yield new DoctrineMigrationsBundle();
        yield new IbexaDoctrineMigrationsBundle();
    }

    protected static function getExposedServicesByClass(): iterable
    {
        yield from parent::getExposedServicesByClass();

        yield DoctrineSchemaMetadataRegistryInterface::class;
    }

    protected function loadServices(LoaderInterface $loader): void
    {
        parent::loadServices($loader);

        $loader->load(__DIR__ . '/Resources/services.yaml');

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('doctrine_migrations', [
                'enable_service_migrations' => true,
            ]);
        });
    }
}
