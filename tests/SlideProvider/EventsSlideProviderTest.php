<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test\SlideProvider;

use Contao\CoreBundle\Framework\Adapter;
use Contao\ModuleModel;
use MadeYourDay\RockSolidSlider\SlideProvider\EventsSlideProvider;
use MadeYourDay\RockSolidSlider\SliderContent;
use PHPUnit\Framework\TestCase;

/**
 * Tests the EventsSlideProvider class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class EventsSlideProviderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\EventsSlideProvider::__construct()
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\EventsSlideProvider::getName()
     */
    public function testInstantiation()
    {
        $provider = new EventsSlideProvider(
            $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\SlideProvider\EventsSlideProvider', $provider);
        $this->assertSame('rsts_events', $provider->getName());
    }

    /**
     * Test adding providers.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\EventsSlideProvider::__construct()
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\EventsSlideProvider::process()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::addSlides()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::getSlides()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::hasSlides()
     */
    public function testProcess()
    {
        $bridge= $this
            ->getMockBuilder(\stdClass::class)
            ->setMethods(['getSlides'])
            ->disableOriginalConstructor()
            ->getMock();
        $bridge->expects($this->once())->method('getSlides')->willReturn([['text' => 'content']]);

        $model = $this->getMockBuilder(ModuleModel::class)->disableOriginalConstructor()->getMock();

        $adapter  = $this
            ->getMockBuilder(Adapter::class)
            ->setMethods(['findByPk'])
            ->disableOriginalConstructor()
            ->getMock();
        $adapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn($model);

        $provider = $this
            ->getMockBuilder(EventsSlideProvider::class)
            ->setMethods(['getBridge'])
            ->setConstructorArgs([$adapter])
            ->getMock();
        $provider
            ->expects($this->once())
            ->method('getBridge')
            ->with($model, 'main')
            ->willReturn($bridge);

        $content = new SliderContent();

        /** @@var EventsSlideProvider $provider */
        $provider->process(['id' => 42, 'slider-column' => 'main'], $content);

        $this->assertTrue($content->hasSlides());
        $this->assertSame([['text' => 'content']], $content->getSlides());
    }
}
