<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Contao\System;


#[AsHook('parseTemplate')]
class ParseTemplateListener
{
    public function __invoke($objTemplate)
    {
        if ($objTemplate->typePrefix == 'mod_') {
            Controller::loadDataContainer('responsive');

            if (!$objTemplate->addResponsiveChildren || !$objTemplate->responsiveColsItems) return;

            //Checks if DCA-Palette has Settings for children (usually used for lists)
            if (!in_array($objTemplate->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']))) return;

            $strColumnClasses = implode(" ", System::getContainer()->get('kiwi.contao.responsive.frontend')->getColClasses($objTemplate->responsiveColsItems));
            $varChildren = ($objTemplate->{$GLOBALS['responsive']['tl_module']['includePalettes']['container'][$objTemplate->type]});

            if (is_array($varChildren) && $objTemplate->isResponsive) {
                foreach ($varChildren as &$varChild) {
                    $varChild = System::getContainer()->get('twig')->render('@KiwiResponsiveBase/list_child.html.twig', [
                        'baseClass' => $objTemplate->baseClass,
                        'class' => $strColumnClasses,
                        'item' => $varChild
                    ]);
                }
            }

            $objTemplate->{$GLOBALS['responsive']['tl_module']['includePalettes']['container'][$objTemplate->type]} = $varChildren;
        }
        return;
        if ($objTemplate->typePrefix == 'mod_') {}
        /**
         * fe_page is still a legacy template
         */
        elseif (substr($objTemplate->getName(), 0, 7) == 'fe_page') {
            $bootstrapClasses = [];

            $sidebars = [];
            if ($objTemplate->layout->cols == '3cl') {
                $sidebars[] = "left";
                $sidebars[] = "right";
            } elseif ($objTemplate->layout->cols == '2cll') {
                $sidebars[] = "left";
            } elseif ($objTemplate->layout->cols == '2clr') {
                $sidebars[] = "right";
            }

            $total = [];

            //Alle Breakpoints holen und nach Größe sortieren
            $arrBreakpoints = BootstrapListener::getBreakpoints();

            uasort($arrBreakpoints, function ($a, $b) {
                if ($a['size'] == $b['size']) {
                    return 0;
                }
                return ($a['size'] < $b['size']) ? -1 : 1;
            });

            // Standardwert für die Seitenspaltenbreite beim kleinsten Breakpoint.
            // Damit #main .col-Klassen bekommt, auch wenn es keine Seitenspalten gibt.
            $total[array_keys($arrBreakpoints)[0]] = 0;

            foreach ($sidebars as $sidebar) {
                $arrBreakpointSetting = StringUtil::deserialize($objTemplate->layout->{'col_' . $sidebar});

                $lastCol = 0;
                foreach ($arrBreakpoints as $arrBreakpoint) {
                    $col = $arrBreakpointSetting[$arrBreakpoint['name']];
                    if ($col == 'inherit') {
                        $col = $lastCol;
                    }

                    if (isset($total[$arrBreakpoint['name']])) {
                        $total[$arrBreakpoint['name']] += (int)$col;
                    } else {
                        $total[$arrBreakpoint['name']] = (int)$col;
                    }

                    if ($lastCol != $col || $col == 'none-only') {
                        if ($col == 'none-only') {
                            $base = 'd-';
                        } else {
                            $base = 'col-';
                        }
                        if ($arrBreakpoint['name'] == 'xs') {
                            $breakpoint = '';
                        } else {
                            $breakpoint = $arrBreakpoint['name'] . '-';
                        }
                        $bootstrapClasses[$sidebar][$arrBreakpoint['name']] = $base . $breakpoint . $col;
                    }
                    $lastCol = $col;
                }
            }
            $bootstrapClasses["left"][] = "order-first";

            // Jeweils mit dem vorherigen Wert vergleichen, damit Klassen, die sich vererben würden, nicht explizit gesetzt werden.
            $lastSize = null;
            foreach ($total as $breakpoint => $col) {
                $size = 12 - ($col % 12);
                if ($lastSize !== $size) {
                    if ($breakpoint == 'xs') {
                        $breakpoint = '';
                    } else {
                        $breakpoint .= '-';
                    }
                    $bootstrapClasses['main'][$breakpoint] = "col-" . $breakpoint . $size;
                }
                $lastSize = $size;
            }

            foreach ($bootstrapClasses as $column => $classes) {
                $objTemplate->{$column . 'Class'} = implode(' ', $classes);
            }

            // Container-Klassen für #header, #container und #footer setzen
            $arrSections = ['header', 'container', 'footer'];
            foreach ($arrSections as $section) {
                // $property = $section.'InsideClass';
                // if ($objTemplate->layout->{'bootstrap_'.$section.'_width'} == 'container-fluid') {
                //     $property = $section.'Class';
                // }
                $objTemplate->{$section . 'ContainerClass'} = $objTemplate->layout->{'bootstrap_' . $section . '_width'};
            }
        } /**
         * kept for BC with 4.13, custom elements and third-party bundles using legacy templates
         */
        else {
            if ($objTemplate->layout_content_alignment) {
                $alignClass = 'text-' . $objTemplate->layout_content_alignment;

                if (!in_array($alignClass, explode(' ', $objTemplate->class))) {
                    $objTemplate->class .= ' ' . $alignClass;
                }
            }
        }
    }
}
