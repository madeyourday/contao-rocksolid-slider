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
     *
     * @coversNothing
     */
    public function testInstantiation()
    {
        $bundle = new RockSolidSliderBundle();

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\RockSolidSliderBundle', $bundle);
    }

    /**
     * Tests the getContainerExtension() method.
     *
     * @covers \MadeYourDay\RockSolidSlider\RockSolidSliderBundle::getContainerExtension()
     */
    public function testGetContainerExtension()
    {
        $bundle = new RockSolidSliderBundle();

        $this->assertInstanceOf(
            'MadeYourDay\RockSolidSlider\DependencyInjection\RockSolidSliderExtension',
            $bundle->getContainerExtension()
        );
    }

    /**
     * Tests the build() method.
     *
     * @covers \MadeYourDay\RockSolidSlider\RockSolidSliderBundle::build()
     */
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $bundle = new RockSolidSliderBundle();
        $bundle->build($container);

        $classes = [];

        foreach ($container->getCompilerPassConfig()->getBeforeOptimizationPasses() as $pass) {
            $reflection = new \ReflectionClass($pass);
            $classes[] = $reflection->getName();
        }

        $this->assertEquals(
            [
                'MadeYourDay\RockSolidSlider\DependencyInjection\Compiler\AddProvidersPass',
            ],
            $classes
        );
    }
}
