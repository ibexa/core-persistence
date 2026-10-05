<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Ibexa\Bundle\RepositoryInstaller\Bootstrapper\DoctrineMigrationsSchemaHook;
use Ibexa\Contracts\Test\Core\Bootstrapper\BaseFixtureHook;
use Ibexa\Contracts\Test\Core\Bootstrapper\Bootstrapper;
use Ibexa\Contracts\Test\Core\Bootstrapper\DatabaseSchemaHook;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

chdir(dirname(__DIR__, 2));

// The schema_builder_event=false CI jobs install the database the way ibexa:install does with
// the SchemaBuilderEvent path turned off: ibexa/core's DoctrineMigrationsSchemaHook runs every
// Ibexa-tagged migration, schema and baseline content (ImportDataMigration) included. The legacy
// schema hook and the baseline fixture would populate the same tables again, so both are off.
// This package owns no tables, but its suite still installs everything it depends on - which is
// what catches a dependency whose migrations drift from its schema.yaml.
$options = [];
if (getenv('IBEXA_TEST_SCHEMA_BUILDER_EVENT_ENABLED') === '0') {
    $options[DatabaseSchemaHook::class] = [DatabaseSchemaHook::OPTION_LOAD_SCHEMA => false];
    $options[DoctrineMigrationsSchemaHook::class] = [DoctrineMigrationsSchemaHook::OPTION_INSTALL_SCHEMA => true];
    $options[BaseFixtureHook::class] = [BaseFixtureHook::OPTION_LOAD_BASE_FIXTURE => false];
}

(new Bootstrapper())->bootstrap(null, $options);
