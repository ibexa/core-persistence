<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingException;
use Ibexa\CorePersistence\Gateway\RelationshipTypeStrategyRegistry;
use Ibexa\Tests\CorePersistence\Stub\InvalidDoctrineRelationship;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\CorePersistence\Gateway\RelationshipTypeStrategyRegistry
 */
final class RelationshipTypeStrategyRegistryTest extends TestCase
{
    private RelationshipTypeStrategyRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new RelationshipTypeStrategyRegistry();
    }

    public function testHandleRelationshipTypeThrowsRuntimeMappingException(): void
    {
        $this->expectException(RuntimeMappingException::class);
        $this->expectExceptionMessage('Unhandled relationship metadata. Expected one of "Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship", "Ibexa\Contracts\CorePersistence\Gateway\DoctrineOneToManyRelationship". Received "Ibexa\Tests\CorePersistence\Stub\InvalidDoctrineRelationship"');

        $this->registry->handleRelationshipType(
            new QueryBuilder($this->createMock(Connection::class)),
            new InvalidDoctrineRelationship(),
            'root_table_alias',
            'from_table',
            'to_table'
        );
    }

    public function testHandleRelationshipTypeQueryThrowsRuntimeMappingException(): void
    {
        $this->expectException(RuntimeMappingException::class);
        $this->expectExceptionMessage('Unhandled relationship metadata. Expected one of "Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship", "Ibexa\Contracts\CorePersistence\Gateway\DoctrineOneToManyRelationship". Received "Ibexa\Tests\CorePersistence\Stub\InvalidDoctrineRelationship"');

        $this->registry->handleRelationshipTypeQuery(
            new InvalidDoctrineRelationship(),
            $this->createMock(QueryBuilder::class),
            'related_class_id_column',
            'related_class_id_column_0'
        );
    }
}
