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
interface DoctrineSchemaMetadataRegistryInterface
{
    /**
     * @phpstan-return array<class-string|non-empty-string>
     */
    public function getAvailableMetadata(): array;

    /**
     * @phpstan-param non-empty-string|class-string $classOrTableName
     */
    public function getMetadata(string $classOrTableName): DoctrineSchemaMetadataInterface;

    /**
     * @param class-string $className
     */
    public function getTranslationMetadata(string $className): TranslationDoctrineSchemaMetadataInterface;

    /**
     * @param non-empty-string $tableName
     */
    public function getMetadataForTable(string $tableName): DoctrineSchemaMetadataInterface;

    /**
     * @param class-string $className
     */
    public function getMetadataForClass(string $className): DoctrineSchemaMetadataInterface;
}
