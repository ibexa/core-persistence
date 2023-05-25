<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Gateway;

/**
 * @template T
 */
interface TranslationGatewayInterface
{
    public function getMetadata(): TranslationDoctrineSchemaMetadataInterface;

    /**
     * @return array<T>
     */
    public function findByTranslatableId(int $id): array;

    public function countByTranslatableId(int $id): int;

    /**
     * @param int[] $ids
     *
     * @return array<T>
     */
    public function findByTranslatableIds(array $ids): array;

    public function exists(int $id, int $languageId): bool;

    /**
     * @param array<string,mixed> $data
     */
    public function insert(int $id, int $languageId, array $data): int;

    /**
     * @param array<string,mixed> $data
     */
    public function update(int $id, int $languageId, array $data): void;

    /**
     * @param array<string,mixed> $data
     */
    public function save(int $id, int $languageId, array $data): void;

    public function delete(int $id, int $languageId): void;

    public function copy(int $fromId, int $toId): void;
}
