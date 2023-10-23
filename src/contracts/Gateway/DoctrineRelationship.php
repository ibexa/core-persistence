<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Gateway;

/**
 * @internal
 */
final class DoctrineRelationship extends AbstractDoctrineRelationship
{
    public const JOIN_TYPE_SUB_SELECT = 'subselect';

    public const JOIN_TYPE_JOINED = 'joined';

    /** @phpstan-var self::JOIN_TYPE_* */
    private string $joinType;

    /**
     * @phpstan-param class-string $relationshipClass
     * @phpstan-param non-empty-string $foreignProperty
     * @phpstan-param non-empty-string $foreignKeyColumn
     * @phpstan-param non-empty-string $relatedClassIdColumn
     * @phpstan-param self::JOIN_TYPE_* $joinType
     */
    public function __construct(
        string $relationshipClass,
        string $foreignProperty,
        string $foreignKeyColumn,
        string $relatedClassIdColumn,
        string $joinType = self::JOIN_TYPE_SUB_SELECT
    ) {
        $this->relationshipClass = $relationshipClass;
        $this->foreignProperty = $foreignProperty;
        $this->foreignKeyColumn = $foreignKeyColumn;
        $this->relatedClassIdColumn = $relatedClassIdColumn;
        $this->setJoinType($joinType);
    }

    /**
     * @phpstan-param self::JOIN_TYPE_* $joinType
     */
    public function setJoinType(string $joinType): void
    {
        $this->joinType = $joinType;
    }

    /**
     * @phpstan-return self::JOIN_TYPE_*
     */
    public function getJoinType(): string
    {
        return $this->joinType;
    }
}
