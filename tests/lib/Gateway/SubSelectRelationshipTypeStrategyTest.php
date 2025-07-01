<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingException;
use Ibexa\CorePersistence\Gateway\SubSelectRelationshipTypeStrategy;

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
            $this->createDoctrineRelationship(),
            'root_alias',
            'from_table',
            'to_table'
        );

        self::assertSame(
            ['to_table.foreign_key_column'],
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
                        'joinCondition' => 'from_table.related_class_id_column = to_table.foreign_key_column',
                    ],
                ],
            ],
            $queryBuilder->getQueryPart('join')
        );
    }

    public function testHandleRelationshipTypeQueryThrowsRuntimeMappingException(): void
    {
        $this->expectException(RuntimeMappingException::class);
        $this->expectExceptionMessage('Query is not initialized.');

        $this->strategy->handleRelationshipTypeQuery(
            $this->createDoctrineSchemaMetadata(),
            $this->createDoctrineRelationship(),
            'related_class_id_column',
            new Comparison(
                'related_class_id_column',
                Comparison::EQ,
                'value'
            ),
            new QueryBuilder($this->connection),
            []
        );
    }

    public function testHandleRelationshipTypeQuery(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder
            ->select('test_alias.related_class_id_column')
            ->from('test_table', 'test_alias');

        $relationshipQuery = $this->strategy->handleRelationshipTypeQuery(
            $this->createDoctrineSchemaMetadata(),
            $this->createDoctrineRelationship(),
            'related_class_id_column',
            new Comparison(
                'related_class_id_column',
                Comparison::EQ,
                'value'
            ),
            $queryBuilder,
            []
        );

        $parameter = $relationshipQuery->getParameter();
        self::assertSame('related_class_id_column_0', $parameter->getName());
        self::assertSame(1, $parameter->getType());
        self::assertSame('value', $parameter->getValue());

        $queryBuilder = $relationshipQuery->getQueryBuilder();

        self::assertSame(
            ['test_alias.related_class_id_column'],
            $queryBuilder->getQueryPart('select')
        );
        self::assertSame(
            [
                [
                    'table' => 'test_table',
                    'alias' => 'test_alias',
                ],
            ],
            $queryBuilder->getQueryPart('from')
        );
        self::assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    'test_alias.related_class_id_column IN (:related_class_id_column_0)',
                ]
            ),
            $queryBuilder->getQueryPart('where')
        );

        self::assertEmpty($queryBuilder->getQueryPart('join'));
    }
}
