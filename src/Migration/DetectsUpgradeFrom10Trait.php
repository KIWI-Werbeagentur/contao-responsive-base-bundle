<?php

declare(strict_types=1);

namespace Kiwi\Contao\ResponsiveBaseBundle\Migration;

use Doctrine\DBAL\Connection;

/**
 * Lets a migration run only on the contao:migrate run that upgrades an installation from 1.0.
 *
 * Migrations run before the schema update. On that run tl_content still lacks
 * responsiveGroupSpacingTop - the only column 1.1 adds - while responsiveCols (present since 1.0)
 * shows the bundle was installed before. Once the schema update has added the column it is false
 * for good, so a migration gated by it runs exactly once and leaves later, deliberate edits alone.
 *
 * Not detected: databases that already carry the column from a 1.1 development version, even one
 * that was rolled back (contao:migrate never drops columns without --with-deletes). Those are
 * treated as already upgraded and have to be checked by hand.
 */
trait DetectsUpgradeFrom10Trait
{
    private function isUpgradeFrom10(Connection $connection): bool
    {
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $arrColumns = array_map(
            static fn ($column) => strtolower($column->getName()),
            $schemaManager->listTableColumns('tl_content'),
        );

        return \in_array('responsivecols', $arrColumns, true)
            && !\in_array('responsivegroupspacingtop', $arrColumns, true);
    }
}
