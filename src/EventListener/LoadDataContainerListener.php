<?php

namespace Kiwi\Contao\ResponsiveBase\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    public function __invoke(string $table): void
    {
        if(!($GLOBALS['responsive'] ?? false)) return;

        //Copy DCA-entry for responsive widgets to avoid exception in Ajax requests (e.g. fileTree)
        if (!($GLOBALS['TL_DCA'][$table]['fields'] ?? false)) {
            return;
        }
        foreach ($GLOBALS['TL_DCA'][$table]['fields'] as $strField => $arrField) {
            if (($arrField['inputType'] ?? false) == "responsive") {
                foreach ((new $GLOBALS['responsive'])->arrBreakpoints as $arrBreakpoint) {
                    if($arrBreakpoint['modifier']){
                        $GLOBALS['TL_DCA'][$table]['fields'][$strField . $arrBreakpoint['modifier']] = $GLOBALS['TL_DCA'][$table]['fields'][$strField];
                        unset($GLOBALS['TL_DCA'][$table]['fields'][$strField . $arrBreakpoint['modifier']]['sql']);
                    }
                }
            }
        }

    }
}
