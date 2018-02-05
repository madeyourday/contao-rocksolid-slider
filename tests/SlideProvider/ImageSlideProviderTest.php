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
use MadeYourDay\RockSolidSlider\SlideProvider\ImageSlideProvider;
use MadeYourDay\RockSolidSlider\SliderContent;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ImageSlideProvider class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class ImageSlideProviderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\ImageSlideProvider::getName()
     */
    public function testInstantiation()
    {
        $provider = new ImageSlideProvider(
            $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\SlideProvider\ImageSlideProvider', $provider);
        $this->assertSame('rsts_images', $provider->getName());
    }

    /**
     * Test adding providers.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\ImageSlideProvider::process()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::addFiles()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::getFiles()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::hasFiles()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::hasSlides()
     */
    public function testProcess()
    {
        $provider = new ImageSlideProvider();
        $content  = new SliderContent();

        $provider->process(['multiSRC' => serialize(['uuid1', 'uuid2'])], $content);

        $this->assertFalse($content->hasSlides());
        $this->assertTrue($content->hasFiles());
        $this->assertSame(['uuid1', 'uuid2'], $content->getFiles());
    }
}
