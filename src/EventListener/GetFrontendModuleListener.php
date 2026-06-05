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

            // When inserted via the "module" content element (CTE), source the responsive
            // settings from the content element record instead of the module's own.
            $objCteModel = $objModuleModel->cte?->getModel() ?: null;
            $objTargetWithClasses = $objCteModel ?? $objModuleModel;
            $objModule->Template->baseClass = $objModule->typePrefix . $objModule->type;

            //Responsive Module Settings: the outermost wrapper offering and enabling them wins, otherwise the CTE/module logic applies unchanged
            // Check field availability against the source's own table (tl_content for a CTE, tl_module otherwise)
            $isField = PaletteManipulatorExtended::create()->hasField($objTargetWithClasses->type, $objCteModel ? 'tl_content' : 'tl_module', 'addResponsive');
            $objSource = $this->classResolver->getWrapperSource($arrWrappers, 'addResponsive');

            if (!$objSource && $objTargetWithClasses->addResponsive && $isField) {
                $objSource = $objTargetWithClasses;
            }

            if ($objSource) {
                $shallReparse = true;
                $arrClasses = $this->responsiveFrontendService->getAllResponsiveClasses($objSource->row());

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
                $arrInnerClasses = $this->responsiveFrontendService->getAllInnerContainerClasses($objSource->row());

                $objModule->Template->hasResponsiveChildren = $hasResponsiveChildren;
                $objModule->Template->innerClass = $arrInnerClasses;
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
