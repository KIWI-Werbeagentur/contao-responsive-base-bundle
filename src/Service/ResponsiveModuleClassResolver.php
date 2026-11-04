<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

use Contao\ModuleModel;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;

/**
 * Resolves the responsive outer column classes and the children settings source for a frontend
 * module, applying the wrapper chain (root page / language / page dependent module) via the
 * "includedVia" backref, the CTE relation, or the module's own settings - outermost wins.
 *
 * The wrapper walk (collectWrappers) is shared with the legacy getFrontendModule hook and the
 * getAllEvents hook (resolveChildrenSource). The row-based resolveColumnClasses() is used by the
 * frontend_module/_base.html.twig override (modern fragment templates) and the
 * parseTemplate hook (fragment modules using a legacy template); resolveChildrenClasses() is its
 * counterpart for the template's items wrapper.
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

        [$arrSourceRow, $strTable, $blnSkipPaletteCheck] = $this->resolveColumnSourceRow($arrRow);

        if (!$arrSourceRow) {
            return [];
        }

        return $this->responsiveFrontendService->getAllResponsiveClasses($arrSourceRow, [], $strTable, $blnSkipPaletteCheck);
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
     * Resolves which record supplies the children (inner container) settings, and its table.
     *
     * The same "outermost includer wins" rule as the column settings, walked outermost first: per
     * wrapper the include element that placed it, then the wrapper itself; then the include
     * element that placed the rendered module directly; then the module's own record. Unlike
     * columns, every level - include elements included - opts in via its own addResponsiveChildren
     * toggle; a disabled level is transparent and falls through to the next one in.
     *
     * The wrapper's flag is read without a palette check: it gets addResponsiveChildren per record
     * in the backend only (see IncludesListener::addWrapperResponsiveChildrenSettings), so its
     * frontend palette cannot tell. The caller still checks that the rendered module supports an
     * inner container structurally.
     *
     * @param object|array  $varModule   the rendered module: its model, Module instance or row (e.g. a
     *                                   template's data array) - returned as is when it is the source
     * @param ModuleModel[] $arrWrappers outermost first, see collectWrappers()
     *
     * @return array{0: object|array|null, 1: string} [source record or null, its table]
     */
    public function resolveChildrenSource(object|array $varModule, array $arrWrappers): array
    {
        foreach ($arrWrappers as $objWrapper) {
            if (($objCte = ($objWrapper->cte ?? null)?->getModel()) && $objCte->addResponsiveChildren) {
                return [$objCte, 'tl_content'];
            }

            if ($objWrapper->addResponsiveChildren) {
                return [$objWrapper, 'tl_module'];
            }
        }

        $objModuleCte = \is_array($varModule) ? ($varModule['cte'] ?? null) : ($varModule->cte ?? null);

        if (($objCte = $objModuleCte?->getModel()) && $objCte->addResponsiveChildren) {
            return [$objCte, 'tl_content'];
        }

        $blnOwn = \is_array($varModule) ? ($varModule['addResponsiveChildren'] ?? false) : $varModule->addResponsiveChildren;

        return [$blnOwn ? $varModule : null, 'tl_module'];
    }

    /**
     * Returns the resolved children (inner container) classes for a module row - the counterpart
     * of resolveColumnClasses() for the items wrapper of modules rendering their own child items
     * (frontend_module/_base.html.twig, "items_attributes"). Same source and gate as the legacy
     * getFrontendModule hook: the outermost enabled includer wins, and the rendered module still
     * has to support children settings structurally.
     *
     * @return string[]
     */
    public function resolveChildrenClasses(mixed $varRow): array
    {
        if (!\is_array($varRow) || !$varRow) {
            return [];
        }

        $strType = (string) ($varRow['type'] ?? '');
        $blnSupported = \in_array($strType, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container'] ?? []), true)
            || PaletteManipulatorExtended::create()->hasField($strType, 'tl_module', 'addResponsiveChildren');

        if (!$blnSupported) {
            return [];
        }

        [$varSource, $strTable] = $this->resolveChildrenSource($varRow, $this->collectWrappers($varRow['includedVia'] ?? null));

        if (!$varSource) {
            return [];
        }

        // Include element and wrapper sources only carry addResponsiveChildren in their backend
        // palette, so their gate is skipped; the module's own row keeps it (see GetFrontendModuleListener)
        $blnOwn = \is_array($varSource);

        return $this->responsiveFrontendService->getAllInnerContainerClasses(
            $blnOwn ? $varSource : $varSource->row(),
            [],
            $strTable,
            !$blnOwn,
        );
    }

    /**
     * Resolves which row supplies the responsive column classes and the DCA table it belongs to:
     * the outermost enabled wrapper (tl_module), otherwise the CTE-bound content element
     * (tl_content), otherwise the module itself (tl_module) - mirroring the legacy
     * getFrontendModule logic so all paths stay consistent. The availability check uses the
     * source's own table so the palette gate is meaningful.
     *
     * @return array{0: ?array, 1: string, 2: bool} [source row or null, source table, skip palette check]
     */
    public function resolveColumnSourceRow(array $arrRow): array
    {

        // Walk the include chain outermost-first and let the outermost includer win. A module
        // wrapper that was itself inserted via the "module" content element (CTE) carries that
        // element as its "cte" backref; the CTE sits one level further out than the wrapper, so it
        // outranks the wrapper's own settings - and, being the outermost includer, everything below
        // it too. Wrappers that impose nothing (no cte, no enabled addResponsive) are transparent
        // and fall through to the next level in.
        foreach ($this->collectWrappers($arrRow['includedVia'] ?? null) as $objWrapper) {
            if ($objCte = ($objWrapper->cte ?? null)?->getModel()) {
                return [$objCte->row(), 'tl_content', true];
            }

            if ($objWrapper->addResponsive
                && PaletteManipulatorExtended::create()->hasField($objWrapper->type, 'tl_module', 'addResponsive')
            ) {
                return [$objWrapper->row(), 'tl_module', false];
            }
        }

        // The rendered module's own inclusion: a CTE that inserted it directly, otherwise its own
        // enabled responsive settings. tl_content has no "addResponsive" flag (bootstrap's
        // "responsiveOverwriteRowCols" etc. gate is enforced inside the frontend service), and its
        // responsive fields live behind a selector/subpalette absent from the frontend palette - so
        // source it unconditionally and skip the palette gate, letting the service decide what renders.
        if ($objCte = ($arrRow['cte'] ?? null)?->getModel()) {
            return [$objCte->row(), 'tl_content', true];
        }

        $arrSource = ($arrRow['addResponsive'] ?? false)
            && PaletteManipulatorExtended::create()->hasField($arrRow['type'] ?? '', 'tl_module', 'addResponsive')
                ? $arrRow
                : null;

        return [$arrSource, 'tl_module', false];
    }
}
