<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Ibexa\CorePersistence\Gateway\DoctrineSchemaMetadataRegistry;
use LogicException;
use PHPUnit\Framework\TestCase;

final class DoctrineSchemaMetadataRegistryTest extends TestCase
{
    public function testGetMetadataForTable(): void
    {
        $registry = new DoctrineSchemaMetadataRegistry([]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Failed to find metadata for table "foo". '
            . 'Did you forget to tag your gateway with "ibexa.core.persistence.doctrine_gateway" tag?'
        );

        $registry->getMetadataForTable('foo');
    }
}
