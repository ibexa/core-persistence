<?php

namespace Ibexa\Tests\Integration\CorePersistence\Fixtures;

use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadata;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\PreJoinedDoctrineRelationship;

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

        /** @var class-string $relationshipClass */
        $relationshipClass = 'Relationship1Class';
        $metadata->addRelationship(new PreJoinedDoctrineRelationship(
            $relationshipClass,
            'relationship_1',
            'relationship_1_id',
            'id',
        ));

        return $metadata;
    }
}
