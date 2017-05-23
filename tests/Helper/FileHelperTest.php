<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test\Helper;

use Contao\CoreBundle\Framework\Adapter;
use MadeYourDay\RockSolidSlider\Helper\FileHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Tests the FileHelper class.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class FileHelperTest extends TestCase
{
    /**
     * Tests the object instantiation.
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     */
    public function testInstantiation()
    {
        $helper = new FileHelper(
            $this->mockAdapter(),
            $this->mockAdapter()
        );

        $this->assertInstanceOf('MadeYourDay\RockSolidSlider\Helper\FileHelper', $helper);
    }

    /**
     * Test the findMultipleFilesByUuids method.
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::findMultipleFilesByUuids()
     */
    public function testFindMultipleFilesByUuids()
    {
        $filesModelAdapter = $this->mockAdapter(['findMultipleByUuids']);
        $frontendAdapter   = $this->mockAdapter();

        $collection = $this
            ->getMockBuilder('\Contao\Model\Collection')
            ->disableOriginalConstructor()
            ->getMock();

        $filesModelAdapter
            ->expects($this->once())
            ->method('findMultipleByUuids')
            ->with(['uuid1', 'uuid2'], ['option1' => 'value1', 'option2' => 'value2'])
            ->willReturn($collection);

        $helper = new FileHelper($filesModelAdapter, $frontendAdapter);

        $this->assertSame(
            $collection,
            $helper->findMultipleFilesByUuids(
                ['uuid1', 'uuid2'],
                ['option1' => 'value1', 'option2' => 'value2']
            )
        );
    }

    /**
     * Test the prepareImageForTemplate method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::prepareImageForTemplate()
     */
    public function testPrepareImageForTemplate()
    {
        $filesModelAdapter = $this->mockAdapter();
        $frontendAdapter   = $this->mockAdapter(['addImageToTemplate']);
        $frontendAdapter
            ->expects($this->once())
            ->method('addImageToTemplate')
            ->with(new \stdClass, ['option1' => 'value1', 'option2' => 'value2']);

        $helper = new FileHelper($filesModelAdapter, $frontendAdapter);

        $helper->prepareImageForTemplate(['option1' => 'value1', 'option2' => 'value2']);
    }

    /**
     * Test the tryPrepareImage method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::prepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     */
    public function testTryPrepareImage()
    {
        $GLOBALS['objPage'] = (object) ['language' => 'en_EN'];

        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $frontendAdapter   = $this->mockAdapter(['getMetaData', 'addImageToTemplate']);

        $fileModelMock = (object) [
            'id'   => 1,
            'uuid' => 'the-uuid',
            'path' => 'some/path/file.ext',
            'meta' => ['file-meta-data']
        ];
        $fileMock      = (object) [
            'uuid'      => 'the-uuid',
            'path'      => 'some/path/file.ext',
            'basename'  => 'file.ext',
            'isGdImage' => true,
            'isImage'   => true,
        ];

        $filesModelAdapter
            ->expects($this->once())
            ->method('findByUuid')
            ->with('the-uuid')
            ->willReturn($fileModelMock);

        $frontendAdapter
            ->expects($this->once())
            ->method('getMetaData')
            ->with(['file-meta-data'], 'en_EN')
            ->willReturn([
                'title'   => 'Title',
                'link'    => 'https://example.org',
                'caption' => 'File caption!'
            ]);
        $frontendAdapter
            ->expects($this->once())
            ->method('addImageToTemplate')
            ->with(
                new \stdClass,
                [
                    'id'         => 1,
                    'uuid'       => 'the-uuid',
                    'name'       => 'file.ext',
                    'singleSRC'  => 'some/path/file.ext',
                    'additional' => 'attribute',
                    'alt'        => 'Title',
                    'imageUrl'   => 'https://example.org',
                    'caption'    => 'File caption!'
                ]
            )
            ->willReturnCallback(function ($image, $data) { $image->result = 'Success!';});

        $helper = $this
            ->getMockBuilder(FileHelper::class)
            ->setConstructorArgs([$filesModelAdapter, $frontendAdapter])
            ->setMethods(['getFileInstance'])
            ->getMock();
        $helper
            ->expects($this->once())
            ->method('getFileInstance')
            ->with($fileModelMock->path)
            ->willReturn($fileMock);

        /** @var FileHelper $helper */
        $this->assertSame(
            'Success!',
            $helper->tryPrepareImage('the-uuid', ['additional' => 'attribute'], true)->result
        );
    }

    /**
     * Test the tryPrepareImage method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     */
    public function testTryPrepareImageWithoutUuid()
    {

        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $frontendAdapter   = $this->mockAdapter(['getMetaData', 'addImageToTemplate']);

        $filesModelAdapter
            ->expects($this->never())
            ->method('findByUuid');
        $frontendAdapter
            ->expects($this->never())
            ->method('getMetaData');
        $frontendAdapter
            ->expects($this->never())
            ->method('addImageToTemplate');

        $helper = $this
            ->getMockBuilder(FileHelper::class)
            ->setConstructorArgs([$filesModelAdapter, $frontendAdapter])
            ->setMethods(['getFileInstance'])
            ->getMock();
        $helper
            ->expects($this->never())
            ->method('getFileInstance');

        /** @var FileHelper $helper */
        $this->assertNull($helper->tryPrepareImage('', []));
    }

    /**
     * Test the tryPrepareImage method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::prepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     */
    public function testTryPrepareImageWithUnknownUuid()
    {

        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $frontendAdapter   = $this->mockAdapter(['getMetaData', 'addImageToTemplate']);

        $filesModelAdapter
            ->expects($this->once())
            ->method('findByUuid')
            ->with('unknown-uuid')
            ->willReturn(null);
        $frontendAdapter
            ->expects($this->never())
            ->method('getMetaData');
        $frontendAdapter
            ->expects($this->never())
            ->method('addImageToTemplate');


        $helper = $this
            ->getMockBuilder(FileHelper::class)
            ->setConstructorArgs([$filesModelAdapter, $frontendAdapter])
            ->setMethods(['getFileInstance'])
            ->getMock();
        $helper
            ->expects($this->never())
            ->method('getFileInstance');

        /** @var FileHelper $helper */
        $this->assertNull($helper->tryPrepareImage('unknown-uuid', []));
    }

    /**
     * Test the tryPrepareImage method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::prepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     */
    public function testTryPrepareImageWithNonImageUuid()
    {
        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $frontendAdapter   = $this->mockAdapter(['getMetaData', 'addImageToTemplate']);

        $fileModelMock = (object) [
            'id'   => 1,
            'uuid' => 'the-uuid',
            'path' => 'some/path/file.ext',
            'meta' => ['file-meta-data']
        ];
        $fileMock      = (object) [
            'uuid'      => 'the-uuid',
            'path'      => 'some/path/file.ext',
            'basename'  => 'file.ext',
            'isGdImage' => false,
            'isImage'   => false,
        ];

        $filesModelAdapter
            ->expects($this->once())
            ->method('findByUuid')
            ->with('the-uuid')
            ->willReturn($fileModelMock);

        $frontendAdapter
            ->expects($this->never())
            ->method('getMetaData');
        $frontendAdapter
            ->expects($this->never())
            ->method('addImageToTemplate');

        $helper = $this
            ->getMockBuilder(FileHelper::class)
            ->setConstructorArgs([$filesModelAdapter, $frontendAdapter])
            ->setMethods(['getFileInstance'])
            ->getMock();
        $helper
            ->expects($this->once())
            ->method('getFileInstance')
            ->with('some/path/file.ext')
            ->willReturn($fileMock);

        /** @var FileHelper $helper */
        $this->assertNull($helper->tryPrepareImage('the-uuid', []));
    }

    /**
     * Mock an adapter
     *
     * @param string[] $methods The methods to mock.
     *
     * @return Adapter|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockAdapter($methods = [])
    {
        return $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
