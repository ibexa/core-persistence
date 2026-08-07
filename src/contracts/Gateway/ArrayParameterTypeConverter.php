<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

/**
 * The inverse of {@see \Doctrine\DBAL\ArrayParameterType::toElementParameterType()}, which DBAL
 * does not provide.
 */
final class ArrayParameterTypeConverter
{
    /**
     * Every ParameterType is listed rather than defaulted, so a case DBAL adds later fails here
     * instead of silently binding as a string. The three without an array counterpart bind as
     * string: the databases coerce string literals for them in an IN list.
     */
    public static function fromParameterType(ParameterType $type): ArrayParameterType
    {
        return match ($type) {
            ParameterType::INTEGER => ArrayParameterType::INTEGER,
            ParameterType::ASCII => ArrayParameterType::ASCII,
            ParameterType::BINARY => ArrayParameterType::BINARY,
            ParameterType::STRING,
            ParameterType::BOOLEAN,
            ParameterType::LARGE_OBJECT,
            ParameterType::NULL => ArrayParameterType::STRING,
        };
    }
}
