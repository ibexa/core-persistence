<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\Common\Collections\Expr\Comparison;
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
        $relationshipQuery = $this->strategy->handleRelationshipTypeQuery(
            $this->createDoctrineSchemaMetadata(),
            $this->createDoctrineRelationship(DoctrineRelationship::JOIN_TYPE_JOINED),
            'related_class_id_column',
            new Comparison(
                'related_class_id_column',
                Comparison::EQ,
                'value'
            ),
            new QueryBuilder($this->connection),
            []
        );

        $parameter = $relationshipQuery->getParameter();
        self::assertSame('related_class_id_column_0', $parameter->getName());
        self::assertSame(1, $parameter->getType());
        self::assertSame('value', $parameter->getValue());

        $queryBuilder = $relationshipQuery->getQueryBuilder();

        self::assertEmpty($queryBuilder->getQueryPart('select'));
        self::assertEmpty($queryBuilder->getQueryPart('from'));
        self::assertEmpty($queryBuilder->getQueryPart('where'));
        self::assertEmpty($queryBuilder->getQueryPart('join'));
    }
}
