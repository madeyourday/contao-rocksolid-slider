<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test;

use MadeYourDay\RockSolidSlider\RockSolidSliderBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the RockSolidSliderBundle class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class RockSolidSliderBundleTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $bundle = new RockSolidSliderBundle();

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\RockSolidSliderBundle', $bundle);
    }

    /**
     * Tests the getContainerExtension() method.
     */
    public function testGetContainerExtension()
    {
        $bundle = new RockSolidSliderBundle();

        $this->assertInstanceOf(
            'MadeYourDay\RockSolidSlider\DependencyInjection\RockSolidSliderExtension',
            $bundle->getContainerExtension()
        );
    }
}
