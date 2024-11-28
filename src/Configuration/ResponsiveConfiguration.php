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
    public function getCols(): array
    {
        return array_keys($this->arrCols);
    }

    #[AsCallback('tl_content', 'fields.responsive_offsets.options')]
    public function getOffsets(): array
    {
        return array_keys($this->arrOffsets);
    }

    #[AsCallback('tl_article', 'fields.responsive_spacing-top.options')]
    #[AsCallback('tl_article', 'fields.responsive_spacing-bottom.options')]
    public function getSpacings(): array
    {
        return array_keys($this->arrSpacings);
    }

    #[AsCallback(table: 'tl_article', target: 'config.onload')]
    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function getDefaults(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['responsiveCols']['default'] = (new $GLOBALS['responsive'])->arrColsDefaults;
        $GLOBALS['TL_DCA']['tl_content']['fields']['responsiveOffsets']['default'] = (new $GLOBALS['responsive'])->arrOffsetsDefaults;
    }

    public function getContainerSizes():array
    {
        return $this->arrContainerSizes;
    }
}