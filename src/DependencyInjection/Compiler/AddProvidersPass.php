<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds the providers to the registry.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class AddProvidersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('madeyourday.rocksolid_slider.slideproviders');
        $arguments  = $definition->getArguments();

        // Collect provider services.
        $arguments[0] = array_merge(count($arguments) > 0 ? $arguments[0] : [], $this->getProviders($container));

        $definition->setArguments($arguments);
    }

    /**
     * Returns the available provider implementations.
     *
     * @return Reference[]
     */
    private function getProviders(ContainerBuilder $container)
    {
        $services = $container->findTaggedServiceIds('madeyourday.rocksolid_slider.slideprovider');
        $result   = [];
        foreach (array_keys($services) as $service) {
            $result[] = new Reference($service);
        }

        return $result;
    }
}
