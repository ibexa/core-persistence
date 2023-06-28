<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\GatewayInterface;
use Ibexa\CorePersistence\Gateway\DoctrineSchemaMetadataRegistry;
use LogicException;
use PHPUnit\Framework\TestCase;

final class DoctrineSchemaMetadataRegistryTest extends TestCase
{
    private DoctrineSchemaMetadataRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new DoctrineSchemaMetadataRegistry([
            $this->createGateway($this->createMetadata('foo', 'Foo')),
            $this->createGateway($this->createMetadata('bar')),
        ]);
    }

    public function testGetMetadataForTableFailure(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Failed to find metadata for table "nonexistent". '
            . 'Did you forget to tag your gateway with "ibexa.core.persistence.doctrine_gateway" tag?'
        );

        $this->registry->getMetadataForTable('nonexistent');
    }

    public function testGetMetadataForClassFailure(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Failed to find metadata for class "stdClass". '
            . 'Did you forget to tag your gateway with "ibexa.core.persistence.doctrine_gateway" tag?'
        );

        $this->registry->getMetadataForClass('stdClass');
    }

    public function testGetMetadataFailure(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Failed to find metadata for table or class "nonexistent". '
            . 'Did you forget to tag your gateway with "ibexa.core.persistence.doctrine_gateway" tag?'
        );

        $this->registry->getMetadata('nonexistent');
    }

    public function testGetMetadata(): void
    {
        /** @phpstan-var class-string $classString */
        $classString = 'Foo';

        $metadata = $this->registry->getMetadata('foo');
        self::assertSame('foo', $metadata->getTableName());
        self::assertSame($classString, $metadata->getClassName());

        $metadata = $this->registry->getMetadata($classString);
        self::assertSame('foo', $metadata->getTableName());
        self::assertSame($classString, $metadata->getClassName());

        $metadata = $this->registry->getMetadata('bar');
        self::assertSame('bar', $metadata->getTableName());
        self::assertNull($metadata->getClassName());
    }

    public function testGetMetadataForTable(): void
    {
        /** @phpstan-var class-string $classString */
        $classString = 'Foo';

        $metadata = $this->registry->getMetadataForTable('foo');
        self::assertSame('foo', $metadata->getTableName());
        self::assertSame($classString, $metadata->getClassName());

        $metadata = $this->registry->getMetadataForTable('bar');
        self::assertSame('bar', $metadata->getTableName());
        self::assertNull($metadata->getClassName());
    }

    public function testGetMetadataForClass(): void
    {
        /** @phpstan-var class-string $classString */
        $classString = 'Foo';

        $metadata = $this->registry->getMetadataForClass($classString);
        self::assertSame('foo', $metadata->getTableName());
        self::assertSame($classString, $metadata->getClassName());
    }

    /**
     * @return \Ibexa\Contracts\CorePersistence\Gateway\GatewayInterface<array<mixed>>
     */
    private function createGateway(DoctrineSchemaMetadataInterface $metadata): GatewayInterface
    {
        $gateway = $this->createMock(GatewayInterface::class);

        $gateway
            ->method('getMetadata')
            ->willReturn($metadata);

        return $gateway;
    }

    private function createMetadata(string $tableName, ?string $className = null): DoctrineSchemaMetadataInterface
    {
        $mock = $this->createMock(DoctrineSchemaMetadataInterface::class);

        $mock->method('getTableName')->willReturn($tableName);
        $mock->method('getClassName')->willReturn($className);

        return $mock;
    }
}
