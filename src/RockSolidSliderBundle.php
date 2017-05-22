<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider;

use MadeYourDay\RockSolidSlider\DependencyInjection\Compiler\AddProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Configures the RockSolid Slider bundle.
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class RockSolidSliderBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddProvidersPass());
    }
}
