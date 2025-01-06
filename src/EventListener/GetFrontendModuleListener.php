<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Hybrid;
use Contao\ModuleModel;
use Contao\System;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;

#[AsHook('getFrontendModule')]
class GetFrontendModuleListener
{
    public function __invoke(ModuleModel $objModuleModel, string $strBuffer, object $objModule): string
    {
        $shallReparse = false;

        $objFrontendModule = $objModule instanceof Hybrid ? $objModule->getParent() : $objModuleModel;
        $objResponsiveFrontendService = System::getContainer()->get('kiwi.contao.responsive.frontend');
        $objModule->Template->baseClass = $objModule->typePrefix . $objModule->type;

        $isField = PaletteManipulatorExtended::create()->hasField($objFrontendModule->type, 'tl_module', 'addResponsive');

        if ($objFrontendModule->addResponsive && $isField) {
            $shallReparse = true;
            $arrClasses = $objResponsiveFrontendService->getAllResponsiveClasses($objFrontendModule->row());

            $strResponsiveClasses = implode(' ', $arrClasses);

            if ($objModule->Template) {
                $objModule->Template->isResponsive = true;
                $objModule->Template->class = trim($objModule->Template->class . ' ' . $strResponsiveClasses);
            }
        }

        $isField = PaletteManipulatorExtended::create()->hasField($objFrontendModule->type, 'tl_module', 'addResponsiveChildren');

        if ($objFrontendModule->addResponsiveChildren && $isField) {
            $shallReparse = true;
            $arrInnerClasses = $objResponsiveFrontendService->getAllInnerContainerClasses($objFrontendModule->row());
            $hasResponsiveChildren = in_array($objFrontendModule->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']));

            if ($objModule->Template) {
                $objModule->Template->hasResponsiveChildren = $hasResponsiveChildren;
                $objModule->Template->innerClass = $arrInnerClasses;
            }
        }

        if ($shallReparse) {
            // HOOK: customize Template Data
            if (isset($GLOBALS['TL_HOOKS']['alterTemplateData']) && \is_array($GLOBALS['TL_HOOKS']['alterTemplateData'])) {
                foreach ($GLOBALS['TL_HOOKS']['alterTemplateData'] as $callback) {
                    System::importStatic($callback[0])->{$callback[1]}($objModule->Template, $objFrontendModule, $objModule);
                }
            }

            $strBuffer = $objModule->Template->parse();
        }

        return $strBuffer;
    }
}
