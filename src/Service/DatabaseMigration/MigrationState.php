<?php declare(strict_types=1);

namespace Movary\Service\DatabaseMigration;

enum MigrationState : string
{
    case DOCTRINE = 'doctrine';

    case EMPTY = 'empty';

    case LEGACY_INCOMPLETE = 'legacy-incomplete';

    case LEGACY_READY = 'legacy-ready';

    case UNEXPECTED = 'unexpected';
}
