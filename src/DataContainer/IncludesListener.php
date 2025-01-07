<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;

class IncludesListener
{
    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function checkForResponsiveSettings(DataContainer $objDca): void
    {
        $strType = $objDca->getCurrentRecord()['type'] ?? '';

        if ($strType && $GLOBALS['TL_CTE']['includes'][$strType] ?? false) {
            $intTarget = $objDca->getCurrentRecord()[$strType];
            $strTargetClass = $GLOBALS['TL_MODELS']["tl_$strType"] ?? null;

            if(!$strTargetClass) return;
            $objModel = $strTargetClass::findByPk($intTarget);

            if(!$objModel->type || in_array($objModel->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']))){
                PaletteManipulatorExtended::create()
                    ->addLegend('items_legend', ['protected_legend','expert_legend'], PaletteManipulator::POSITION_BEFORE)
                    ->addField(['addResponsiveChildren'], 'items_legend', PaletteManipulator::POSITION_APPEND)
                    ->applyToPalettes([$strType], 'tl_content');
            }
        }
    }
}