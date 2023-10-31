<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\CorePersistence\Gateway;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingException;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use Ibexa\Contracts\Test\Core\IbexaKernelTestCase;
use Ibexa\CorePersistence\Gateway\ExpressionVisitor;

final class ExpressionVisitorTest extends IbexaKernelTestCase
{
    private ExpressionVisitor $expressionVisitor;

    protected function setUp(): void
    {
        $core = $this->getIbexaTestCore();
        $connection = $core->getDoctrineConnection();
        $registry = $core->getServiceByClassName(DoctrineSchemaMetadataRegistryInterface::class);
        $this->expressionVisitor = new ExpressionVisitor(
            new QueryBuilder($connection),
            $registry,
            'foo_table_name',
            'foo_table_alias',
        );
    }

    public function testWalkComparison(): void
    {
        $result = $this->expressionVisitor->dispatch(new Comparison('foo', '=', 'bar'));
        self::assertSame(
            'foo_table_alias.foo = :foo_0',
            $result
        );
    }

    public function testInvalidField(): void
    {
        $this->expectException(RuntimeMappingException::class);
        $this->expectExceptionMessage('foo_table_name table metadata does not contain non_existent_field column.');
        $this->expressionVisitor->dispatch(new Comparison('non_existent_field', '=', 'bar'));
    }

    public function testTraversingRelationships(): void
    {
        // Note: This assumes relationship tables are joined before being used.
        $expr = new Comparison(
            'relationship_1.relationship_2.relationship_2_foo',
            'IN',
            'bar',
        );
        $result = $this->expressionVisitor->dispatch($expr);
        self::assertSame('relationship_2_table_name.relationship_2_foo = :relationship_2_foo_0', $result);
    }

    public function testInvalidRelationshipTraversal(): void
    {
        $expr = new Comparison(
            'relationship_1.relationship_non_existent.relationship_2_foo',
            'IN',
            'bar',
        );
        $this->expectException(RuntimeMappingException::class);
        $message = '"relationship_non_existent" does not exist as a relationship for "Relationship1Class" class '
            . 'metadata. Available relationship property: "relationship_2"';
        $this->expectExceptionMessage($message);
        $this->expressionVisitor->dispatch($expr);
    }
}
