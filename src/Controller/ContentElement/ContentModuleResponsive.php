<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Controller\ContentElement;

use Contao\ContentModule;
use Contao\Controller;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;

class ContentModuleResponsive extends ContentModule
{
    public function generate()
    {
        if ($this->isHidden()) {
            return '';
        }

        if (!$objModule = ModuleModel::findById($this->module)) {
            return '';
        }

        // Clone the model, so we do not modify the shared model in the registry
        $objModel = $objModule->cloneDetached();
        $cssID = StringUtil::deserialize($objModel->cssID, true);

        // Override the CSS ID (see #305)
        if (!empty($this->cssID[0])) {
            $cssID[0] = $this->cssID[0];
        }

        // Merge the CSS classes (see #6011)
        if (!empty($this->cssID[1])) {
            $cssID[1] = trim(($cssID[1] ?? '') . ' ' . $this->cssID[1]);
        }

        $objModel->cssID = $cssID;
        $objModel->cte = $this;

        // Tag the content element (see #2137)
        if ($this->objModel !== null) {
            System::getContainer()->get('contao.cache.entity_tags')->tagWithModelInstance($this->objModel);
        }

        return Controller::getFrontendModule($objModel, $this->strColumn);
    }
}