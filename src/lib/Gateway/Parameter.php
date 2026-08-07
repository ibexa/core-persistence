<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

/**
 * @internal
 */
final class Parameter
{
    private string $name;

    private ArrayParameterType|ParameterType $type;

    /** @var mixed */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct(string $name, $value, ArrayParameterType|ParameterType $type)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getType(): ArrayParameterType|ParameterType
    {
        return $this->type;
    }
}
