<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\CorePersistence\Fixtures;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadata;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataRegistryInterface;

/**
 * A translatable entity gateway. Both "article" and its "article_translation" table
 * define their own "id" primary key, mirroring the layout of real translatable entities.
 *
 * @extends \Ibexa\Contracts\CorePersistence\Gateway\AbstractDoctrineDatabase<array{
 *     id: int,
 *     identifier: string,
 * }>
 */
final class ArticleGateway extends AbstractDoctrineDatabase
{
    private ArticleTranslationGateway $translationGateway;

    public function __construct(
        Connection $connection,
        DoctrineSchemaMetadataRegistryInterface $registry,
        ArticleTranslationGateway $translationGateway
    ) {
        parent::__construct($connection, $registry);

        $this->translationGateway = $translationGateway;
    }

    protected function getTableName(): string
    {
        return 'article';
    }

    protected function getTableAlias(): string
    {
        return 'article';
    }

    protected function buildMetadata(): DoctrineSchemaMetadataInterface
    {
        $metadata = new DoctrineSchemaMetadata(
            $this->connection,
            self::class,
            $this->getTableName(),
            [
                'id' => Types::INTEGER,
                'identifier' => Types::STRING,
            ],
            ['id']
        );

        $metadata->setTranslationSchemaMetadata($this->translationGateway->getMetadata());

        return $metadata;
    }
}
