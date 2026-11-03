<?php

declare(strict_types=1);

namespace Kiwi\Contao\ResponsiveBaseBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Switches off responsive settings that never had any effect before 1.1, but would win now.
 *
 * Since 1.1 the outermost includer wins: a wrapper module (core's root page dependent modules,
 * kiwi/contao-core-bundle's language dependent and dynamic modules) and the include element
 * placing it outrank the module that is actually rendered. Before, they were never consulted:
 *
 *   - Wrapper modules never received responsive classes themselves - they render through
 *     ModuleProxy (fragment controllers) or return the included module's output directly
 *     (IncludesModuleTrait), so the getFrontendModule hook found no Template to apply them to -
 *     and only their cssID was handed down to the included module.
 *   - An include element placing a wrapper sets its "cte" backref on the wrapper's model, not on
 *     the module the wrapper renders, so its children settings never reached that module either.
 *
 * Whatever those records hold was therefore never a decision anyone saw. Left enabled they would
 * now override the rendered module - typically a wrapper's untouched col-12 replacing the module's
 * own columns, or an include element's row-cols-1 collapsing a news list. Disabling them makes
 * each level transparent again, so the rendered module's own settings apply as before, including
 * per-language or per-page differences no single includer could express.
 *
 * The include element's column settings are left alone: an element has no toggle to fall through,
 * so {@see DelegatedModuleColumnsMigration} carries the rendered module's columns up to it instead.
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
        return 'Kiwi Responsive Base: disable wrapper and include settings that had no effect before 1.1';
    }

    public function shouldRun(): bool
    {
        if (!$this->hasRequiredColumns() || !$this->isUpgradeFrom10($this->connection)) {
            return false;
        }

        return $this->countWrappers() > 0 || $this->countIncludeElements() > 0;
    }

    public function run(): MigrationResult
    {
        $intWrappers = (int) $this->connection->executeStatement(
            "UPDATE tl_module SET addResponsive = '', addResponsiveChildren = ''
                WHERE type IN (:types) AND (" . self::enabled('addResponsive') . ' OR ' . self::enabled('addResponsiveChildren') . ')',
            ['types' => self::WRAPPER_TYPES],
            ['types' => ArrayParameterType::STRING],
        );

        $intElements = (int) $this->connection->executeStatement(
            "UPDATE tl_content c INNER JOIN tl_module m ON m.id = c.module SET c.addResponsiveChildren = ''
                WHERE c.type = 'module' AND m.type IN (:types) AND " . self::enabled('c.addResponsiveChildren'),
            ['types' => self::WRAPPER_TYPES],
            ['types' => ArrayParameterType::STRING],
        );

        return $this->createResult(true, sprintf(
            'Disabled the responsive settings of %d wrapper module(s) and the children settings of %d '
            . 'include element(s) placing a wrapper. They had no effect before 1.1 and would now override '
            . 'the module that is rendered.',
            $intWrappers,
            $intElements,
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

    private function countIncludeElements(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_content c INNER JOIN tl_module m ON m.id = c.module
                WHERE c.type = 'module' AND m.type IN (:types) AND " . self::enabled('c.addResponsiveChildren'),
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

        if (!$schemaManager->tablesExist(['tl_content', 'tl_module'])) {
            return false;
        }

        $arrModule = array_map(static fn ($col) => strtolower($col->getName()), $schemaManager->listTableColumns('tl_module'));
        $arrContent = array_map(static fn ($col) => strtolower($col->getName()), $schemaManager->listTableColumns('tl_content'));

        return \in_array('addresponsive', $arrModule, true)
            && \in_array('addresponsivechildren', $arrModule, true)
            && \in_array('addresponsivechildren', $arrContent, true)
            && \in_array('module', $arrContent, true);
    }
}
