<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;

#[AsHook('resolveValue')]
class ResolveValue
{
    public function __invoke($arrData, $strMapping, $objFrontendService, $strField)
    {
        $arrStyles = StringUtil::deserialize($arrData[$strField], true);
        $arrReturn = [];

        foreach (array_reverse((new $GLOBALS['responsive']['config']())->arrBreakpoints) ?? [] as $strBreakpoint => $arrBreakpoint) {
            if ($arrStyles[$strBreakpoint] ?? false) {
                $arrReturn[] = $objFrontendService->getClasses($arrStyles[$strBreakpoint], $strMapping, $strField, $strBreakpoint, $arrBreakpoints);
                $arrBreakpoints = [];
            } else {
                $arrBreakpoints[] = $strBreakpoint;
            }
        }

        return $arrReturn;
    }
}
