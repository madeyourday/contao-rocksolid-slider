<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test\SlideProvider;

use Contao\CoreBundle\Framework\Adapter;
use MadeYourDay\RockSolidSlider\Helper\FileHelper;
use MadeYourDay\RockSolidSlider\SlideProvider\DefaultSlideProvider;
use MadeYourDay\RockSolidSlider\SliderContent;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DefaultSlideProvider class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class DefaultSlideProviderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\DefaultSlideProvider::__construct()
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\DefaultSlideProvider::getName()
     */
    public function testInstantiation()
    {
        $filesHelper          = $this->getMockBuilder(FileHelper::class)->disableOriginalConstructor()->getMock();
        $sliderModelAdapter   = $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock();
        $slideModelAdapter    = $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock();
        $frontendAdapter      = $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock();

        $provider = new DefaultSlideProvider(
            $filesHelper,
            $sliderModelAdapter,
            $slideModelAdapter,
            $frontendAdapter
        );

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\SlideProvider\DefaultSlideProvider', $provider);
        $this->assertSame('rsts_default', $provider->getName());
    }

    /**
     * Test adding providers.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\DefaultSlideProvider::__construct()
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\DefaultSlideProvider::process()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::addFiles()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::getFiles()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::hasFiles()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::hasSlides()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::getSlides()
     */
    public function testProcessWithoutSlider()
    {
        $filesHelper          = $this->getMockBuilder(FileHelper::class)->disableOriginalConstructor()->getMock();
        $sliderModelAdapter   = $this->getMockBuilder(Adapter::class)->setMethods(['findByPk'])->disableOriginalConstructor()->getMock();
        $slideModelAdapter    = $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock();
        $frontendAdapter      = $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock();

        $sliderModelAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->willReturn(null);

        $provider = new DefaultSlideProvider(
            $filesHelper,
            $sliderModelAdapter,
            $slideModelAdapter,
            $frontendAdapter
        );
        $content  = new SliderContent();

        $provider->process(['rsts_id' => 42], $content);

        $this->assertFalse($content->hasSlides());
        $this->assertFalse($content->hasFiles());
        $this->assertSame([], $content->getSlides());
        $this->assertSame([], $content->getFiles());
    }

    /**
     * Test adding providers.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\DefaultSlideProvider::__construct()
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\DefaultSlideProvider::process()
     */
    public function testProcessWithImageSlides()
    {
        $filesHelper          = $this->getMockBuilder(FileHelper::class)->disableOriginalConstructor()->getMock();
        $sliderModelAdapter   = $this->getMockBuilder(Adapter::class)->setMethods(['findByPk'])->disableOriginalConstructor()->getMock();
        $slideModelAdapter    = $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock();
        $frontendAdapter      = $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock();

        $sliderModelAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->willReturn((object) [
                'id' => 42,
                'type' => 'image',
                'multiSRC' => serialize(['uuid1', 'uuid2']),
                'orderSRC' => serialize(['uuid1', 'uuid2', 'uuid3', 'uuid4']),
            ]);

        $provider = new DefaultSlideProvider(
            $filesHelper,
            $sliderModelAdapter,
            $slideModelAdapter,
            $frontendAdapter
        );

        new SliderContent();

        $content  = $this->getMockBuilder(SliderContent::class)->setMethods(
            [
                'addFiles',
                'getFiles',
                'hasFiles',
                'hasSlides',
                'getSlides',
                'getFilesOrder',
            ]
        )->getMock();
        $content->expects($this->once())->method('addFiles')->with(
            ['uuid1', 'uuid2'],
            ['uuid1', 'uuid2', 'uuid3', 'uuid4']
        );
        $content->expects($this->never())->method('hasFiles');
        $content->expects($this->never())->method('getFiles');
        $content->expects($this->never())->method('getFilesOrder');
        $content->expects($this->never())->method('hasSlides');
        $content->expects($this->never())->method('getSlides');
        $content->expects($this->never())->method('getFiles');

        $provider->process(['rsts_id' => 42], $content);
    }
}
