<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\DBAL\Query\QueryBuilder;

final class RelationshipQuery
{
    private Parameter $parameter;

    private QueryBuilder $queryBuilder;

    public function __construct(
        Parameter $parameter,
        QueryBuilder $queryBuilder
    ) {
        $this->parameter = $parameter;
        $this->queryBuilder = $queryBuilder;
    }

    public function getParameter(): Parameter
    {
        return $this->parameter;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}
