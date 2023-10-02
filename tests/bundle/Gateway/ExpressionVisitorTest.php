<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\CorePersistence\Gateway;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use Ibexa\CorePersistence\Gateway\ExpressionVisitor;
use Ibexa\CorePersistence\Gateway\Parameter;
use PHPUnit\Framework\TestCase;

final class ExpressionVisitorTest extends TestCase
{
    private ExpressionVisitor $expressionVisitor;

    protected function setUp(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($connection));

        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMockForAbstractClass();

        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->expressionVisitor = new ExpressionVisitor(
            new QueryBuilder($connection),
            $this->createMock(DoctrineSchemaMetadataRegistryInterface::class),
            'table_name',
            'table_alias',
        );
    }

    public function testWalkComparison(): void
    {
        $result = $this->expressionVisitor->dispatch(new Comparison('field', '=', 'value'));

        self::assertSame('table_alias.field = :field_0', $result);
        self::assertEquals([
            new Parameter(
                'field_0',
                'value',
                0,
            ),
        ], $this->expressionVisitor->getParameters());
    }

    public function testLogicalNot(): void
    {
        $result = $this->expressionVisitor->dispatch(
            new CompositeExpression('NOT', [new Comparison('field', '=', 'value')]),
        );

        self::assertSame('NOT(table_alias.field = :field_0)', $result);
        self::assertEquals([
            new Parameter(
                'field_0',
                'value',
                0,
            ),
        ], $this->expressionVisitor->getParameters());
    }

    public function testLogicalAnd(): void
    {
        $result = $this->expressionVisitor->dispatch(
            new CompositeExpression('AND', [
                new Comparison('field', '=', 'value'),
                new Comparison('field_2', 'IN', 'value_2'),
            ]),
        );

        self::assertSame(
            '(table_alias.field = :field_0) AND (table_alias.field_2 IN (:field_2_1))',
            $result,
        );

        self::assertEquals([
            new Parameter(
                'field_0',
                'value',
                0,
            ),
            new Parameter(
                'field_2_1',
                'value_2',
                0,
            ),
        ], $this->expressionVisitor->getParameters());
    }
}
