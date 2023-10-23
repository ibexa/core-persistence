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
 *     relationship_1_foo: string,
 *     relationship_2_id: int,
 * }>
 */
final class Relationship1Gateway extends AbstractDoctrineDatabase
{

    protected function getTableName(): string
    {
        return 'relationship_1_table_name';
    }

    protected function buildMetadata(): DoctrineSchemaMetadataInterface
    {
        /** @var class-string $class */
        $class = 'Relationship1Class';
        $columns = [
            'id' => Types::INTEGER,
            'relationship_1_foo' => Types::STRING,
            'relationship_2_id' => Types::INTEGER,
        ];

        $metadata = new DoctrineSchemaMetadata(
            $this->connection,
            $class,
            $this->getTableName(),
            $columns,
            ['id'],
        );

        /** @var class-string $relationshipClass */
        $relationshipClass = 'Relationship2Class';
        $metadata->addRelationship(new PreJoinedDoctrineRelationship(
            $relationshipClass,
            'relationship_2',
            'relationship_2_id',
            'id',
        ));

        return $metadata;
    }
}
