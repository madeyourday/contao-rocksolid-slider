<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test\DependencyInjection\Compiler;

use MadeYourDay\RockSolidSlider\DependencyInjection\Compiler\AddProvidersPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests the AddProvidersPass class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class AddProvidersPassTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pass = new AddProvidersPass();

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\DependencyInjection\Compiler\AddProvidersPass', $pass);
    }

    /**
     * Test the processing.
     *
     * @return void
     */
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $mock1 = new Reference('foo');

        $container->setDefinition(
            'madeyourday.rocksolid_slider.slideproviders',
            $registry = new Definition('', [[$mock1]])
        );

        $tagged1 = new Definition('Tagged1');
        $tagged1->addTag('madeyourday.rocksolid_slider.slideprovider');
        $tagged2 = new Definition('Tagged1');
        $tagged2->addTag('madeyourday.rocksolid_slider.slideprovider');

        $container->setDefinition('tagged1', $tagged1);
        $container->setDefinition('tagged2', $tagged2);

        $pass = new AddProvidersPass();

        $pass->process($container);

        $ids = [];
        foreach ($registry->getArguments()[0] as $argument) {
            $this->assertInstanceOf(Reference::class, $argument);
            $ids[] = (string) $argument;
        }

        $this->assertEquals(['foo', 'tagged1', 'tagged2'], $ids);
    }

    /**
     * Test the processing when no arguments have been provided so far.
     *
     * @return void
     */
    public function testProcessWithoutArguments()
    {
        $container = new ContainerBuilder();

        $container->setDefinition(
            'madeyourday.rocksolid_slider.slideproviders',
            $registry = new Definition('')
        );

        $tagged1 = new Definition('Tagged1');
        $tagged1->addTag('madeyourday.rocksolid_slider.slideprovider');
        $tagged2 = new Definition('Tagged1');
        $tagged2->addTag('madeyourday.rocksolid_slider.slideprovider');

        $container->setDefinition('tagged1', $tagged1);
        $container->setDefinition('tagged2', $tagged2);

        $pass = new AddProvidersPass();

        $pass->process($container);

        $ids = [];
        foreach ($registry->getArguments()[0] as $argument) {
            $this->assertInstanceOf(Reference::class, $argument);
            $ids[] = (string) $argument;
        }

        $this->assertEquals(['tagged1', 'tagged2'], $ids);
    }
}
