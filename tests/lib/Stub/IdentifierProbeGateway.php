<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Stub;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadata;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;

/**
 * @phpstan-extends \Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase<array{
 *     id: int,
 *     left_id: int,
 *     right_id: int,
 *     name: string,
 * }>
 */
final class IdentifierProbeGateway extends AbstractDoctrineDatabase
{
    /** @var array<string> */
    private array $identifierColumns;

    /**
     * @param array<string> $identifierColumns
     */
    public function __construct(
        Connection $connection,
        DoctrineSchemaMetadataRegistryInterface $registry,
        array $identifierColumns
    ) {
        parent::__construct($connection, $registry);

        $this->identifierColumns = $identifierColumns;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function insert(array $data): int
    {
        return $this->doInsert($data);
    }

    protected function getTableName(): string
    {
        return 'test_table';
    }

    protected function getTableAlias(): string
    {
        return 'test_alias';
    }

    protected function buildMetadata(): DoctrineSchemaMetadataInterface
    {
        return new DoctrineSchemaMetadata(
            $this->connection,
            null,
            $this->getTableName(),
            [
                'id' => Types::INTEGER,
                'left_id' => Types::INTEGER,
                'right_id' => Types::INTEGER,
                'name' => Types::STRING,
            ],
            $this->identifierColumns,
        );
    }
}
