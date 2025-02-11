<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use DateTimeImmutable;
use Doctrine\Common\Collections\Expr\Comparison;
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
    /**
     * @dataProvider provideForDateTimeQueries
     *
     * @param array<mixed|\Doctrine\Common\Collections\Expr\Comparison> $query
     * @param array<array-key, string> $expectedParameters
     */
    public function testDateTimeQueries(
        array $query,
        string $sql,
        array $expectedParameters
    ): void {
        $connection = $this->createConnectionMock();
        $database = $this->createDatabaseGateway($connection);

        $result = $this->createMock(ResultStatement::class);
        $result->expects(self::once())
            ->method('fetch')
            ->willReturn(false);

        $connection->expects(self::once())
            ->method('executeQuery')
            ->with(
                self::identicalTo($sql),
                self::identicalTo($expectedParameters),
            )
            ->willReturn($result);

        $database->findBy($query);
    }

    /**
     * @return iterable<array{
     *     array<mixed|\Doctrine\Common\Collections\Expr\Comparison>,
     *     non-empty-string,
     *     array<array-key, string>,
     * }>
     */
    public static function provideForDateTimeQueries(): iterable
    {
        yield [
            [
                new Comparison(
                    'date_time_immutable_column',
                    Comparison::LT,
                    new DateTimeImmutable('2024-01-01 00:00:00'),
                ),
            ],
            str_replace(
                "\n",
                ' ',
                <<<SQL
                SELECT __foo_table__.id, __foo_table__.date_time_immutable_column
                FROM __foo_table__ __foo_table__
                WHERE __foo_table__.date_time_immutable_column < :date_time_immutable_column_0
                SQL,
            ),
            [
                'date_time_immutable_column_0' => '2024-01-01 00:00:00',
            ],
        ];

        yield [
            [
                'date_time_immutable_column' => new DateTimeImmutable('2024-01-01 00:00:00'),
            ],
            str_replace(
                "\n",
                ' ',
                <<<SQL
                SELECT __foo_table__.id, __foo_table__.date_time_immutable_column
                FROM __foo_table__ __foo_table__ WHERE __foo_table__.date_time_immutable_column = ?
                SQL,
            ),
            [
                1 => '2024-01-01 00:00:00',
            ],
        ];
    }

    /**
     * @return \Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase<array<mixed>>
     */
    private function createDatabaseGateway(Connection $connection): AbstractDoctrineDatabase
    {
        $registry = $this->createMock(DoctrineSchemaMetadataRegistryInterface::class);

        $metadata = new DoctrineSchemaMetadata(
            $connection,
            null,
            '__foo_table__',
            [
                'id' => Types::INTEGER,
                'date_time_immutable_column' => Types::DATETIME_IMMUTABLE,
            ],
            ['id'],
        );

        $registry->expects(self::atLeastOnce())
            ->method('getMetadataForTable')
            ->with(self::identicalTo('__foo_table__'))
            ->willReturn($metadata);

        return new class($connection, $registry, $metadata) extends AbstractDoctrineDatabase {
            private DoctrineSchemaMetadata $metadata;

            public function __construct(
                Connection $connection,
                DoctrineSchemaMetadataRegistryInterface $registry,
                DoctrineSchemaMetadata $metadata
            ) {
                parent::__construct($connection, $registry);
                $this->metadata = $metadata;
            }

            protected function getTableName(): string
            {
                return '__foo_table__';
            }

            protected function buildMetadata(): DoctrineSchemaMetadataInterface
            {
                return $this->metadata;
            }
        };
    }

    /**
     * @return \Doctrine\DBAL\Connection&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createConnectionMock(): Connection
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

        return $connection;
    }
}
