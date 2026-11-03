<?php

declare(strict_types=1);

namespace Kiwi\Contao\ResponsiveBaseBundle\Migration;

use Contao\Controller;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;

/**
 * Enables a module's own responsive toggles where 1.0 rendered its settings regardless.
 *
 * 1.0 checked the toggles with `=== 0`, but they are char(1) columns and arrive as strings, so the
 * check never matched: a module whose palette offers addResponsive / addResponsiveChildren had its
 * stored column and children settings rendered whether the toggle was on or not. 1.1 honours the
 * toggles, so modules saved with them off lose classes they always had - e.g. a news slider
 * losing the row-cols around its swiper.
 *
 * Enabling the toggle where settings are stored restores exactly that output. Item columns
 * (responsiveColsItems) are the one setting 1.0 did gate on a truthy toggle; they are cleared on
 * the rows enabled here, so turning the toggle on does not wrap list items that never were.
 *
 * Wrapper modules are left to {@see ObsoleteIncluderSettingsMigration}: they never rendered their
 * own settings at all. Runs only on the upgrade from 1.0 ({@see DetectsUpgradeFrom10Trait}), so a
 * toggle switched off deliberately afterwards stays off.
 */
class ImplicitModuleFlagsMigration extends AbstractMigration
{
    use DetectsUpgradeFrom10Trait;

    private const WRAPPER_TYPES = ['root_page_dependent_modules', 'languageDependentModule', 'dynamicModule'];

    /** The fields getAllResponsiveClasses() renders. */
    private const COLUMN_FIELDS = ['responsiveCols', 'responsiveOffsets', 'responsiveOrder', 'responsiveAlignSelf'];

    /** The fields getAllInnerContainerClasses() renders, including contao-bootstrap's. */
    private const CHILDREN_FIELDS = ['responsiveRowCols', 'responsiveFlexDirection', 'responsiveFlexWrap', 'responsiveAlignItems', 'responsiveAlignContent', 'responsiveJustifyContent', 'responsiveGutter', 'responsiveRowGap'];

    /** @var array{columns: list<int>, children: list<int>}|null */
    private ?array $arrTargets = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Kiwi Responsive Base: enable module responsive toggles that 1.0 ignored';
    }

    public function shouldRun(): bool
    {
        if (!$this->isUpgradeFrom10($this->connection)) {
            return false;
        }

        $arrTargets = $this->collectTargets();

        return $arrTargets['columns'] !== [] || $arrTargets['children'] !== [];
    }

    public function run(): MigrationResult
    {
        $arrTargets = $this->collectTargets();

        if ($arrTargets['columns'] !== []) {
            $this->connection->executeStatement(
                "UPDATE tl_module SET addResponsive = '1' WHERE id IN (:ids)",
                ['ids' => $arrTargets['columns']],
                ['ids' => ArrayParameterType::INTEGER],
            );
        }

        if ($arrTargets['children'] !== []) {
            $strItems = \in_array('responsivecolsitems', $this->getColumns(), true) ? ', responsiveColsItems = NULL' : '';

            $this->connection->executeStatement(
                "UPDATE tl_module SET addResponsiveChildren = '1'$strItems WHERE id IN (:ids)",
                ['ids' => $arrTargets['children']],
                ['ids' => ArrayParameterType::INTEGER],
            );
        }

        $this->arrTargets = null;

        return $this->createResult(true, sprintf(
            'Enabled the column settings toggle on %d and the children settings toggle on %d module(s): '
            . '1.0 rendered their stored settings with the toggle off, 1.1 honours it.',
            \count($arrTargets['columns']),
            \count($arrTargets['children']),
        ));
    }

    /**
     * @return array{columns: list<int>, children: list<int>}
     */
    private function collectTargets(): array
    {
        if ($this->arrTargets !== null) {
            return $this->arrTargets;
        }

        $this->arrTargets = ['columns' => [], 'children' => []];
        $arrColumns = $this->getColumns();

        if (!\in_array('addresponsive', $arrColumns, true) || !\in_array('addresponsivechildren', $arrColumns, true)) {
            return $this->arrTargets;
        }

        $arrColumnFields = array_values(array_filter(self::COLUMN_FIELDS, static fn ($f) => \in_array(strtolower($f), $arrColumns, true)));
        $arrChildrenFields = array_values(array_filter(self::CHILDREN_FIELDS, static fn ($f) => \in_array(strtolower($f), $arrColumns, true)));

        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                "SELECT id, type, addResponsive, addResponsiveChildren, %s FROM tl_module WHERE type NOT IN (:types)
                    AND (COALESCE(addResponsive, '') IN ('', '0') OR COALESCE(addResponsiveChildren, '') IN ('', '0'))",
                implode(', ', array_merge($arrColumnFields, $arrChildrenFields)),
            ),
            ['types' => self::WRAPPER_TYPES],
            ['types' => ArrayParameterType::STRING],
        );

        if ($rows === []) {
            return $this->arrTargets;
        }

        Controller::loadDataContainer('tl_module');
        $arrContainerTypes = array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container'] ?? []);
        $objPalettes = PaletteManipulatorExtended::create();

        foreach ($rows as $row) {
            $strType = (string) $row['type'];

            // A type without a palette is not rendered at all.
            if (!isset($GLOBALS['TL_DCA']['tl_module']['palettes'][$strType])) {
                continue;
            }

            // Same applicability conditions as 1.0's getFrontendModule hook
            if (self::isOff($row['addResponsive']) && self::holdsAny($row, $arrColumnFields)
                && $objPalettes->hasField($strType, 'tl_module', 'addResponsive')
            ) {
                $this->arrTargets['columns'][] = (int) $row['id'];
            }

            if (self::isOff($row['addResponsiveChildren']) && self::holdsAny($row, $arrChildrenFields)
                && ($objPalettes->hasField($strType, 'tl_module', 'addResponsiveChildren') || \in_array($strType, $arrContainerTypes, true))
            ) {
                $this->arrTargets['children'][] = (int) $row['id'];
            }
        }

        return $this->arrTargets;
    }

    /**
     * @return list<string> lower-cased tl_module column names
     */
    private function getColumns(): array
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module'])) {
            return [];
        }

        return array_map(static fn ($col) => strtolower($col->getName()), $schemaManager->listTableColumns('tl_module'));
    }

    private static function isOff(mixed $varFlag): bool
    {
        return \in_array((string) $varFlag, ['', '0'], true);
    }

    /**
     * @param list<string> $arrFields
     */
    private static function holdsAny(array $row, array $arrFields): bool
    {
        foreach ($arrFields as $strField) {
            $arrValue = @unserialize((string) ($row[$strField] ?? ''), ['allowed_classes' => false]);

            if (\is_array($arrValue) && array_filter($arrValue, static fn ($v) => (string) $v !== '') !== []) {
                return true;
            }
        }

        return false;
    }
}
