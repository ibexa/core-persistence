<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\CorePersistence\Fixtures;

use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadata;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;

/**
 * @phpstan-extends \Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase<array{
 *     id: int,
 *     relationship_2_foo: string,
 * }>
 */
final class Relationship2Gateway extends AbstractDoctrineDatabase
{
    protected function getTableName(): string
    {
        return 'relationship_2_table_name';
    }

    protected function buildMetadata(): DoctrineSchemaMetadataInterface
    {
        /** @var class-string $class */
        $class = 'Relationship2Class';
        $columns = [
            'id' => Types::INTEGER,
            'relationship_2_foo' => Types::STRING,
        ];

        return new DoctrineSchemaMetadata(
            $this->connection,
            $class,
            $this->getTableName(),
            $columns,
            ['id'],
        );
    }
}
