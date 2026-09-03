<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship;
use Ibexa\CorePersistence\Gateway\JoinedRelationshipTypeStrategy;

/**
 * @covers \Ibexa\CorePersistence\Gateway\JoinedRelationshipTypeStrategy
 */
final class JoinedRelationshipTypeStrategyTest extends BaseRelationshipTypeStrategyTestCase
{
    private JoinedRelationshipTypeStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new JoinedRelationshipTypeStrategy();
    }

    public function testHandleRelationshipType(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->select('from_table.id')->from('from_table');

        $this->strategy->handleRelationshipType(
            $queryBuilder,
            $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_JOINED),
            'root_alias',
            'from_table',
            'to_table'
        );

        self::assertSame(
            'SELECT from_table.id FROM from_table'
            . ' LEFT JOIN to_table to_table'
            . ' ON from_table.foreign_key_column = to_table.related_class_id_column',
            $queryBuilder->getSQL()
        );
    }

    public function testHandleRelationshipTypeIsIdempotentForTheSameTable(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->select('from_table.id')->from('from_table');

        $relationship = $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_JOINED);
        $this->strategy->handleRelationshipType($queryBuilder, $relationship, 'root_alias', 'from_table', 'to_table');
        $sqlAfterFirstJoin = $queryBuilder->getSQL();
        $this->strategy->handleRelationshipType($queryBuilder, $relationship, 'root_alias', 'from_table', 'to_table');

        self::assertSame($sqlAfterFirstJoin, $queryBuilder->getSQL());
    }

    /**
     * A gateway may have joined the relationship's table itself before criteria are converted, which
     * is invisible to anything but the query builder. Joining it a second time makes the query
     * unbuildable, so the alias has to be recognised as taken no matter who took it.
     */
    public function testHandleRelationshipTypeSkipsATableTheCallerAlreadyJoined(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder
            ->select('from_table.id')
            ->from('from_table')
            ->leftJoin('from_table', 'to_table', 'to_table', 'from_table.id = to_table.from_table_id');

        $sqlBeforeStrategy = $queryBuilder->getSQL();

        $this->strategy->handleRelationshipType(
            $queryBuilder,
            $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_JOINED),
            'root_alias',
            'from_table',
            'to_table'
        );

        self::assertSame($sqlBeforeStrategy, $queryBuilder->getSQL());
    }

    /**
     * Each query gets its own builder, so a table joined into one must not be remembered as joined
     * for the next.
     */
    public function testHandleRelationshipTypeTracksEachQueryBuilderSeparately(): void
    {
        $relationship = $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_JOINED);

        $first = new QueryBuilder($this->connection);
        $first->select('from_table.id')->from('from_table');
        $this->strategy->handleRelationshipType($first, $relationship, 'root_alias', 'from_table', 'to_table');

        $second = new QueryBuilder($this->connection);
        $second->select('from_table.id')->from('from_table');
        $this->strategy->handleRelationshipType($second, $relationship, 'root_alias', 'from_table', 'to_table');

        self::assertStringContainsString('LEFT JOIN to_table to_table', $second->getSQL());
    }

    public function testHandleRelationshipTypeQueryLeavesTheQueryUntouched(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->select('from_table.id')->from('from_table');

        $relationshipQuery = $this->strategy->handleRelationshipTypeQuery(
            $queryBuilder,
            'to_table.related_class_id_column_0',
            ':related_class_id_column_0'
        );

        self::assertSame('SELECT from_table.id FROM from_table', $relationshipQuery->getSQL());
    }
}
