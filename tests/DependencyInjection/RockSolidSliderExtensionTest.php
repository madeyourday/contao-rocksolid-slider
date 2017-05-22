<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test\DependencyInjection;

use MadeYourDay\RockSolidSlider\DependencyInjection\RockSolidSliderExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the RockSolidSliderExtension class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class RockSolidSliderExtensionTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new RockSolidSliderExtension();

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\DependencyInjection\RockSolidSliderExtension', $extension);
    }

    /**
     * Tests adding the bundle services to the container.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder();

        $extension = new RockSolidSliderExtension();
        $extension->load([], $container);

        $this->assertSame(
            realpath(__DIR__ . '/../../src/Resources/config/services.yml'),
            (string) $container->getResources()[0]
        );
        $this->assertTrue($container->has('madeyourday.rocksolid_slider.slideproviders'));
    }
}
