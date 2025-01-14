<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Hybrid;
use Contao\ModuleModel;
use Contao\System;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;

#[AsHook('getFrontendModule')]
class GetFrontendModuleListener
{
    public function __construct(protected ResponsiveFrontendService $responsiveFrontendService)
    {
    }

    public function __invoke(ModuleModel $objModuleModel, string $strBuffer, object $objModule): string
    {
        if ($objModule->Template) {
            $shallReparse = false;

            // Ignore responsive classes from module when it is inserted via CTE
            $objTargetWithClasses = ($objModuleModel->cte?->getModel() ?? false) ? $objModuleModel->cte->getModel() : $objModuleModel;
            $objModule->Template->baseClass = $objModule->typePrefix . $objModule->type;

            //Responsive Module Settings
            $isField = PaletteManipulatorExtended::create()->hasField($objModuleModel->type, 'tl_module', 'addResponsive');

            if (!($objTargetWithClasses->addResponsive === 0) && $isField) {
                $shallReparse = true;
                $arrClasses = $this->responsiveFrontendService->getAllResponsiveClasses($objTargetWithClasses->row());

                $strResponsiveClasses = implode(' ', $arrClasses);

                $objModule->Template->isResponsive = true;
                $objModule->Template->class = trim($objModule->Template->class . ' ' . $strResponsiveClasses);
            }

            //Responsive Children Settings
            $isField = PaletteManipulatorExtended::create()->hasField($objModuleModel->type, 'tl_module', 'addResponsiveChildren');
            $objTargetWithClasses = $objTargetWithClasses->addResponsiveChildren ? $objTargetWithClasses : $objModuleModel;

            if (!($objTargetWithClasses->addResponsiveChildren === 0) && $isField) {
                $shallReparse = true;
                $arrInnerClasses = $this->responsiveFrontendService->getAllInnerContainerClasses($objTargetWithClasses->row());
                $hasResponsiveChildren = in_array($objModuleModel->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']));

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
