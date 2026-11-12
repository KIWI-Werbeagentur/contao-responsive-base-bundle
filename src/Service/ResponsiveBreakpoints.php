<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

/**
 * Provides the configured breakpoints ($GLOBALS['responsive']['config']) to the frontend,
 * as CSS custom properties and as JSON for responsive.js.
 */
class ResponsiveBreakpoints
{
    public function __construct(private readonly ResponsiveFrontendService $responsiveFrontendService)
    {
    }

    /**
     * The breakpoints as JSON, safe to be embedded in an inline <script>.
     */
    public function getBreakpointsJson(): string
    {
        return json_encode($this->getBreakpoints(), JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }

    /**
     * Provides the properties that responsive.js used to set on the <body> only after page load:
     * one --responsive-<prop> per property of the active breakpoint, plus --responsive-key. As
     * responsive.js did, empty values are left out.
     */
    public function getCustomPropertiesCss(): string
    {
        $strCss = '';

        foreach ($this->getBreakpoints() as $strKey => $arrBreakpoint) {
            $arrProperties = ['key' => $strKey, ...$arrBreakpoint];
            $strDeclarations = '';

            foreach ($arrProperties as $strProperty => $varValue) {
                // Only plain tokens like "sm", "-sm", "576" or "540px" (no CSS injection via the config)
                if (!\is_scalar($varValue) || !preg_match('/^[\w%.-]+$/', (string) $varValue) || !preg_match('/^[\w-]+$/', (string) $strProperty)) {
                    continue;
                }

                $strDeclarations .= '--responsive-'.$strProperty.':'.$varValue.';';
            }

            $strRule = 'body{'.$strDeclarations.'}';
            $intMinWidth = (int) ($arrBreakpoint['breakpoint'] ?? 0);

            $strCss .= $intMinWidth > 0 ? '@media (min-width:'.$intMinWidth.'px){'.$strRule.'}' : $strRule;
        }

        return $strCss;
    }

    private function getBreakpoints(): array
    {
        // The configuration shared within the request (and reset between requests)
        return $this->responsiveFrontendService->getConfig()->arrBreakpoints;
    }
}
