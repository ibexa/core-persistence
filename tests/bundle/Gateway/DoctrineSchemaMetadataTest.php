<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\CorePersistence\Gateway;

use Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingExceptionInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadata;
use PHPUnit\Framework\TestCase;

final class DoctrineSchemaMetadataTest extends TestCase
{
    private DoctrineSchemaMetadata $metadata;

    protected function setUp(): void
    {
        $this->metadata = new DoctrineSchemaMetadata(
            $this->createMock(\Doctrine\DBAL\Connection::class),
            'stdClass',
            'std_class_table',
            [
                'id' => 'integer',
                'column_string' => 'string',
            ],
            ['id'],
        );
    }

    public function testGetRelationshipByForeignColumnWithoutRelationships(): void
    {
        $this->expectException(RuntimeMappingExceptionInterface::class);
        $this->expectExceptionMessage('"foo" does not exist as a relationship for "stdClass" class metadata. Available relationship columns: ""');
        $this->metadata->getRelationshipByForeignKeyColumn('foo');
    }

    public function testGetRelationshipByForeignPropertyWithoutRelationships(): void
    {
        $this->expectException(RuntimeMappingExceptionInterface::class);
        $this->expectExceptionMessage('"foo" does not exist as a relationship for "stdClass" class metadata. Available relationship property: ""');
        $this->metadata->getRelationshipByForeignProperty('foo');
    }

    public function testGetRelationshipByForeignColumn(): void
    {
        $relationship = $this->getFooRelationship();
        $this->metadata->addRelationship($relationship);

        $result = $this->metadata->getRelationshipByForeignKeyColumn('foo_column');
        self::assertSame($relationship, $result);
    }

    public function testGetRelationshipByForeignProperty(): void
    {
        $relationship = $this->getFooRelationship();
        $this->metadata->addRelationship($relationship);

        $result = $this->metadata->getRelationshipByForeignProperty('foo_property');
        self::assertSame($relationship, $result);
    }

    private function getFooRelationship(): DoctrineRelationshipInterface
    {
        $relationship = $this->createMock(DoctrineRelationshipInterface::class);
        $relationship->method('getForeignKeyColumn')->willReturn('foo_column');
        $relationship->method('getForeignProperty')->willReturn('foo_property');

        return $relationship;
    }
}
