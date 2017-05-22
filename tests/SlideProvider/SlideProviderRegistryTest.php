<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test\SlideProvider;

use MadeYourDay\RockSolidSlider\SlideProvider\SlideProviderInterface;
use MadeYourDay\RockSolidSlider\SlideProvider\SlideProviderRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests the SlideProviderRegistry class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class SlideProviderRegistryTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $registry = new SlideProviderRegistry();

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\SlideProvider\SlideProviderRegistry', $registry);
    }

    /**
     * Test adding providers.
     *
     * @return void
     */
    public function testAddProvider()
    {
        $registry = new SlideProviderRegistry();

        $this->assertFalse($registry->hasProvider('test-provider'));

        $mock = $this->getMockForAbstractClass(SlideProviderInterface::class);
        $mock->method('getName')->willReturn('test-provider');

        $registry->addProvider($mock);

        $this->assertTrue($registry->hasProvider('test-provider'));
        $this->assertSame($mock, $registry->getProvider('test-provider'));
    }

    /**
     * Test that the instance get's populated with constructor arguments.
     *
     * @return void
     */
    public function initWithProviders()
    {
        $mock1 = $this->getMockForAbstractClass(SlideProviderInterface::class);
        $mock1->method('getName')->willReturn('test-provider1');
        $mock2 = $this->getMockForAbstractClass(SlideProviderInterface::class);
        $mock2->method('getName')->willReturn('test-provider2');

        $registry = new SlideProviderRegistry([$mock1, $mock2]);

        $this->assertTrue($registry->hasProvider('test-provider1'));
        $this->assertSame($mock1, $registry->getProvider('test-provider1'));
        $this->assertTrue($registry->hasProvider('test-provider2'));
        $this->assertSame($mock2, $registry->getProvider('test-provider2'));
    }
}
