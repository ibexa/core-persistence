<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship;
use Ibexa\CorePersistence\Gateway\SubSelectRelationshipTypeStrategy;
use LogicException;

/**
 * @covers \Ibexa\CorePersistence\Gateway\SubSelectRelationshipTypeStrategy
 */
final class SubSelectRelationshipTypeStrategyTest extends BaseRelationshipTypeStrategyTestCase
{
    private SubSelectRelationshipTypeStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new SubSelectRelationshipTypeStrategy();
    }

    public function testHandleRelationshipTypeInitialisesAnEmptyQuery(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);

        $this->strategy->handleRelationshipType(
            $queryBuilder,
            $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_SUB_SELECT),
            'to_table',
            'to_table',
            'to_table'
        );

        self::assertSame(
            'SELECT to_table.related_class_id_column FROM to_table',
            $queryBuilder->getSQL()
        );
    }

    public function testHandleRelationshipTypeJoinsWhenFromTableIsNotTheRootAlias(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->select('from_table.id')->from('from_table');

        $this->strategy->handleRelationshipType(
            $queryBuilder,
            $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_SUB_SELECT),
            'root_alias',
            'from_table',
            'to_table'
        );

        self::assertSame(
            'SELECT from_table.id FROM from_table'
            . ' INNER JOIN to_table to_table'
            . ' ON from_table.foreign_key_column = to_table.related_class_id_column',
            $queryBuilder->getSQL()
        );
    }

    public function testHandleRelationshipTypeQueryThrowsForAnUninitialisedQuery(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Query is not initialized.');

        $this->strategy->handleRelationshipTypeQuery(
            new QueryBuilder($this->connection),
            'alias.related_class_id_column',
            ':alias.related_class_id_column_0'
        );
    }

    public function testHandleRelationshipTypeQuery(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder
            ->select('test_alias.related_class_id_column')
            ->from('test_table', 'test_alias');

        $relationshipQuery = $this->strategy->handleRelationshipTypeQuery(
            $queryBuilder,
            'test_alias.related_class_id_column',
            ':related_class_id_column_0'
        );

        self::assertSame(
            'SELECT test_alias.related_class_id_column FROM test_table test_alias'
            . ' WHERE test_alias.related_class_id_column IN (:related_class_id_column_0)',
            $relationshipQuery->getSQL()
        );
    }
}
