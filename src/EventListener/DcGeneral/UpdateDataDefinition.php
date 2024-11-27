<?php

namespace Kiwi\Contao\ResponsiveBase\EventListener\DcGeneral;

use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\DefaultProperty;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PropertiesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;

/**
 * Class UpdateDataDefinition
 */
class UpdateDataDefinition
{
    /**
     * Add all fields from the MCW to the DCA. This is needed for some fields, because other components need this
     * to create the widget/view etc.
     *
     * @param BuildDataDefinitionEvent $event The event to process.
     *
     * @return void
     */
    public function addResponsiveFields(BuildDataDefinitionEvent $event)
    {
        // Get the container and all properties.
        $container  = $event->getContainer();
        $properties = $container->getPropertiesDefinition();

        /** @var DefaultProperty $property */
        foreach ($properties as $property) {
            // Only run for mcw.
            if ('responsive' !== $property->getWidgetType()) {
                continue;
            }

            // Get the extra and make an own field from it.
            $extra = $property->getExtra();

            // If we have no data here, go to the next.
            if (!(empty($extra['responsiveInputType']) || !is_array($extra['responsiveInputType']))) {
                continue;
            }

            $this->addPropertyToDefinition($extra, $property, $properties);
        }
    }
}
