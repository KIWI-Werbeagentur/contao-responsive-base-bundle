<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;

class IncludesListener
{
    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function addResponsiveChildrenSettings(DataContainer $objDca): void
    {
        //Bug: Loads content with article-id when opening content-overview of article --> leads to permission errors
        if(Input::get("do") == 'article' && Input::get("table") == 'tl_content' && !Input::get("act") == 'edit') return;

        $strType = $objDca->getCurrentRecord()['type'] ?? '';
        $strTargetClass = $GLOBALS['TL_MODELS']["tl_$strType"] ?? null;
        if (!$strTargetClass) return;

        //Label Children
        if (in_array($objDca->getCurrentRecord()['type'], array_keys($GLOBALS['TL_CTE']['includes']))) {
            $objInclude = $strTargetClass::findByPk($objDca->getCurrentRecord()[$objDca->getCurrentRecord()['type']]) ?? null;

            if($objInclude){
                $arrClasses = System::getContainer()->get('kiwi.contao.responsive.frontend')->getAllInnerContainerClasses($objInclude->row());
                $GLOBALS['TL_DCA']['tl_content']['fields']['addResponsiveChildren']['label'] = &$GLOBALS['TL_LANG']['responsive']['overwriteResponsiveChildren'];
                $GLOBALS['TL_DCA']['tl_content']['fields']['addResponsiveChildren']['label'][1] = sprintf($GLOBALS['TL_LANG']['responsive']['overwriteResponsiveChildren'][1] ?? "", "(".implode(" ", $arrClasses).")");
            }
        }

        //Add Childrens Legend
        if ($strType && $GLOBALS['TL_CTE']['includes'][$strType] ?? false) {
            $intTarget = $objDca->getCurrentRecord()[$strType];

            $objModel = $strTargetClass::findByPk($intTarget);

            if ($objModel && in_array($objModel->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']))) {
                PaletteManipulatorExtended::create()
                    ->addLegend('items_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                    ->addField(['addResponsiveChildren'], 'items_legend', PaletteManipulator::POSITION_APPEND)
                    ->applyToPalettes([$strType], 'tl_content');
            }
        }
    }
}