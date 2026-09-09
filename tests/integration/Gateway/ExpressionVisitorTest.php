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
use Ibexa\CorePersistence\Gateway\RelationshipTypeStrategyRegistry;

final class ExpressionVisitorTest extends IbexaKernelTestCase
{
    private ExpressionVisitor $expressionVisitor;

    private ExpressionVisitor $articleExpressionVisitor;

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
            new RelationshipTypeStrategyRegistry()
        );
        $this->articleExpressionVisitor = new ExpressionVisitor(
            new QueryBuilder($connection),
            $registry,
            'article',
            'article',
            new RelationshipTypeStrategyRegistry()
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

    /**
     * @dataProvider provideForTraversingRelationships
     */
    public function testTraversingRelationships(Comparison $expr, string $expectedResult): void
    {
        // Note: This assumes relationship tables are joined before being used.
        $result = $this->expressionVisitor->dispatch($expr);
        self::assertSame($expectedResult, $result);
    }

    /**
     * @return iterable<array{\Doctrine\Common\Collections\Expr\Comparison, non-empty-string}>
     */
    public static function provideForTraversingRelationships(): iterable
    {
        yield [
            new Comparison(
                'relationship_1.relationship_2.relationship_2_foo',
                'IN',
                'bar',
            ),
            'relationship_2_table_name.relationship_2_foo IN (:relationship_2_foo_0)',
        ];

        yield [
            new Comparison(
                'relationship_1.relationship_2.relationship_2_foo',
                '<',
                '2023-11-22 10:00:00',
            ),
            'relationship_2_table_name.relationship_2_foo < :relationship_2_foo_0',
        ];
    }

    /**
     * "id" exists on both the "article" table and the "article_translation" table (each has its
     * own primary key). Filtering by "id" must compare against the main table's primary key,
     * never against translation-row ids.
     */
    public function testSharedColumnTargetsMainTable(): void
    {
        $result = $this->articleExpressionVisitor->dispatch(new Comparison('id', '=', 1));

        self::assertSame('article.id = :id_0', $result);
    }

    /**
     * "name" exists only on the "article_translation" table, so filtering by it must keep being
     * resolved through the translation subquery.
     */
    public function testTranslationColumnTargetsTranslationTable(): void
    {
        $result = $this->articleExpressionVisitor->dispatch(new Comparison('name', '=', 'bar'));

        $expectedTranslationSubquery = 'SELECT translation.article_id FROM article_translation translation'
            . ' WHERE (translation.article_id = article.id) AND (translation.name = :name_0)';

        self::assertSame("article.id IN ({$expectedTranslationSubquery})", $result);
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
