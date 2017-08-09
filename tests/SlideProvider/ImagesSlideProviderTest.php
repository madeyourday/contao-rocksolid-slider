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
use MadeYourDay\RockSolidSlider\SlideProvider\ImagesSlideProvider;
use MadeYourDay\RockSolidSlider\SliderContent;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ImagesSlideProvider class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class ImagesSlideProviderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\ImagesSlideProvider::getName()
     */
    public function testInstantiation()
    {
        $provider = new ImagesSlideProvider(
            $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\SlideProvider\ImagesSlideProvider', $provider);
        $this->assertSame('rsts_images', $provider->getName());
    }

    /**
     * Test adding providers.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\SlideProvider\ImagesSlideProvider::process()
     * @uses \MadeYourDay\RockSolidSlider\SliderContent::hasSlides()
     */
    public function testProcess()
    {
        $provider = new ImagesSlideProvider();
        $content  = new SliderContent();

        $provider->process(['multiSRC' => serialize(['uuid1', 'uuid2']), 'orderSRC' => serialize(['uuid1', 'uuid2'])], $content);

        $this->assertFalse($content->hasSlides());
    }
}
