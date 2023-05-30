<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Ibexa\Bundle\CorePersistence\DependencyInjection\IbexaCorePersistenceExtension;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use Ibexa\Contracts\CorePersistence\Gateway\TranslationDoctrineSchemaMetadataInterface;
use LogicException;

final class DoctrineSchemaMetadataRegistry implements DoctrineSchemaMetadataRegistryInterface
{
    /** @var array<class-string, \Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface> */
    private array $metadata;

    /** @var array<class-string, \Ibexa\Contracts\CorePersistence\Gateway\TranslationDoctrineSchemaMetadataInterface> */
    private array $translationMetadata;

    /** @var array<non-empty-string, \Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface> */
    private array $tableToMetadata;

    /**
     * @var iterable<\Ibexa\Contracts\CorePersistence\Gateway\GatewayInterface<array<mixed>>>
     */
    private iterable $gateways;

    private bool $initialized = false;

    /**
     * @param iterable<\Ibexa\Contracts\CorePersistence\Gateway\GatewayInterface<array<mixed>>> $gateways
     */
    public function __construct(iterable $gateways)
    {
        $this->gateways = $gateways;
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        foreach ($this->gateways as $gateway) {
            $metadata = $gateway->getMetadata();

            $tableName = $metadata->getTableName();
            if (isset($this->tableToMetadata[$tableName])) {
                throw new LogicException(sprintf(
                    'Unable to register table schema metadata for "%s" table. Schema metadata is already registered.',
                    $tableName,
                ));
            }
            $this->tableToMetadata[$tableName] = $metadata;

            $className = $metadata->getClassName();
            if ($className === null) {
                continue;
            }

            if ($metadata instanceof TranslationDoctrineSchemaMetadataInterface) {
                if (isset($this->translationMetadata[$className])) {
                    throw new LogicException(sprintf(
                        'Unable to register translation schema metadata for "%s" class. Schema metadata is already registered.',
                        $className,
                    ));
                }

                $this->translationMetadata[$className] = $metadata;

                continue;
            }

            if (isset($this->metadata[$className])) {
                throw new LogicException(sprintf(
                    'Unable to register schema metadata for "%s" class. Schema metadata is already registered.',
                    $className,
                ));
            }

            $this->metadata[$className] = $metadata;
        }
        $this->initialized = true;
    }

    public function getAvailableMetadata(): array
    {
        $this->ensureInitialized();

        return array_keys($this->metadata);
    }

    public function getMetadata(string $className): DoctrineSchemaMetadataInterface
    {
        $this->ensureInitialized();

        return $this->metadata[$className];
    }

    public function getTranslationMetadata(string $className): TranslationDoctrineSchemaMetadataInterface
    {
        $this->ensureInitialized();

        return $this->translationMetadata[$className];
    }

    public function getMetadataForTable(string $tableName): DoctrineSchemaMetadataInterface
    {
        $this->ensureInitialized();

        if (!isset($this->tableToMetadata[$tableName])) {
            throw new LogicException(sprintf(
                'Failed to find metadata for table "%s". Did you forget to tag your gateway with "%s" tag?',
                $tableName,
                IbexaCorePersistenceExtension::TAG_DOCTRINE_GATEWAY,
            ));
        }

        return $this->tableToMetadata[$tableName];
    }
}
