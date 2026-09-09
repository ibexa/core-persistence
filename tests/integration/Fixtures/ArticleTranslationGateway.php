<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\CorePersistence\Fixtures;

use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\CorePersistence\Gateway\AbstractTranslationGateway;
use Ibexa\Contracts\CorePersistence\Gateway\TranslationDoctrineSchemaMetadata;
use Ibexa\Contracts\CorePersistence\Gateway\TranslationDoctrineSchemaMetadataInterface;

/**
 * Translation gateway for {@see ArticleGateway}. The "id" column is this table's own
 * primary key and deliberately shares its name with the main table's primary key.
 *
 * @extends \Ibexa\Contracts\CorePersistence\Gateway\AbstractTranslationGateway<array{
 *     id: int,
 *     article_id: int,
 *     language_id: int,
 *     name: string,
 * }>
 */
final class ArticleTranslationGateway extends AbstractTranslationGateway
{
    protected function getTableName(): string
    {
        return 'article_translation';
    }

    protected function buildMetadata(): TranslationDoctrineSchemaMetadataInterface
    {
        return new TranslationDoctrineSchemaMetadata(
            $this->connection,
            self::class,
            $this->getTableName(),
            [
                'id' => Types::INTEGER,
                'article_id' => Types::INTEGER,
                'language_id' => Types::INTEGER,
                'name' => Types::STRING,
            ],
            ['id'],
            'language_id',
            'article_id'
        );
    }
}
