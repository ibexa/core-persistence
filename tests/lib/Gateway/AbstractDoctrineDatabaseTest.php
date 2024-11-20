<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadata;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase
 */
final class AbstractDoctrineDatabaseTest extends TestCase
{
    public function testDateTimeQueries(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($connection));

        $connection->expects(self::atLeastOnce())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSQL94Platform());

        $connection->expects(self::atLeastOnce())
            ->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($connection));

        $result = $this->createMock(ResultStatement::class);
        $result->expects(self::once())
            ->method('fetch')
            ->willReturn(false);

        $connection->expects(self::once())
            ->method('executeQuery')
            ->with(
                self::identicalTo(
                    'SELECT __foo_table__.id, __foo_table__.date_time_immutable_column FROM __foo_table__ __foo_table__ WHERE __foo_table__.date_time_immutable_column = ?',
                ),
                self::identicalTo([
                    1 => '2024-01-01 00:00:00',
                ]),
            )
            ->willReturn($result);

        $registry = $this->createMock(DoctrineSchemaMetadataRegistryInterface::class);

        $database = new class($connection, $registry) extends AbstractDoctrineDatabase {
            protected function getTableName(): string
            {
                return '__foo_table__';
            }

            protected function buildMetadata(): DoctrineSchemaMetadataInterface
            {
                return new DoctrineSchemaMetadata(
                    $this->connection,
                    null,
                    $this->getTableName(),
                    [
                        'id' => Types::INTEGER,
                        'date_time_immutable_column' => Types::DATETIME_IMMUTABLE,
                    ],
                    ['id'],
                );
            }
        };

        $database->findBy([
            'date_time_immutable_column' => new \DateTimeImmutable('2024-01-01 00:00:00'),
        ]);
    }
}
