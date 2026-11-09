<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveModuleClassResolver;

/**
 * Offers addResponsiveChildren on records that place another module - the "module" content element
 * and the module wrappers (root page, language or page dependent) - where the placed module supports
 * children settings, so the includer can override them (see
 * ResponsiveModuleClassResolver::resolveChildrenSource()).
 *
 * Whether the toggle is offered depends on the record itself (which module it places), while palettes
 * are shared per type: two "module" elements on one page can place a news list and a navigation. So the
 * toggle is added to the type's palette up front (config.onload, no record involved), core resolves its
 * sub-palette as for any selector, and config.onpalette - called per record, also for every record of
 * editAll and for the Ajax sub-palette toggle - removes it again where the placed module does not offer
 * it. List views build no palette, so no record is looked up there.
 */
class IncludesListener
{
    private const FIELD = 'addResponsiveChildren';

    private const WRAPPER_MODULES = [
        'root_page_dependent_modules' => 'rootPageDependentModules',
        'languageDependentModule' => 'languageDependentModules',
        'dynamicModule' => 'dynamicModule',
    ];

    /** The field's own label while a record's label replaces it (a reference into $GLOBALS['TL_LANG']) */
    private mixed $varDefaultLabel = null;

    private bool $blnLabelReplaced = false;

    public function __construct(private readonly ResponsiveModuleClassResolver $moduleClassResolver)
    {
    }

    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function addContentChildrenToggle(): void
    {
        self::addToggle('tl_content', ['module']);
    }

    #[AsCallback(table: 'tl_module', target: 'config.onload')]
    public function addWrapperChildrenToggle(): void
    {
        self::addToggle('tl_module', array_keys(self::WRAPPER_MODULES));
    }

    /**
     * Per "module" element: keeps the toggle only where the placed module offers the override, and
     * shows that module's current children classes in its description.
     */
    #[AsCallback(table: 'tl_content', target: 'config.onpalette')]
    public function applyContentChildrenToggle(string $strPalette, DataContainer $objDca): string
    {
        $this->restoreLabel();

        $arrRecord = self::currentRecord($objDca);

        if ('module' !== ($arrRecord['type'] ?? null)) {
            return $strPalette;
        }

        $objModule = ModuleModel::findByPk($arrRecord['module'] ?? 0);

        // The frontend only honours the override where it is offered - one definition for both
        if (!$objModule || !$this->moduleClassResolver->offersChildrenOverride((string) $objModule->type)) {
            return self::withoutToggle($strPalette);
        }

        $arrClasses = System::getContainer()->get('kiwi.contao.responsive.frontend')->getAllInnerContainerClasses($objModule->row(), [], 'tl_module');

        // A copy: the language array itself must stay unformatted for the next record
        $arrLabel = (array) ($GLOBALS['TL_LANG']['responsive']['overwriteResponsiveChildren'] ?? []);
        $arrLabel[1] = sprintf($arrLabel[1] ?? '', '(' . implode(' ', $arrClasses) . ')');
        $this->replaceLabel($arrLabel);

        return $strPalette;
    }

    /**
     * Per wrapper module: keeps the toggle only when at least one of the modules it includes offers
     * the override - the same condition the frontend uses to apply children settings to the child.
     */
    #[AsCallback(table: 'tl_module', target: 'config.onpalette')]
    public function applyWrapperChildrenToggle(string $strPalette, DataContainer $objDca): string
    {
        $arrRecord = self::currentRecord($objDca);
        $strType = $arrRecord['type'] ?? '';

        if (!isset(self::WRAPPER_MODULES[$strType])) {
            return $strPalette;
        }

        $arrConfig = StringUtil::deserialize($arrRecord[self::WRAPPER_MODULES[$strType]] ?? '', true);

        $arrModuleIds = match ($strType) {
            'root_page_dependent_modules' => $arrConfig,
            'languageDependentModule' => array_column($arrConfig, 'mod'),
            'dynamicModule' => array_column($arrConfig, 'module'),
        };

        foreach (array_filter($arrModuleIds) as $intId) {
            $objModule = ModuleModel::findByPk($intId);

            if ($objModule && $this->moduleClassResolver->offersChildrenOverride((string) $objModule->type)) {
                return $strPalette;
            }
        }

        return self::withoutToggle($strPalette);
    }

    /**
     * @param list<string> $arrTypes
     */
    private static function addToggle(string $strTable, array $arrTypes): void
    {
        // Only palettes that exist: the wrapper types come from optional bundles
        $arrTypes = array_values(array_filter($arrTypes, static fn (string $strType): bool => isset($GLOBALS['TL_DCA'][$strTable]['palettes'][$strType])));

        if (!$arrTypes) {
            return;
        }

        PaletteManipulatorExtended::create()
            ->addLegend('items_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
            ->addField([self::FIELD], 'items_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalettes($arrTypes, $strTable);
    }

    /**
     * Removes the toggle from a resolved palette, together with the sub-palette block core inserted
     * behind it when it is enabled ("addResponsiveChildren,[addResponsiveChildren],…,[EOF]", possibly
     * holding nested blocks). A legend left without fields is dropped by core when rendering.
     *
     * Not done with PaletteManipulator: it parses the sub-palette markers as plain field names and
     * removes by value per legend, so it would either leave the block behind or strip the [EOF]
     * markers of other sub-palettes in the same legend.
     */
    private static function withoutToggle(string $strPalette): string
    {
        $arrBoxes = explode(';', $strPalette);

        foreach ($arrBoxes as $k => $strBox) {
            $arrTokens = explode(',', $strBox);
            $arrKept = [];
            $intDepth = 0;

            for ($i = 0, $n = \count($arrTokens); $i < $n; ++$i) {
                $strToken = trim($arrTokens[$i]);

                if ($intDepth > 0) {
                    if ('[EOF]' === $strToken) {
                        --$intDepth;
                    } elseif (preg_match('/^\[.+]$/', $strToken)) {
                        ++$intDepth;
                    }

                    continue;
                }

                if (self::FIELD === $strToken) {
                    if ('[' . self::FIELD . ']' === trim($arrTokens[$i + 1] ?? '')) {
                        $intDepth = 1;
                        ++$i;
                    }

                    continue;
                }

                $arrKept[] = $arrTokens[$i];
            }

            $arrBoxes[$k] = implode(',', $arrKept);
        }

        return implode(';', $arrBoxes);
    }

    /**
     * The record whose palette is being built. Unreadable records render no form, and core's own
     * palette lookup treats them as having no record as well.
     */
    private static function currentRecord(DataContainer $objDca): array
    {
        try {
            return $objDca->getCurrentRecord() ?? [];
        } catch (AccessDeniedException) {
            return [];
        }
    }

    /**
     * The field's label is a reference into $GLOBALS['TL_LANG'], shared with every other table using
     * the field, so it is rebound rather than assigned - an assignment would write through it.
     */
    private function replaceLabel(array $arrLabel): void
    {
        $arrField = &$GLOBALS['TL_DCA']['tl_content']['fields'][self::FIELD];

        if (!$this->blnLabelReplaced) {
            $this->varDefaultLabel = &$arrField['label'];
            $this->blnLabelReplaced = true;
        }

        unset($arrField['label']);
        $arrField['label'] = $arrLabel;
    }

    /**
     * Records of other types share the field (e.g. element groups) and must get its own label back
     * after a "module" element replaced it in the same request (editAll).
     */
    private function restoreLabel(): void
    {
        if (!$this->blnLabelReplaced) {
            return;
        }

        $arrField = &$GLOBALS['TL_DCA']['tl_content']['fields'][self::FIELD];
        unset($arrField['label']);
        $arrField['label'] = &$this->varDefaultLabel;
        $this->blnLabelReplaced = false;
    }
}
