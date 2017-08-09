<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test;

use MadeYourDay\RockSolidSlider\SliderContent;
use PHPUnit\Framework\TestCase;

/**
 * Tests the SliderContent class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class SliderContentTest extends TestCase
{
    /**
     * Tests the object instantiation.
     *
     * @coversNothing
     */
    public function testInstantiation()
    {
        $content = new SliderContent();

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\SliderContent', $content);
    }

    /**
     * Test that empty slides are really empty.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\SliderContent::hasSlides()
     * @covers \MadeYourDay\RockSolidSlider\SliderContent::getSlides()
     */
    public function testEmptySlides()
    {
        $content = new SliderContent();

        $this->assertFalse($content->hasSlides());
        $this->assertEquals([], $content->getSlides());
    }

    /**
     * Test that slides can get added and are returned correctly.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\SliderContent::addSlides()
     * @covers \MadeYourDay\RockSolidSlider\SliderContent::hasSlides()
     * @covers \MadeYourDay\RockSolidSlider\SliderContent::getSlides()
     */
    public function testAddSlides()
    {
        $content = new SliderContent();
        $content->addSlides([
            ['text' => 'slide 1'],
            ['text' => 'slide 2'],
            ['text' => 'slide 3'],
        ]);
        $content->addSlides([
            'some_key' => ['text' => 'slide 4'],
            ['text' => 'slide 5'],
            ['text' => 'slide 6'],
        ]);

        $this->assertTrue($content->hasSlides());
        $this->assertEquals(
            [
                 ['text' => 'slide 1'],
                 ['text' => 'slide 2'],
                 ['text' => 'slide 3'],
                 ['text' => 'slide 4'],
                 ['text' => 'slide 5'],
                 ['text' => 'slide 6'],
            ],
            $content->getSlides()
        );
    }

    /**
     * Test that invalid slides can not get added.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\SliderContent::addSlides()
     */
    public function testAddInvalidSlide()
    {
        $content = new SliderContent();
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Slide does not contain key "text" or "image".');
        $content->addSlides([['invalid' => 'slide']]);
    }
}
