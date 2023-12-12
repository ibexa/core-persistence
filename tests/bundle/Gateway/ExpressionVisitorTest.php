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
use Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingExceptionInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineOneToManyRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use Ibexa\CorePersistence\Gateway\ExpressionVisitor;
use Ibexa\CorePersistence\Gateway\Parameter;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\TestCase;

final class ExpressionVisitorTest extends TestCase
{
    private ExpressionVisitor $expressionVisitor;

    /** @var \Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private DoctrineSchemaMetadataRegistryInterface $registry;

    /** @var \Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface&\PHPUnit\Framework\MockObject\MockObject */
    private DoctrineSchemaMetadataInterface $schemaMetadata;

    private QueryBuilder $queryBuilder;

    /** @var \Doctrine\DBAL\Connection&\PHPUnit\Framework\MockObject\MockObject */
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->connection->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($this->connection));

        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMockForAbstractClass();

        $this->connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->schemaMetadata = $this->createMock(DoctrineSchemaMetadataInterface::class);

        $this->registry = $this->createMock(DoctrineSchemaMetadataRegistryInterface::class);
        $this->registry->method('getMetadataForTable')
            ->with('table_name')
            ->willReturn($this->schemaMetadata);

        $this->queryBuilder = new QueryBuilder($this->connection);
        $this->expressionVisitor = new ExpressionVisitor(
            $this->queryBuilder,
            $this->registry,
            'table_name',
            'table_alias',
        );
    }

    public function testWalkComparison(): void
    {
        $this->configureFieldInMetadata($this->schemaMetadata, ['field']);

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
        $this->configureFieldInMetadata($this->schemaMetadata, ['field']);

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
        $this->configureFieldInMetadata($this->schemaMetadata, ['field', 'field_2']);

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

    public function testFieldNotFound(): void
    {
        $this->expectException(RuntimeMappingExceptionInterface::class);
        $this->expressionVisitor->dispatch(new Comparison('field', '=', 'value'));
    }

    public function testFieldFromMissingRelationship(): void
    {
        /** @var \Exception $exception */
        $exception = $this->createMock(RuntimeMappingExceptionInterface::class);
        $this->schemaMetadata
            ->expects(self::once())
            ->method('getRelationshipByForeignProperty')
            ->with('relationship_1')
            ->willThrowException($exception);

        $this->expectExceptionObject($exception);
        $this->expressionVisitor->dispatch(new Comparison('relationship_1.field', '=', 'value'));
    }

    public function testFieldUnknownRelationshipType(): void
    {
        /** @var class-string $relationshipClass pretend it's a class-string */
        $relationshipClass = 'relationship_class';

        $doctrineRelationship = $this->createMock(DoctrineRelationshipInterface::class);
        $doctrineRelationship->method('getRelationshipClass')->willReturn($relationshipClass);
        $this->schemaMetadata
            ->expects(self::once())
            ->method('getRelationshipByForeignProperty')
            ->with('relationship_1')
            ->willReturn($doctrineRelationship);

        $relationshipMetadata = $this->createRelationshipSchemaMetadata();

        $this->registry
            ->expects(self::once())
            ->method('getMetadata')
            ->with(self::identicalTo($relationshipClass))
            ->willReturn($relationshipMetadata);

        $this->expectException(RuntimeMappingExceptionInterface::class);
        $this->expectExceptionMessage(
            'Unhandled relationship metadata. Expected one of '
            . '"Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship", '
            . '"Ibexa\Contracts\CorePersistence\Gateway\DoctrineOneToManyRelationship". '
            . 'Received "' . get_class($doctrineRelationship) . '"'
        );
        $this->expressionVisitor->dispatch(new Comparison('relationship_1.field', '=', 'value'));
    }

    public function testFieldFromSubSelectRelationship(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturnCallback(fn () => new QueryBuilder($this->connection));

        /** @var class-string $relationshipClass pretend it's a class-string */
        $relationshipClass = 'relationship_class';
        $doctrineRelationship = new DoctrineRelationship(
            $relationshipClass,
            'relationship_1',
            'relationship_id',
            'id',
        );

        $this->schemaMetadata
            ->expects(self::once())
            ->method('getRelationshipByForeignProperty')
            ->with('relationship_1')
            ->willReturn($doctrineRelationship);

        $relationshipMetadata = $this->createRelationshipSchemaMetadata();

        $this->registry
            ->expects(self::once())
            ->method('getMetadata')
            ->with($relationshipClass)
            ->willReturn($relationshipMetadata);

        $result = $this->expressionVisitor->dispatch(new Comparison('relationship_1.field', '=', 'value'));
        self::assertSame(
            'table_alias.relationship_id IN (SELECT relationship_table_name.id FROM relationship_table_name '
            . 'WHERE relationship_table_name.field IN (:field_0))',
            $result,
        );
    }

    /**
     * @dataProvider provideForFieldFromInheritedRelationship
     *
     * @phpstan-param array<\Ibexa\CorePersistence\Gateway\Parameter> $parameters
     */
    public function testFieldFromInheritedRelationship(
        Comparison $comparison,
        string $expectedResult,
        array $parameters
    ): void {
        /** @var class-string $relationshipClass pretend it's a class-string */
        $relationshipClass = 'relationship_class';
        $doctrineRelationship = new DoctrineOneToManyRelationship(
            $relationshipClass,
            'relationship_1',
            'relationship_id',
        );

        $this->schemaMetadata
            ->expects(self::once())
            ->method('getRelationshipByForeignProperty')
            ->with('relationship_1')
            ->willReturn($doctrineRelationship);

        $relationshipMetadata = $this->createRelationshipSchemaMetadata();

        $this->registry
            ->expects(self::once())
            ->method('getMetadata')
            ->with($relationshipClass)
            ->willReturn($relationshipMetadata);

        $result = $this->expressionVisitor->dispatch($comparison);
        self::assertSame(
            $expectedResult,
            $result,
        );

        self::assertEquals($parameters, $this->expressionVisitor->getParameters());
    }

    /**
     * @return iterable<array{
     *     \Doctrine\Common\Collections\Expr\Comparison,
     *     non-empty-string,
     *     array<\Ibexa\CorePersistence\Gateway\Parameter>,
     * }>
     */
    public static function provideForFieldFromInheritedRelationship(): iterable
    {
        yield [
            new Comparison('relationship_1.field', '=', 'value'),
            'relationship_table_name.field = :field_0',
            [new Parameter('field_0', 'value', 0)],
        ];

        yield [
            new Comparison('relationship_1.field', 'IN', ['value', 'value_2']),
            'relationship_table_name.field IN (:field_0)',
            [new Parameter('field_0', ['value', 'value_2'], 100)],
        ];

        yield [
            new Comparison('relationship_1.field', '=', ['value', 'value_2']),
            'relationship_table_name.field IN (:field_0)',
            [new Parameter('field_0', ['value', 'value_2'], 100)],
        ];

        yield [
            new Comparison('relationship_1.field', 'STARTS_WITH', 'value'),
            'relationship_table_name.field LIKE :field_0',
            [new Parameter('field_0', 'value%', 0)],
        ];

        yield [
            new Comparison('relationship_1.field', 'ENDS_WITH', 'value'),
            'relationship_table_name.field LIKE :field_0',
            [new Parameter('field_0', '%value', 0)],
        ];

        yield [
            new Comparison('relationship_1.field', 'CONTAINS', 'value'),
            'relationship_table_name.field LIKE :field_0',
            [new Parameter('field_0', '%value%', 0)],
        ];
    }

    public function testQueryThroughMultipleRelationships(): void
    {
        /** @var class-string $relationshipClass pretend it's a class-string */
        $relationshipClass = 'relationship_1_class';
        $doctrineRelationship1 = new DoctrineRelationship(
            $relationshipClass,
            'relationship_1',
            'relationship_1_id',
            'id',
            DoctrineRelationship::JOIN_TYPE_JOINED,
        );

        $this->schemaMetadata
            ->expects(self::once())
            ->method('getRelationshipByForeignProperty')
            ->with('relationship_1')
            ->willReturn($doctrineRelationship1);

        $relationshipMetadata1 = $this->createRelationshipSchemaMetadata();

        /** @var class-string $relationshipClass pretend it's a class-string */
        $relationshipClass = 'relationship_2_class';
        $doctrineRelationship2 = new DoctrineRelationship(
            $relationshipClass,
            'relationship_2',
            'relationship_2_id',
            'id',
            DoctrineRelationship::JOIN_TYPE_JOINED,
        );

        $relationshipMetadata2 = $this->createRelationshipSchemaMetadata('relationship_2_table_name');

        $relationshipMetadata1
            ->expects(self::once())
            ->method('getRelationshipByForeignProperty')
            ->with('relationship_2')
            ->willReturn($doctrineRelationship2);

        $this->registry
            ->expects(self::exactly(2))
            ->method('getMetadata')
            ->with(
                self::logicalOr(
                    self::identicalTo('relationship_1_class'),
                    self::identicalTo('relationship_2_class'),
                ),
            )
            ->willReturnCallback(static function (string $class) use ($relationshipMetadata1, $relationshipMetadata2): DoctrineSchemaMetadataInterface {
                if ($class === 'relationship_1_class') {
                    return $relationshipMetadata1;
                }

                if ($class === 'relationship_2_class') {
                    return $relationshipMetadata2;
                }

                self::fail('Invalid class passed to "getMetadata"');
            });

        $result = $this->expressionVisitor->dispatch(new Comparison('relationship_1.relationship_2.field', '=', 'value'));
        self::assertSame(
            'relationship_2_table_name.field = :field_0',
            $result,
        );
    }

    public function testFieldFromSubclass(): void
    {
        $this->schemaMetadata
            ->expects(self::exactly(2))
            ->method('isInheritedColumn')
            ->with('inherited_field')
            ->willReturn(true);

        $inheritanceMetadata = $this->createMock(DoctrineSchemaMetadataInterface::class);
        $this->schemaMetadata
            ->expects(self::once())
            ->method('getInheritanceMetadataWithColumn')
            ->with('inherited_field')
            ->willReturn($inheritanceMetadata);

        $inheritanceMetadata
            ->expects(self::once())
            ->method('getTableName')
            ->willReturn('inheritance_table');

        $result = $this->expressionVisitor->dispatch(new Comparison('inherited_field', '=', 'value'));
        self::assertSame(
            'inheritance_table.inherited_field = :inherited_field_0',
            $result,
        );
    }

    /**
     * @return \Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRelationshipSchemaMetadata(string $tableName = 'relationship_table_name'): DoctrineSchemaMetadataInterface
    {
        $relationshipMetadata = $this->createMock(DoctrineSchemaMetadataInterface::class);
        $relationshipMetadata
            ->method('hasColumn')
            ->with('field')
            ->willReturn(true);

        $relationshipMetadata
            ->method('getTableName')
            ->willReturn($tableName);

        $relationshipMetadata
            ->method('getIdentifierColumn')
            ->willReturn('id');

        return $relationshipMetadata;
    }

    /**
     * @param \Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface&\PHPUnit\Framework\MockObject\MockObject $metadata
     * @param array<string> $fields
     */
    private function configureFieldInMetadata(DoctrineSchemaMetadataInterface $metadata, array $fields): void
    {
        $metadata
            ->expects(self::atLeastOnce())
            ->method('hasColumn')
            ->with(self::logicalOr(
                ...array_map(static fn (string $fieldName): IsIdentical => self::identicalTo($fieldName), $fields),
            ))
            ->willReturn(true);
    }
}
