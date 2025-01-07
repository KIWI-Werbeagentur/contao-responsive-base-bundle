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

            $objFrontendModule = $objModule instanceof Hybrid ? $objModule->getParent() : $objModuleModel;
            $objModule->Template->baseClass = $objModule->typePrefix . $objModule->type;

            //Responsive
            $isField = PaletteManipulatorExtended::create()->hasField($objFrontendModule->type, 'tl_module', 'addResponsive');
            $isResponsive = $this->checkFieldValue('addResponsive', $objFrontendModule, $objModule, $objModuleModel);

            if ($isResponsive && $isField) {
                $shallReparse = true;
                $arrClasses = $this->getClasses('getAllResponsiveClasses', $objFrontendModule, $objModule, $objModuleModel);

                $strResponsiveClasses = implode(' ', $arrClasses);

                $objModule->Template->isResponsive = true;
                $objModule->Template->class = trim($objModule->Template->class . ' ' . $strResponsiveClasses);
            }

            //Responsive Children
            $isField = PaletteManipulatorExtended::create()->hasField($objFrontendModule->type, 'tl_module', 'addResponsiveChildren');
            $hasResponsiveChildren = $this->checkFieldValue('addResponsiveChildren', $objFrontendModule, $objModule, $objModuleModel);

            if ($hasResponsiveChildren && $isField) {
                $shallReparse = true;
                $arrInnerClasses = $this->getClasses('getAllInnerContainerClasses', $objFrontendModule, $objModule, $objModuleModel);
                $hasResponsiveChildren = in_array($objFrontendModule->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']));

                $objModule->Template->hasResponsiveChildren = $hasResponsiveChildren;
                $objModule->Template->innerClass = $arrInnerClasses;
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
        }

        return $strBuffer;
    }

    public function checkFieldValue($strField, $objFrontendModule, $objModule, $objModuleModel)
    {
        if ($objModule instanceof Hybrid && $objModuleModel->cte) {
            return $objModuleModel->cte->getModel()->{$strField};
        } elseif ($objFrontendModule->cte) {
            return $objFrontendModule->cte->getModel()->{$strField};
        } else {
            return $objFrontendModule->{$strField};
        }
    }

    public function getClasses($strMethod, $objFrontendModule, $objModule, $objModuleModel)
    {
        if ($objModule instanceof Hybrid && $objModuleModel->cte) {
            return $this->responsiveFrontendService->{$strMethod}($objModuleModel->cte->getModel()->row());
        } elseif ($objFrontendModule->cte) {
            return $this->responsiveFrontendService->{$strMethod}($objFrontendModule->cte->getModel()->row());
        } else {
            return $this->responsiveFrontendService->{$strMethod}($objFrontendModule->row());
        }
    }
}
