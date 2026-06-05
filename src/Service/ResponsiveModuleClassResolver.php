<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

use Contao\ModuleModel;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;

/**
 * Resolves the responsive outer column classes for a frontend module, applying the
 * wrapper chain (root page / language / page dependent module) via the "includedVia"
 * backref, the CTE relation, or the module's own settings - outermost wins.
 *
 * The wrapper walk (collectWrappers/getWrapperSource) is shared with the legacy
 * getFrontendModule hook. The row-based resolveColumnClasses() is used by the
 * frontend_module/_base.html.twig override (modern fragment templates) and the
 * parseTemplate hook (fragment modules using a legacy template).
 */
class ResponsiveModuleClassResolver
{
    public function __construct(protected ResponsiveFrontendService $responsiveFrontendService)
    {
    }

    /**
     * Returns the resolved responsive column classes for a module row (e.g. $model->row()
     * or a template's data array). Empty when no source applies.
     *
     * @return string[]
     */
    public function resolveColumnClasses(?array $arrRow): array
    {
        if (!$arrRow) {
            return [];
        }

        if (!$arrSourceRow = $this->resolveColumnSourceRow($arrRow)) {
            return [];
        }

        return $this->responsiveFrontendService->getAllResponsiveClasses($arrSourceRow, [], 'tl_module');
    }

    /**
     * Walks the "includedVia" backref upward starting at (and including) the given first
     * wrapper, and returns the wrappers outermost first. A visited guard protects against
     * a backref cycle. Pass the rendered module's own "includedVia" as the first wrapper.
     *
     * @return ModuleModel[]
     */
    public function collectWrappers(?ModuleModel $objFirstWrapper): array
    {
        $arrWrappers = [];
        $arrSeen = [];
        $objNode = $objFirstWrapper;

        while ($objNode instanceof ModuleModel) {
            $intId = spl_object_id($objNode);

            if (isset($arrSeen[$intId])) {
                break;
            }

            $arrSeen[$intId] = true;
            $arrWrappers[] = $objNode;
            $objNode = $objNode->includedVia ?? null;
        }

        // walked innermost first; the resolution rule wants the outermost wrapper to win
        return array_reverse($arrWrappers);
    }

    /**
     * Returns the outermost wrapper that has $strFlag available in its own palette and enabled.
     * Disabled or unavailable levels are transparent and fall through to the next one.
     *
     * @param ModuleModel[] $arrWrappers
     */
    public function getWrapperSource(array $arrWrappers, string $strFlag, bool $blnCheckPalette = true): ?ModuleModel
    {
        foreach ($arrWrappers as $objWrapper) {
            if (!$objWrapper->{$strFlag}) {
                continue;
            }

            if ($blnCheckPalette && !PaletteManipulatorExtended::create()->hasField($objWrapper->type, 'tl_module', $strFlag)) {
                continue;
            }

            return $objWrapper;
        }

        return null;
    }

    /**
     * Resolves which row supplies the responsive column classes: the outermost enabled wrapper,
     * otherwise the CTE-bound model, otherwise the module itself - mirroring the legacy
     * getFrontendModule logic so all paths stay consistent. The availability check uses the
     * rendered module's own type, like the hook does.
     */
    protected function resolveColumnSourceRow(array $arrRow): ?array
    {
        $arrWrappers = $this->collectWrappers($arrRow['includedVia'] ?? null);

        if ($objWrapper = $this->getWrapperSource($arrWrappers, 'addResponsive')) {
            return $objWrapper->row();
        }

        // When inserted via the "module" content element (CTE), source the responsive settings
        // from the content element record instead of the module's own. Field availability is
        // checked against the source's own table (tl_content for the CTE, tl_module otherwise).
        if ($objCte = ($arrRow['cte'] ?? null)?->getModel()) {
            return $objCte->addResponsive
                && PaletteManipulatorExtended::create()->hasField($objCte->type, 'tl_content', 'addResponsive')
                    ? $objCte->row()
                    : null;
        }

        return ($arrRow['addResponsive'] ?? false)
            && PaletteManipulatorExtended::create()->hasField($arrRow['type'] ?? '', 'tl_module', 'addResponsive')
                ? $arrRow
                : null;
    }
}
