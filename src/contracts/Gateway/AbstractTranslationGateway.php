<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Gateway;

/**
 * @template T of array
 *
 * @implements \Ibexa\Contracts\CorePersistence\Gateway\TranslationGatewayInterface<T>
 *
 * @extends \Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase<T>
 */
abstract class AbstractTranslationGateway extends AbstractDoctrineDatabase implements TranslationGatewayInterface
{
    abstract protected function buildMetadata(): TranslationDoctrineSchemaMetadataInterface;

    public function getMetadata(): TranslationDoctrineSchemaMetadataInterface
    {
        $metadata = parent::getMetadata();
        assert($metadata instanceof TranslationDoctrineSchemaMetadataInterface);

        return $metadata;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findByTranslatableId(int $id): array
    {
        /** @var array<T> */
        return $this->findBy([
            $this->getTranslatableIdColumn() => $id,
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\CorePersistence\Exception\MappingException
     */
    public function countByTranslatableId(int $id): int
    {
        return $this->countBy([
            $this->getTranslatableIdColumn() => $id,
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findByTranslatableIds(array $ids): array
    {
        /** @var array<T> */
        return $this->findBy([
            $this->getTranslatableIdColumn() => $ids,
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insert(int $id, int $languageId, array $data): int
    {
        $data += [
            $this->getTranslatableIdColumn() => $id,
            $this->getLanguageIdColumn() => $languageId,
        ];

        return $this->doInsert($data);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function update(int $id, int $languageId, array $data): void
    {
        $criteria = [
            $this->getTranslatableIdColumn() => $id,
            $this->getLanguageIdColumn() => $languageId,
        ];

        $this->doUpdate($criteria, $data);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function exists(int $id, int $languageId): bool
    {
        $result = $this->findOneBy([
            $this->getTranslatableIdColumn() => $id,
            $this->getLanguageIdColumn() => $languageId,
        ]);

        return $result !== null;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function save(int $id, int $languageId, array $data): void
    {
        if ($this->exists($id, $languageId)) {
            $this->update($id, $languageId, $data);
        } else {
            $this->insert($id, $languageId, $data);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function delete(int $id, int $languageId): void
    {
        $criteria = [
            $this->getTranslatableIdColumn() => $id,
            $this->getLanguageIdColumn() => $languageId,
        ];

        $this->doDelete($criteria);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function copy(int $fromId, int $toId): void
    {
        $translations = $this->findByTranslatableId($fromId);

        foreach ($translations as $translation) {
            unset($translation['id']);
            $translation[$this->getTranslatableIdColumn()] = $toId;

            $this->doInsert($translation);
        }
    }

    protected function getLanguageIdColumn(): string
    {
        return $this->getMetadata()->getLanguageIdColumn();
    }

    protected function getTranslatableIdColumn(): string
    {
        return $this->getMetadata()->getTranslatableIdColumn();
    }
}
