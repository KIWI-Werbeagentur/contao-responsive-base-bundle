<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Hybrid;
use Contao\ModuleModel;
use Contao\System;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveModuleClassResolver;

#[AsHook('getFrontendModule')]
class GetFrontendModuleListener
{
    public function __construct(
        protected ResponsiveFrontendService $responsiveFrontendService,
        protected ResponsiveModuleClassResolver $classResolver,
    ) {
    }

    public function __invoke(ModuleModel $objModuleModel, string $strBuffer, object $objModule): string
    {
        // Reconstruct the wrapper chain by walking the "includedVia" backref upward (set by the
        // module wrappers, see RootPageDependentModulesControllerDecorator / IncludesModuleTrait)
        $arrWrappers = $this->classResolver->collectWrappers($objModuleModel->includedVia ?? null);

        // Consume our own backref so a shared registry model rendered again does not inherit it
        if (isset($objModuleModel->includedVia)) {
            $objModuleModel->includedVia = null;
        }

        if ($objModule->Template) {
            $shallReparse = false;

            // When inserted via the "module" content element (CTE), the column settings come from
            // the content element record instead of the module's own.
            $objCteModel = $objModuleModel->cte?->getModel() ?: null;
            $objTargetWithClasses = $objCteModel ?? $objModuleModel;
            $objModule->Template->baseClass = $objModule->typePrefix . $objModule->type;

            // Responsive Module Settings: the outermost enabled wrapper wins; otherwise a module
            // inserted via CTE takes its columns from the content element (whose own enable flag -
            // tl_content has no "addResponsive"; bootstrap's "responsiveOverwriteRowCols" etc. - is
            // enforced inside the frontend service), otherwise the module's own addResponsive applies.
            $objSource = $this->classResolver->getWrapperSource($arrWrappers, 'addResponsive');

            if (!$objSource) {
                if ($objCteModel) {
                    $objSource = $objCteModel;
                } elseif ($objModuleModel->addResponsive && PaletteManipulatorExtended::create()->hasField($objModuleModel->type, 'tl_module', 'addResponsive')) {
                    $objSource = $objModuleModel;
                }
            }

            if ($objSource) {
                $shallReparse = true;
                // Compute against the source's own table. For a CTE source skip the palette gate: the
                // content element's responsive fields live behind a selector/subpalette that is not
                // present in the frontend palette, so the service self-gates (responsiveOverwriteRowCols).
                $arrClasses = $this->responsiveFrontendService->getAllResponsiveClasses($objSource->row(), [], $objSource === $objCteModel ? 'tl_content' : 'tl_module', $objSource === $objCteModel);

                $strResponsiveClasses = implode(' ', $arrClasses);

                $objModule->Template->isResponsive = true;
                $objModule->Template->class = trim($objModule->Template->class . ' ' . $strResponsiveClasses);
            }

            //Responsive Children Settings
            $isField = PaletteManipulatorExtended::create()->hasField($objModuleModel->type, 'tl_module', 'addResponsiveChildren');
            $objTargetWithClasses = $objTargetWithClasses->addResponsiveChildren ? $objTargetWithClasses : $objModuleModel;
            $hasResponsiveChildren = in_array($objModuleModel->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']));

            // Flag-only wrapper check: the wrapper gets addResponsiveChildren per record in the
            // backend only (see IncludesListener::addWrapperResponsiveChildrenSettings), so the
            // palette cannot be inspected here - the child gate below ensures applicability
            $objSource = $this->classResolver->getWrapperSource($arrWrappers, 'addResponsiveChildren', false);

            if (!$objSource && $objTargetWithClasses->addResponsiveChildren) {
                $objSource = $objTargetWithClasses;
            }

            // The child still needs to support an inner container structurally, no matter whose values apply
            if ($objSource && ($isField || $hasResponsiveChildren)) {
                $shallReparse = true;
                // CTE and wrapper sources expose addResponsiveChildren via a subpalette that is only
                // present in the backend palette (the CTE selector/subpalette, or the per-record wrapper
                // injection in IncludesListener::addWrapperResponsiveChildrenSettings), so skip the
                // frontend-unavailable palette gate and use the row values. Only the module's own record
                // carries the field in its frontend palette ($isField), where the gate stays meaningful.
                $arrInnerClasses = $this->responsiveFrontendService->getAllInnerContainerClasses($objSource->row(), [], $objSource === $objCteModel ? 'tl_content' : 'tl_module', $objSource !== $objModuleModel);

                $objModule->Template->hasResponsiveChildren = $hasResponsiveChildren;
                $objModule->Template->innerClass = $arrInnerClasses;

                // The per-item column classes are applied on the reparse by
                // ParseTemplateListener::wrapListItems, which reads the children settings from the
                // template - and the template only carries the module's own row. Propagate the
                // resolved source's values so wrapper/CTE settings win there too. The dedicated
                // isResponsiveChildren flag (instead of widening isResponsive, which means "outer
                // column classes were applied") doubles as the reparse marker: it is only ever set
                // here, after the first parse, so the items are wrapped exactly once.
                $objModule->Template->addResponsiveChildren = $objSource->addResponsiveChildren;
                $objModule->Template->responsiveColsItems = $objSource->responsiveColsItems;
                $objModule->Template->isResponsiveChildren = true;
            }

            // HOOK: customize Template Data
            if (isset($GLOBALS['TL_HOOKS']['alterTemplateData']) && \is_array($GLOBALS['TL_HOOKS']['alterTemplateData'])) {
                foreach ($GLOBALS['TL_HOOKS']['alterTemplateData'] as $callback) {
                    System::importStatic($callback[0])->{$callback[1]}($objModule->Template, $objModuleModel, $objModule, $shallReparse);
                }
            }

            if ($shallReparse) {

                $strBuffer = $objModule->Template->parse();
            }
        }

        return $strBuffer;
    }
}
