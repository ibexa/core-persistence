<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence;

use Doctrine\Common\Collections\Expr\Expression;
use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * @template T of object
 */
interface HandlerInterface
{
    /**
     * @return T[]
     */
    public function findAll(
        ?int $limit = null,
        int $offset = 0
    ): array;

    /**
     * @param array<string|int, scalar|array<scalar>|Expression> $criteria
     * @param array<string, mixed>|null $orderBy
     *
     * @return T[]
     */
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        int $offset = 0
    ): array;

    /**
     * @param array<string|int, scalar|array<scalar>|Expression> $criteria
     */
    public function countBy(array $criteria): int;

    /**
     * @return T
     *
     * @throws NotFoundException
     */
    public function find(int $id): object;

    public function exists(int $id): bool;
}
