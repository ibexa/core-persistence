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
    /**
     * @param class-string $relationshipClass
     * @param non-empty-string $foreignProperty
     * @param non-empty-string $foreignKeyColumn
     * @param non-empty-string $relatedClassIdColumn
     */
    public function __construct(
        string $relationshipClass,
        string $foreignProperty,
        string $foreignKeyColumn,
        string $relatedClassIdColumn
    ) {
        $this->relationshipClass = $relationshipClass;
        $this->foreignProperty = $foreignProperty;
        $this->foreignKeyColumn = $foreignKeyColumn;
        $this->relatedClassIdColumn = $relatedClassIdColumn;
    }
}
