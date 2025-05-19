<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\System;


#[AsHook('parseWidget')]
class ParseWidgetListener
{
    public function __invoke($strBuffer, $objWidget)
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if (!System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $objWidget->rowClasses .= implode(" ", System::getContainer()->get('kiwi.contao.responsive.frontend')->getAllResponsiveClasses($objWidget));
            return $objWidget->inherit();
        }
        return $strBuffer;
    }
}