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
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use LogicException;
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

    private RelationshipTypeStrategyRegistryInterface $relationshipTypeStrategyRegistry;

    /**
     * @param non-empty-string $tableName
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        DoctrineSchemaMetadataRegistryInterface $registry,
        string $tableName,
        string $tableAlias,
        RelationshipTypeStrategyRegistryInterface $relationshipTypeStrategyRegistry
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->schemaMetadata = $registry->getMetadataForTable($tableName);
        $this->tableAlias = $tableAlias;
        $this->registry = $registry;
        $this->relationshipTypeStrategyRegistry = $relationshipTypeStrategyRegistry;
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
        $placeholder = $this->getPlaceholder($parameterName);
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
        $fromTable = $this->tableAlias;
        $toTable = null;
        $subQuery = null;

        do {
            [
                $foreignProperty,
                $column,
            ] = explode(self::RELATIONSHIP_DELIMITER, $column, 2);

            $relationship = $metadata->getRelationshipByForeignProperty($foreignProperty);
            $metadata = $this->registry->getMetadata($relationship->getRelationshipClass());
            $parentMetadata = $metadata->getParentMetadata();
            if (null !== $parentMetadata) {
                $fromTable = $parentMetadata->getTableName();
            } else {
                $fromTable = $toTable ?? $fromTable;
            }

            $toTable = $metadata->getTableName();

            if (DoctrineRelationship::JOIN_TYPE_SUB_SELECT === $relationship->getJoinType()) {
                $subQuery ??= $this->queryBuilder->getConnection()->createQueryBuilder();
            }

            $this->relationshipTypeStrategyRegistry->handleRelationshipType(
                $subQuery ?? $this->queryBuilder,
                $relationship,
                $this->tableAlias,
                $fromTable,
                $toTable
            );
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

        return $this->handleRelationshipQuery($metadata, $relationship, $column, $comparison, $subQuery);
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

    private function handleRelationshipQuery(
        DoctrineSchemaMetadataInterface $metadata,
        DoctrineRelationshipInterface $relationship,
        string $column,
        Comparison $comparison,
        ?QueryBuilder $subQuery
    ): string {
        $isSubSelectJoin = $relationship->getJoinType() === DoctrineRelationship::JOIN_TYPE_SUB_SELECT;
        if ($isSubSelectJoin) {
            if ($subQuery === null) {
                throw new LogicException(
                    'Sub-select query is not initialized.',
                );
            }
        }

        $parameterName = $column . '_' . count($this->parameters);
        $fullColumnName = $metadata->getTableName() . '.' . $column;
        $relationshipQuery = $this->relationshipTypeStrategyRegistry->handleRelationshipTypeQuery(
            $relationship,
            $subQuery ?? $this->queryBuilder,
            $fullColumnName,
            $this->getPlaceholder($parameterName)
        );

        if ($isSubSelectJoin) {
            return $this->handleSubSelectQuery(
                $relationship,
                $metadata,
                $comparison,
                $column,
                $parameterName,
                $relationshipQuery
            );
        }

        return $this->handleJoinQuery(
            $comparison,
            $metadata,
            $column,
            $parameterName,
            $fullColumnName,
            $relationshipQuery
        );
    }

    private function handleJoinQuery(
        Comparison $comparison,
        DoctrineSchemaMetadataInterface $relationshipMetadata,
        string $field,
        string $parameterName,
        string $fullColumnName,
        QueryBuilder $relationshipQuery
    ): string {
        $value = $this->walkValue($comparison->getValue());
        $type = $relationshipMetadata->getBindingTypeForColumn($field);

        if (is_array($value)) {
            $type += Connection::ARRAY_PARAM_OFFSET;
        }

        $parameter = new Parameter($parameterName, $value, $type);
        $placeholder = $this->getPlaceholder($parameterName);

        if (is_array($value)) {
            $this->parameters[] = $parameter;

            return $relationshipQuery->expr()->in(
                $fullColumnName,
                $placeholder
            );
        }

        return $this->handleComparison(
            $comparison,
            $parameter,
            $fullColumnName,
            $placeholder
        );
    }

    private function handleSubSelectQuery(
        DoctrineRelationshipInterface $relationship,
        DoctrineSchemaMetadataInterface $relationshipMetadata,
        Comparison $comparison,
        string $field,
        string $parameterName,
        QueryBuilder $relationshipQuery
    ): string {
        $value = $this->walkValue($comparison->getValue());
        $type = $relationshipMetadata->getBindingTypeForColumn($field);
        if (is_array($value)) {
            $type += Connection::ARRAY_PARAM_OFFSET;
        }

        $this->parameters[] = new Parameter($parameterName, $value, $type);

        return $this->expr()->in(
            $this->tableAlias . '.' . $relationship->getForeignKeyColumn(),
            $relationshipQuery->getSQL(),
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
            $this->relationshipTypeStrategyRegistry
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

    private function getPlaceholder(string $parameterName): string
    {
        return ':' . $parameterName;
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
