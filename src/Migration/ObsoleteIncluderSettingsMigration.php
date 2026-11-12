<?php

declare(strict_types=1);

namespace Kiwi\Contao\ResponsiveBaseBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Switches off wrapper module settings that never had any effect before 1.1, but would win now.
 *
 * Since 1.1 the outermost includer wins: a wrapper module (core's root page dependent modules,
 * kiwi/contao-core-bundle's language dependent and dynamic modules) outranks the module that is
 * actually rendered. Before, it was never consulted: wrapper modules never received responsive
 * classes themselves - they render through ModuleProxy (fragment controllers) or return the
 * included module's output directly (IncludesModuleTrait), so the getFrontendModule hook found no
 * Template to apply them to - and only their cssID was handed down to the included module.
 *
 * Whatever those records hold was therefore never a decision anyone saw, yet both toggles are
 * offered on wrappers, so 1.1 honours them. Left enabled they would now override the rendered
 * module - typically a wrapper's untouched col-12 replacing the module's own columns, or its
 * row-cols-1 collapsing a news list. Disabling them makes the wrapper transparent again, so the
 * rendered module's own settings apply as before, including per-language or per-page differences
 * no single wrapper could express.
 *
 * Include elements placing a wrapper need no reset: they are never offered the children toggle,
 * so the frontend ignores whatever they store ({@see
 * \Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveModuleClassResolver::offersChildrenOverride()}).
 * Their column settings have no toggle to fall through, so {@see DelegatedModuleColumnsMigration}
 * carries the rendered module's columns up to them instead.
 *
 * Runs only on the upgrade from 1.0 ({@see DetectsUpgradeFrom10Trait}), so settings enabled
 * deliberately afterwards are kept.
 */
class ObsoleteIncluderSettingsMigration extends AbstractMigration
{
    use DetectsUpgradeFrom10Trait;

    private const WRAPPER_TYPES = ['root_page_dependent_modules', 'languageDependentModule', 'dynamicModule'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Kiwi Responsive Base: disable wrapper module settings that had no effect before 1.1';
    }

    public function shouldRun(): bool
    {
        if (!$this->hasRequiredColumns() || !$this->isUpgradeFrom10($this->connection)) {
            return false;
        }

        return $this->countWrappers() > 0;
    }

    public function run(): MigrationResult
    {
        $intWrappers = (int) $this->connection->executeStatement(
            "UPDATE tl_module SET addResponsive = '', addResponsiveChildren = ''
                WHERE type IN (:types) AND (" . self::enabled('addResponsive') . ' OR ' . self::enabled('addResponsiveChildren') . ')',
            ['types' => self::WRAPPER_TYPES],
            ['types' => ArrayParameterType::STRING],
        );

        return $this->createResult(true, sprintf(
            'Disabled the responsive settings of %d wrapper module(s). They had no effect before 1.1 '
            . 'and would now override the module that is rendered.',
            $intWrappers,
        ));
    }

    private function countWrappers(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_module WHERE type IN (:types) AND (' . self::enabled('addResponsive') . ' OR ' . self::enabled('addResponsiveChildren') . ')',
            ['types' => self::WRAPPER_TYPES],
            ['types' => ArrayParameterType::STRING],
        );
    }

    private static function enabled(string $strColumn): string
    {
        return "COALESCE($strColumn, '') NOT IN ('', '0')";
    }

    private function hasRequiredColumns(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module'])) {
            return false;
        }

        $arrModule = array_map(static fn ($col) => strtolower($col->getName()), $schemaManager->listTableColumns('tl_module'));

        return \in_array('addresponsive', $arrModule, true)
            && \in_array('addresponsivechildren', $arrModule, true);
    }
}
