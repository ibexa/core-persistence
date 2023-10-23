<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\CorePersistence\Fixtures;

use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineOneToManyRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadata;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;

/**
 * @phpstan-extends \Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase<array{
 *     id: int,
 *     foo: string,
 *     relationship_1_id: int,
 * }>
 */
final class FooGateway extends AbstractDoctrineDatabase
{
    protected function getTableName(): string
    {
        return 'foo_table_name';
    }

    protected function getTableAlias(): string
    {
        return 'foo_table_alias';
    }

    protected function buildMetadata(): DoctrineSchemaMetadataInterface
    {
        /** @var class-string $class */
        $class = 'FooClass';
        $columns = [
            'id' => Types::INTEGER,
            'foo' => Types::STRING,
            'relationship_1_id' => Types::INTEGER,
        ];

        $metadata = new DoctrineSchemaMetadata(
            $this->connection,
            $class,
            $this->getTableName(),
            $columns,
            ['id'],
        );

        /** @var class-string $relationship1Class */
        $relationship1Class = 'Relationship1Class';
        $metadata->addRelationship(new DoctrineRelationship(
            $relationship1Class,
            'relationship_1',
            'relationship_1_id',
            'id',
            DoctrineRelationship::JOIN_TYPE_JOINED,
        ));

        /** @var class-string $relationship3Class */
        $relationship3Class = 'Relationship3Class';
        $metadata->addRelationship(new DoctrineOneToManyRelationship(
            $relationship3Class,
            'relationship_3',
            'id',
        ));

        return $metadata;
    }
}
