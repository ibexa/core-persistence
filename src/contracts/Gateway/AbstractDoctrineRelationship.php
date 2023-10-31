<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Gateway;

abstract class AbstractDoctrineRelationship implements DoctrineRelationshipInterface
{
    /** @var class-string */
    protected string $relationshipClass;

    /** @var non-empty-string */
    protected string $foreignProperty;

    /** @var non-empty-string */
    protected string $foreignKeyColumn;

    /** @var non-empty-string */
    protected string $relatedClassIdColumn;

    public function getRelationshipClass(): string
    {
        return $this->relationshipClass;
    }

    public function getRelatedClassIdColumn(): string
    {
        return $this->relatedClassIdColumn;
    }

    public function getForeignProperty(): string
    {
        return $this->foreignProperty;
    }

    public function getForeignKeyColumn(): string
    {
        return $this->foreignKeyColumn;
    }
}
