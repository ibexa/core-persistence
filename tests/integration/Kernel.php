<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\CorePersistence;

use Ibexa\Bundle\CorePersistence\IbexaCorePersistenceBundle;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use Ibexa\Contracts\Test\Core\IbexaTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

final class Kernel extends IbexaTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new IbexaCorePersistenceBundle();
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
    }
}
