<?php

use Kiwi\Contao\ResponsiveBaseBundle\Controller\ContentElement\ContentModuleResponsive;
use Kiwi\Contao\ResponsiveBaseBundle\Widget\Backend\OptionalResponsiveWidget;
use Kiwi\Contao\ResponsiveBaseBundle\Widget\Backend\ResponsiveWidget;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

$GLOBALS['responsive']['tl_content']['excludePalettes']['column'] = ['default', 'html', 'unfiltered_html', 'accordionStop', 'sliderStop', 'code', 'alias', 'element_group'];
$GLOBALS['responsive']['tl_form_field']['excludePalettes']['column'] = ['default', 'html', 'fieldsetStart', 'fieldsetStop'];
$GLOBALS['responsive']['tl_module']['excludePalettes']['column'] = ['default', 'html'];
$GLOBALS['responsive']['tl_module']['includePalettes']['container'] = ['newslist'=>'articles', 'eventlist'=>'events', 'vacancieslist'=>'vacancies', 'form'=>'form_fields'];

$GLOBALS['TL_CTE']['includes']['module'] = ContentModuleResponsive::class;

$GLOBALS['BE_FFL']['responsive'] = ResponsiveWidget::class;
$GLOBALS['BE_FFL']['optionalResponsive'] = OptionalResponsiveWidget::class;

if (System::getContainer()->get('contao.routing.scope_matcher')
    ->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))
) {
    $GLOBALS['TL_CSS'][] = 'bundles/kiwiresponsivebase/responsive.css';
}