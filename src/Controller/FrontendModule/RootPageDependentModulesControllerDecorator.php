<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\RootPageDependentModulesController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tags the root page dependent child module with an "includedVia" backref to this
 * wrapper, so GetFrontendModuleListener can apply the wrapper's responsive settings
 * to the child template - the same convention the language/page dependent module
 * wrappers use (see Kiwi\Contao\CoreBundle\FrontendModule\IncludesModuleTrait).
 *
 * Nesting is handled by the listener walking the backref upward; this wrapper's own
 * model may already carry an "includedVia" set by an outer wrapper.
 *
 * Note: this relies on the default inline fragment renderer; with ESI the tag
 * would not survive the subrequest.
 */
class RootPageDependentModulesControllerDecorator
{
    public function __construct(
        protected RootPageDependentModulesController $inner,
        protected ContaoFramework $framework,
        protected PageFinder $pageFinder,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null): Response
    {
        $objChild = $this->tagChildModule($request, $model);

        try {
            return ($this->inner)($request, $model, $section, $classes);
        } finally {
            // We tag the shared registry instance, so we are responsible for clearing it: if the
            // inner controller does not render the child (invisible element, missing class) the
            // getFrontendModule hook never fires to consume the backref, and it would otherwise
            // leak onto a later, unrelated render of the same module.
            if ($objChild) {
                $objChild->includedVia = null;
            }
        }
    }

    protected function tagChildModule(Request $request, ModuleModel $model): ?ModuleModel
    {
        // Skip resolving the child unless this wrapper imposes settings or was itself included
        if (!isset($model->includedVia) && !$model->addResponsive && !$model->addResponsiveChildren) {
            return null;
        }

        if (!$objPage = $this->pageFinder->getCurrentPage($request)) {
            return null;
        }

        $arrModules = StringUtil::deserialize($model->rootPageDependentModules, true);

        if (!($arrModules[$objPage->rootId] ?? false)) {
            return null;
        }

        if (!$objChild = $this->framework->getAdapter(ModuleModel::class)->findById($arrModules[$objPage->rootId])) {
            return null;
        }

        // findById returns the registry instance the inner controller will render, so the
        // backref is visible in the getFrontendModule hook (and in this decorator again,
        // when the child is another root page dependent modules wrapper)
        $objChild->includedVia = $model;

        return $objChild;
    }
}
