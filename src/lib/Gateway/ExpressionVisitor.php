<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor as BaseExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingException;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineOneToManyRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use RuntimeException;

/**
 * @internal
 *
 * Based on {@see \Doctrine\ORM\Query\QueryExpressionVisitor}.
 */
final class ExpressionVisitor extends BaseExpressionVisitor
{
    private const TRANSLATION_TABLE_ALIAS = 'translation';

    /**
     * Indicates that comparison should expand into a query against a table with relation to the current table.
     */
    public const RELATIONSHIP_DELIMITER = '.';

    private QueryBuilder $queryBuilder;

    private DoctrineSchemaMetadataInterface $schemaMetadata;

    private string $tableAlias;

    /** @var list<\Ibexa\CorePersistence\Gateway\Parameter> */
    private array $parameters = [];

    private DoctrineSchemaMetadataRegistryInterface $registry;

    /**
     * @param non-empty-string $tableName
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        DoctrineSchemaMetadataRegistryInterface $registry,
        string $tableName,
        string $tableAlias
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->schemaMetadata = $registry->getMetadataForTable($tableName);
        $this->tableAlias = $tableAlias;
        $this->registry = $registry;
    }

    /**
     * @return list<\Ibexa\CorePersistence\Gateway\Parameter>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function clearParameters(): void
    {
        $this->parameters = [];
    }

    /**
     * @throws \Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingExceptionInterface
     *
     * @return mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        $column = $comparison->getField();

        if ($this->containsRelationshipDelimiter($column)) {
            return $this->handleRelationshipComparison($column, $comparison);
        }

        if ($this->schemaMetadata->isTranslatedColumn($column)) {
            return $this->handleTranslation($comparison);
        }

        if (!$this->schemaMetadata->hasColumn($column) && !$this->schemaMetadata->isInheritedColumn($column)) {
            throw new RuntimeMappingException(sprintf(
                '%s table metadata does not contain %s column.',
                $this->schemaMetadata->getTableName(),
                $column,
            ));
        }

        $parameterName = $column . '_' . count($this->parameters);
        $placeholder = ':' . $parameterName;
        $value = $this->walkValue($comparison->getValue());
        $type = $this->schemaMetadata->getBindingTypeForColumn($column);
        if (is_array($value)) {
            $type += Connection::ARRAY_PARAM_OFFSET;
        }

        if ($this->isInheritedColumn($column)) {
            $inheritanceMetadata = $this->schemaMetadata->getInheritanceMetadataWithColumn($column);
            assert($inheritanceMetadata !== null);
            $fullColumnName = $inheritanceMetadata->getTableName() . '.' . $column;
        } else {
            $fullColumnName = $this->tableAlias . '.' . $column;
        }
        $parameter = new Parameter($parameterName, $value, $type);

        return $this->handleComparison($comparison, $parameter, $fullColumnName, $placeholder);
    }

    /**
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    public function walkCompositeExpression(CompositeExpression $expr): string
    {
        $expressionList = [];
        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch ($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return (string)$this->expr()->and(...$expressionList);

            case CompositeExpression::TYPE_OR:
                return (string)$this->expr()->or(...$expressionList);

            case 'NOT':
                return $this->queryBuilder->getConnection()->getDatabasePlatform()->getNotExpression($expressionList[0]);

            default:
                throw new RuntimeException('Unknown composite ' . $expr->getType());
        }
    }

    private function containsRelationshipDelimiter(string $column): bool
    {
        return str_contains($column, self::RELATIONSHIP_DELIMITER);
    }

    /**
     * @throws \Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingExceptionInterface
     */
    private function handleRelationshipComparison(string $column, Comparison $comparison): string
    {
        $metadata = $this->schemaMetadata;
        do {
            [
                $foreignProperty,
                $column,
            ] = explode(self::RELATIONSHIP_DELIMITER, $column, 2);

            $relationship = $metadata->getRelationshipByForeignProperty($foreignProperty);
            $metadata = $this->registry->getMetadata($relationship->getRelationshipClass());
        } while ($this->containsRelationshipDelimiter($column));

        if (!$metadata->hasColumn($column)) {
            throw new RuntimeMappingException(sprintf(
                '"%s" does not exist as available column on "%s" class schema metadata. '
                . 'Available columns: "%s". Available relationships: "%s"',
                $column,
                $metadata->getClassName(),
                implode('", "', $metadata->getColumns()),
                implode('", "', array_map(
                    static fn (DoctrineRelationshipInterface $relationship): string => $relationship->getForeignProperty(),
                    $metadata->getRelationships(),
                )),
            ));
        }

        $relationshipType = get_class($relationship);

        switch (true) {
            case $relationship->getJoinType() === DoctrineRelationship::JOIN_TYPE_SUB_SELECT:
                return $this->handleSubSelectQuery(
                    $metadata,
                    $relationship->getForeignKeyColumn(),
                    $column,
                    $comparison,
                );
            case $relationship->getJoinType() === DoctrineRelationship::JOIN_TYPE_JOINED:
                return $this->handleJoinQuery(
                    $metadata,
                    $column,
                    $comparison,
                );
            default:
                throw new RuntimeMappingException(sprintf(
                    'Unhandled relationship metadata. Expected one of "%s". Received "%s".',
                    implode('", "', [
                        DoctrineRelationship::class,
                        DoctrineOneToManyRelationship::class,
                    ]),
                    $relationshipType,
                ));
        }
    }

    private function escapeSpecialSQLValues(Parameter $parameter): string
    {
        $platform = $this->queryBuilder->getConnection()->getDatabasePlatform();

        return $platform->escapeStringForLike($parameter->getValue(), '\\');
    }

    private function expr(): ExpressionBuilder
    {
        return $this->queryBuilder->expr();
    }

    private function handleJoinQuery(
        DoctrineSchemaMetadataInterface $relationshipMetadata,
        string $field,
        Comparison $comparison
    ): string {
        $tableName = $relationshipMetadata->getTableName();
        $parameterName = $field . '_' . count($this->parameters);
        $placeholder = ':' . $parameterName;

        $value = $this->walkValue($comparison->getValue());
        $type = $relationshipMetadata->getBindingTypeForColumn($field);
        if (is_array($value)) {
            $type += Connection::ARRAY_PARAM_OFFSET;
        }

        $parameter = new Parameter($parameterName, $value, $type);

        if (is_array($value)) {
            $this->parameters[] = $parameter;

            return $this->expr()->in($tableName . '.' . $field, $placeholder);
        }

        return $this->handleComparison($comparison, $parameter, $tableName . '.' . $field, $placeholder);
    }

    private function handleSubSelectQuery(
        DoctrineSchemaMetadataInterface $relationshipMetadata,
        string $foreignField,
        string $field,
        Comparison $comparison
    ): string {
        $tableName = $relationshipMetadata->getTableName();
        $parameterName = $field . '_' . count($this->parameters);
        $placeholder = ':' . $parameterName;

        $subquery = $this->queryBuilder->getConnection()->createQueryBuilder();
        $subquery->from($tableName);
        $subquery->select($tableName . '.' . $relationshipMetadata->getIdentifierColumn());
        $subquery->where(
            $subquery->expr()->in(
                $tableName . '.' . $field,
                $placeholder,
            ),
        );

        $value = $this->walkValue($comparison->getValue());
        $type = $relationshipMetadata->getBindingTypeForColumn($field);
        if (is_array($value)) {
            $type += Connection::ARRAY_PARAM_OFFSET;
        }
        $parameter = new Parameter($parameterName, $value, $type);
        $this->parameters[] = $parameter;

        return $this->expr()->in(
            $this->tableAlias . '.' . $foreignField,
            $subquery->getSQL(),
        );
    }

    private function handleTranslation(Comparison $comparison): string
    {
        $translationMetadata = $this->schemaMetadata->getTranslationSchemaMetadata();
        $translationForeignKeyColumn = $translationMetadata->getTranslatableIdColumn();

        $subquery = $this->queryBuilder->getConnection()->createQueryBuilder();
        $subquery->select(self::TRANSLATION_TABLE_ALIAS . '.' . $translationForeignKeyColumn);
        $subquery->from($translationMetadata->getTableName(), self::TRANSLATION_TABLE_ALIAS);
        $subquery->where($subquery->expr()->eq(
            self::TRANSLATION_TABLE_ALIAS . '.' . $translationForeignKeyColumn,
            $this->tableAlias . '.' . $this->schemaMetadata->getIdentifierColumn(),
        ));

        $innerExpressionVisitor = new self(
            $subquery,
            $this->registry,
            $translationMetadata->getTableName(),
            self::TRANSLATION_TABLE_ALIAS,
        );
        $innerCondition = $innerExpressionVisitor->walkComparison($comparison);
        $subquery->andWhere($innerCondition);
        foreach ($innerExpressionVisitor->getParameters() as $parameter) {
            $this->parameters[] = $parameter;
        }

        return $this->queryBuilder->expr()->in(
            $this->tableAlias . '.' . $this->schemaMetadata->getIdentifierColumn(),
            $subquery->getSQL(),
        );
    }

    private function isInheritedColumn(string $column): bool
    {
        return $this->schemaMetadata->getIdentifierColumn() !== $column
            && $this->schemaMetadata->isInheritedColumn($column);
    }

    private function handleComparison(
        Comparison $comparison,
        Parameter $parameter,
        string $fullColumnName,
        string $placeholder
    ): string {
        switch ($comparison->getOperator()) {
            case Comparison::IN:
                $this->parameters[] = $parameter;

                return $this->expr()->in($fullColumnName, $placeholder);

            case Comparison::NIN:
                $this->parameters[] = $parameter;

                return $this->expr()->notIn($fullColumnName, $placeholder);

            case Comparison::EQ:
            case Comparison::IS:
                if ($this->walkValue($comparison->getValue()) === null) {
                    return $this->expr()->isNull($fullColumnName);
                }

                $this->parameters[] = $parameter;

                return $this->expr()->eq($fullColumnName, $placeholder);

            case Comparison::NEQ:
                if ($this->walkValue($comparison->getValue()) === null) {
                    return $this->expr()->isNotNull($fullColumnName);
                }

                $this->parameters[] = $parameter;

                return $this->expr()->neq($fullColumnName, $placeholder);

            case Comparison::CONTAINS:
                $parameter->setValue('%' . $this->escapeSpecialSQLValues($parameter) . '%');
                $this->parameters[] = $parameter;

                return $this->expr()->like($fullColumnName, $placeholder);

            case Comparison::STARTS_WITH:
                $parameter->setValue($this->escapeSpecialSQLValues($parameter) . '%');
                $this->parameters[] = $parameter;

                return $this->expr()->like($fullColumnName, $placeholder);

            case Comparison::ENDS_WITH:
                $parameter->setValue('%' . $this->escapeSpecialSQLValues($parameter));
                $this->parameters[] = $parameter;

                return $this->expr()->like($fullColumnName, $placeholder);

            case Comparison::GT:
            case Comparison::GTE:
            case Comparison::LT:
            case Comparison::LTE:
                $this->parameters[] = $parameter;

                return $this->expr()->comparison($fullColumnName, $comparison->getOperator(), $placeholder);

            default:
                throw new RuntimeException('Unknown comparison operator: ' . $comparison->getOperator());
        }
    }
}
