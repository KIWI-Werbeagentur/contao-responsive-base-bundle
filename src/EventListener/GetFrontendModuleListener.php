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
        $objFrontendModule = $objModule instanceof Hybrid ? $objModule->getParent() : $objModuleModel;

        $isField = PaletteManipulatorExtended::create()->hasField($objFrontendModule->type, 'tl_module', 'addResponsive');

        if ($objFrontendModule->addResponsive && $isField) {
            $arrClasses = System::getContainer()->get('kiwi.contao.responsive.frontend')->getAllResponsiveClasses($objFrontendModule->row());

            $strBootstrapClasses = implode(' ', $arrClasses);

            if ($strBootstrapClasses) {
                if ($objModule->Template) {
                    $objModule->Template->isResponsive = true;
                    $objModule->Template->baseClass = $objModule->typePrefix . $objModule->type;
                    $objModule->Template->class = trim($objModule->Template->class . ' ' . $strBootstrapClasses);
                    $strBuffer = $objModule->Template->parse();
                }
            }
        }

        return $strBuffer;
    }
}
