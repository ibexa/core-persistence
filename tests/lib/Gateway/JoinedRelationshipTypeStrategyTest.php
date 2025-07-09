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

        $this->strategy->handleRelationshipType(
            $queryBuilder,
            $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_JOINED),
            'root_alias',
            'from_table',
            'to_table'
        );

        self::assertEmpty($queryBuilder->getQueryPart('select'));
        self::assertEmpty($queryBuilder->getQueryPart('from'));
        self::assertEmpty($queryBuilder->getQueryPart('where'));
        self::assertSame(
            [
                'from_table' => [
                    [
                        'joinType' => 'left',
                        'joinTable' => 'to_table',
                        'joinAlias' => 'to_table',
                        'joinCondition' => 'from_table.foreign_key_column = to_table.related_class_id_column',
                    ],
                ],
            ],
            $queryBuilder->getQueryPart('join')
        );
    }

    public function testHandleRelationshipTypeQuery(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $relationshipQuery = $this->strategy->handleRelationshipTypeQuery(
            $queryBuilder,
            'to_table.related_class_id_column_0',
            ':related_class_id_column_0'
        );

        self::assertEmpty($relationshipQuery->getQueryPart('select'));
        self::assertEmpty($relationshipQuery->getQueryPart('from'));
        self::assertEmpty($relationshipQuery->getQueryPart('where'));
        self::assertEmpty($relationshipQuery->getQueryPart('join'));
    }
}
