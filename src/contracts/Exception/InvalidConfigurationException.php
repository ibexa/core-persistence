<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Exception;

use Exception;
use Throwable;

class InvalidConfigurationException extends Exception
{
    public function __construct(string $message = '', int $code = 1, Throwable $previous = null)
    {
        parent::__construct("Invalid schema configuration: {$message}", $code, $previous);
    }
}

class_alias(InvalidConfigurationException::class, 'EzSystems\DoctrineSchema\API\Exception\InvalidConfigurationException');
