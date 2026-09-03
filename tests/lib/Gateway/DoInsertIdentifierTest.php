<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use Ibexa\Tests\CorePersistence\Stub\IdentifierProbeGateway;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase
 */
final class DoInsertIdentifierTest extends TestCase
{
    /** @var \Doctrine\DBAL\Connection&\PHPUnit\Framework\MockObject\MockObject */
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->method('getDatabasePlatform')->willReturn(
            $this->getMockBuilder(AbstractPlatform::class)->getMockForAbstractClass()
        );
    }

    public function testReadsGeneratedIdentifierFromTheConnection(): void
    {
        $this->connection->expects(self::once())->method('insert');
        $this->connection->expects(self::once())->method('lastInsertId')->willReturn('42');

        $gateway = $this->createGateway(['id']);

        self::assertSame(42, $gateway->insert(['name' => 'a']));
    }

    public function testReturnsTheCallerSuppliedIdentifierInstead(): void
    {
        $this->connection->expects(self::once())->method('insert');
        $this->connection->expects(self::never())->method('lastInsertId');

        $gateway = $this->createGateway(['id']);

        self::assertSame(7, $gateway->insert(['id' => 7, 'name' => 'a']));
    }

    public function testRefusesACompositeKeyWithoutInsertingAnything(): void
    {
        $this->connection->expects(self::never())->method('insert');
        $this->connection->expects(self::never())->method('lastInsertId');

        $gateway = $this->createGateway(['left_id', 'right_id']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"test_table" does not have a single identifier column to return');

        $gateway->insert(['left_id' => 1, 'right_id' => 2]);
    }

    /**
     * @param array<string> $identifierColumns
     */
    private function createGateway(array $identifierColumns): IdentifierProbeGateway
    {
        return new IdentifierProbeGateway(
            $this->connection,
            $this->createMock(DoctrineSchemaMetadataRegistryInterface::class),
            $identifierColumns,
        );
    }
}
