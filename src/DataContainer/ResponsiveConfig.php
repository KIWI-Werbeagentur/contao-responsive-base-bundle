<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;

class ResponsiveConfig
{
    #[AsCallback(table: 'tl_module', target: 'config.onbeforesubmit')]
    public function unsetResponsiveWhenNotInPalette($arrValues, DataContainer $objDca): array
    {
        foreach (['addResponsive', 'addResponsiveChildren'] as $strResponsiveField) {
            $isField = PaletteManipulatorExtended::create()->hasField($objDca->activeRecord->type, $objDca->table, $strResponsiveField);

            if (!$isField) {
                $arrValues[$strResponsiveField] = 0;
            }
        }
        return $arrValues;
    }
}