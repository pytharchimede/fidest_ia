<?php

declare(strict_types=1);

use FidestIA\Core\Database;
use FidestIA\Core\MigrationRunner;

$root = dirname(__DIR__);
$config = require $root . '/bootstrap.php';

$db = Database::connection($config);
$runner = new MigrationRunner($db, $root . '/database/migrations');
$applied = $runner->run();

if ($applied === []) {
    fwrite(STDOUT, "Aucune migration en attente.\n");
    exit(0);
}

fwrite(STDOUT, "Migrations appliquées :\n- " . implode("\n- ", $applied) . "\n");
