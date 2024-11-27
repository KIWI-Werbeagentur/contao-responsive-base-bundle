<?php

use Kiwi\Contao\ResponsiveBase\Widgets\Backend\OptionalResponsiveWidget;
use Kiwi\Contao\ResponsiveBase\Widgets\Backend\ResponsiveWidget;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

$GLOBALS['BE_FFL']['responsive'] = ResponsiveWidget::class;
$GLOBALS['BE_FFL']['optionalResponsive'] = OptionalResponsiveWidget::class;

if (System::getContainer()->get('contao.routing.scope_matcher')
    ->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))
)
{
    $GLOBALS['TL_CSS'][] = 'bundles/kiwiresponsivebase/responsive.css';
}