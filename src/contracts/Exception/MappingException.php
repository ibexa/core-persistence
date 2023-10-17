<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Exception;

use Exception;

final class MappingException extends Exception implements MappingExceptionInterface
{
    public static function singleIdNotAllowedOnCompositePrimaryKey(): self
    {
        return new self('Attempted to get single ID column on composite primary key schema.');
    }

    public static function noIdDefined(): self
    {
        return new self('No ID column defined for schema');
    }
}
