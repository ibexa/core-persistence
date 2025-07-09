<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
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

    public function testHandleRelationshipType(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);

        $this->strategy->handleRelationshipType(
            $queryBuilder,
            $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_SUB_SELECT),
            'root_alias',
            'from_table',
            'to_table'
        );

        self::assertSame(
            ['to_table.related_class_id_column'],
            $queryBuilder->getQueryPart('select')
        );
        self::assertSame(
            [
                [
                    'table' => 'to_table',
                    'alias' => null,
                ],
            ],
            $queryBuilder->getQueryPart('from')
        );
        self::assertEmpty($queryBuilder->getQueryPart('where'));
        self::assertSame(
            [
                'from_table' => [
                    [
                        'joinType' => 'inner',
                        'joinTable' => 'to_table',
                        'joinAlias' => 'to_table',
                        'joinCondition' => 'from_table.foreign_key_column = to_table.related_class_id_column',
                    ],
                ],
            ],
            $queryBuilder->getQueryPart('join')
        );
    }

    public function testHandleRelationshipTypeQueryThrowsRuntimeMappingException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Query is not initialized.');

        $this->strategy->handleRelationshipTypeQuery(
            $this->createMock(QueryBuilder::class),
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
            ['test_alias.related_class_id_column'],
            $relationshipQuery->getQueryPart('select')
        );
        self::assertSame(
            [
                [
                    'table' => 'test_table',
                    'alias' => 'test_alias',
                ],
            ],
            $relationshipQuery->getQueryPart('from')
        );
        self::assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    'test_alias.related_class_id_column IN (:related_class_id_column_0)',
                ]
            ),
            $relationshipQuery->getQueryPart('where')
        );

        self::assertEmpty($relationshipQuery->getQueryPart('join'));
    }
}
