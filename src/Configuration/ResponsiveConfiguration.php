<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Configuration;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

abstract class ResponsiveConfiguration
{
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            default:
                $this->{$strKey} = $varValue;
                break;
        }
    }

    public function __get($strKey)
    {
        return $this->$strKey;
    }

    #[AsCallback('tl_content', 'fields.responsive_cols.options')]
    public function getCols()
    {
        return array_keys($this->arrCols);
    }

    #[AsCallback('tl_content', 'fields.responsive_offsets.options')]
    public function getOffsets()
    {
        return array_keys($this->arrOffsets);
    }

    #[AsCallback('tl_article', 'fields.responsive_spacing-top.options')]
    #[AsCallback('tl_article', 'fields.responsive_spacing-bottom.options')]
    public function getSpacings()
    {
        return array_keys($this->arrSpacings);
    }

    public function getDefaults(){
        $GLOBALS['TL_DCA']['tl_content']['fields']['responsiveCols']['default'] = (new $GLOBALS['responsive'])->arrColsDefaults;
        $GLOBALS['TL_DCA']['tl_content']['fields']['responsiveOffsets']['default'] = (new $GLOBALS['responsive'])->arrOffsetsDefaults;
    }
}