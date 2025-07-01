<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadata;
use Ibexa\Tests\CorePersistence\Stub\RelationshipClass;
use PHPUnit\Framework\TestCase;

abstract class BaseRelationshipTypeStrategyTestCase extends TestCase
{
    /** @var \Doctrine\DBAL\Connection&\PHPUnit\Framework\MockObject\MockObject */
    protected Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection
            ->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($this->connection));

        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMockForAbstractClass();

        $this->connection
            ->method('getDatabasePlatform')
            ->willReturn($platform);
    }

    /**
     * @phpstan-param DoctrineRelationship::JOIN_TYPE_* $joinType
     */
    protected function createDoctrineRelationship(string $joinType): DoctrineRelationship
    {
        return new DoctrineRelationship(
            RelationshipClass::class,
            'foreign_property',
            'foreign_key_column',
            'related_class_id_column',
            $joinType
        );
    }

    protected function createDoctrineSchemaMetadata(): DoctrineSchemaMetadata
    {
        return new DoctrineSchemaMetadata(
            $this->connection,
            RelationshipClass::class,
            'test_alias',
            ['related_class_id_column' => 'integer'],
            ['foreign_key_column' => 'integer']
        );
    }
}
