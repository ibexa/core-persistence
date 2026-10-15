<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Ibexa\Contracts\CorePersistence\Exception\MappingException;
use Ibexa\Contracts\CorePersistence\Exception\MappingExceptionInterface;
use Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingExceptionInterface;

/**
 * @internal
 *
 * Contains metadata for converting values between database & PHP.
 *
 * Conceptually based on Doctrine's ClassMetadata, but also acts as type converter.
 *
 * @see ClassMetadata
 */
interface DoctrineSchemaMetadataInterface
{
    public function getConnection(): Connection;

    /**
     * @return class-string|null
     */
    public function getClassName(): ?string;

    /**
     * @return non-empty-string
     */
    public function getTableName(): string;

    /**
     * @throws RuntimeMappingExceptionInterface
     */
    public function getColumnType(string $column): Type;

    /**
     * @return array<string>
     */
    public function getColumns(): array;

    public function hasColumn(string $column): bool;

    /**
     * @throws RuntimeMappingExceptionInterface
     */
    public function getColumn(string $column): string;

    public function getInheritanceMetadataWithColumn(string $column): ?self;

    public function isInheritedColumn(string $column): bool;

    /**
     * Similarly to Doctrine\DBAL\Types\Type::convertToPHPValue, converts database representation to PHP
     * representation.
     *
     * @see Type::convertToPHPValue
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function convertToPHPValues(array $data): array;

    /**
     * Similarly to Doctrine\DBAL\Types\Type::convertToDatabaseValue, converts PHP representation to database
     * representation.
     *
     * @see Type::convertToDatabaseValue
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeMappingExceptionInterface
     */
    public function convertToDatabaseValues(array $data): array;

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, int>
     *
     * @throws RuntimeMappingExceptionInterface
     */
    public function getBindingTypesForData(array $data): array;

    /**
     * @throws MappingException
     */
    public function getIdentifierColumn(): string;

    /**
     * @throws Exception
     * @throws RuntimeMappingExceptionInterface
     */
    public function getBindingTypeForColumn(string $columnName): int;

    /**
     * @throws MappingExceptionInterface
     */
    public function setParentMetadata(self $parentMetadata): void;

    public function getParentMetadata(): ?self;

    public function getSubclassByDiscriminator(string $discriminator): self;

    /**
     * @return array<string, DoctrineSchemaMetadataInterface>
     */
    public function getSubclasses(): array;

    /**
     * @throws MappingExceptionInterface
     */
    public function addSubclass(
        string $discriminator,
        self $doctrineSchemaMetadata
    ): void;

    public function isInheritanceTypeJoined(): bool;

    /**
     * @throws MappingExceptionInterface
     */
    public function setTranslationSchemaMetadata(TranslationDoctrineSchemaMetadataInterface $translationMetadata): void;

    public function hasTranslationSchemaMetadata(): bool;

    /**
     * @throws RuntimeMappingExceptionInterface
     */
    public function getTranslationSchemaMetadata(): TranslationDoctrineSchemaMetadataInterface;

    public function isTranslatedColumn(string $column): bool;

    /**
     * @throws MappingExceptionInterface
     */
    public function addRelationship(DoctrineRelationshipInterface $relationship): void;

    /**
     * @return array<non-empty-string, DoctrineRelationshipInterface>
     */
    public function getRelationships(): array;

    /**
     * @throws RuntimeMappingExceptionInterface
     */
    public function getRelationshipByForeignProperty(string $foreignProperty): DoctrineRelationshipInterface;

    /**
     * @throws RuntimeMappingExceptionInterface
     */
    public function getRelationshipByForeignKeyColumn(string $foreignColumn): DoctrineRelationshipInterface;
}
