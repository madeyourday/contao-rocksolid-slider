<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Test\Helper;

use Contao\CoreBundle\Framework\Adapter;
use Contao\FilesModel;
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
     * Test the findMultipleFilesByUuidRecursive method.
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::findMultipleFilesByUuids()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::findMultipleFilesByUuidRecursive()
     *
     * @return void
     */
    public function testFindMultipleFilesByUuidRecursive()
    {
        $filesModelAdapter = $this->mockAdapter(['findMultipleByUuids', 'getTable', 'findBy']);
        $frontendAdapter   = $this->mockAdapter();

        $filesModelAdapter->method('getTable')->willReturn('tl_files');

        $initial = ['uuid1', 'uuid2'];

        $helper = $this
            ->getMockBuilder(FileHelper::class)
            ->setConstructorArgs([$filesModelAdapter, $frontendAdapter])
            ->setMethods(['findMultipleFilesByUuids', 'findMultipleFilesByPidRecursive'])
            ->getMock();
        $helper->expects($this->once())
            ->method('findMultipleFilesByUuids')
            ->with($initial)
            ->willReturn([
                $file1 = $this->mockFileModel(['uuid' => 'uuid1', 'pid' => null, 'type' => 'file']),
                $this->mockFileModel(['uuid' => 'uuid2', 'pid' => null, 'type' => 'folder']),
            ]);
        $helper->expects($this->once())
            ->method('findMultipleFilesByPidRecursive')
            ->with(['uuid2'])
            ->willReturn([
                $file3 = $this->mockFileModel(['uuid' => 'uuid3', 'pid' => 'uuid2', 'type' => 'file']),
                $file4 = $this->mockFileModel(['uuid' => 'uuid4', 'pid' => 'uuid2', 'type' => 'file']),
            ]);

        /** @var FileHelper $helper */
        $this->assertEquals([$file1, $file3, $file4], $helper->findMultipleFilesByUuidRecursive($initial));
    }

    /**
     * Test the findMultipleFilesByUuidRecursive method.
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::findMultipleFilesByUuids()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::findMultipleFilesByUuidRecursive()
     *
     * @return void
     */
    public function testFindMultipleFilesByUuidRecursive2()
    {
        $filesModelAdapter = $this->mockAdapter(['findMultipleByUuids', 'getTable', 'findBy']);
        $frontendAdapter   = $this->mockAdapter();

        $filesModelAdapter->method('getTable')->willReturn('tl_files');

        $initial = ['uuid1', 'uuid2'];

        $helper = $this
            ->getMockBuilder(FileHelper::class)
            ->setConstructorArgs([$filesModelAdapter, $frontendAdapter])
            ->setMethods(['findMultipleFilesByUuids', 'findMultipleFilesByPidRecursive'])
            ->getMock();
        $helper->expects($this->once())
            ->method('findMultipleFilesByUuids')
            ->with($initial)
            ->willReturn([
                $file1 = $this->mockFileModel(['uuid' => 'uuid1', 'pid' => null, 'type' => 'file']),
                $file2 = $this->mockFileModel(['uuid' => 'uuid2', 'pid' => null, 'type' => 'file']),
            ]);
        $helper->expects($this->never())
            ->method('findMultipleFilesByPidRecursive');

        /** @var FileHelper $helper */
        $this->assertEquals([$file1, $file2], $helper->findMultipleFilesByUuidRecursive($initial));
    }

    /**
     * Test the findMultipleFilesByPidRecursive method.
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::findMultipleFilesByUuids()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::findMultipleFilesByPidRecursive()
     *
     * @return void
     */
    public function testFindMultipleFilesByPidRecursive()
    {
        $filesModelAdapter = $this->mockAdapter(['getTable', 'findBy']);
        $frontendAdapter   = $this->mockAdapter();

        $file3 = $this->mockFileModel(['uuid' => 'uuid3', 'pid' => 'uuid1', 'type' => 'folder']);
        $file4 = $this->mockFileModel(['uuid' => 'uuid4', 'pid' => 'uuid1', 'type' => 'file']);
        $file5 = $this->mockFileModel(['uuid' => 'uuid4', 'pid' => 'uuid1', 'type' => 'file']);

        $filesModelAdapter->method('getTable')->willReturn('tl_files');

        $initial = ['uuid1', 'uuid2'];

        $filesModelAdapter->method('findBy')
            ->withConsecutive(
                [
                    ['tl_files.pid IN (UNHEX(?),UNHEX(?))'],
                    [bin2hex('uuid1'), bin2hex('uuid2')],
                    []
                ],
                [
                    ['tl_files.pid IN (UNHEX(?))'],
                    [bin2hex('uuid3')],
                    []
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [$file3, $file4],
                [$file5]
            );

        $helper = new FileHelper($filesModelAdapter, $frontendAdapter);

        /** @var FileHelper $helper */
        $this->assertEquals([$file4, $file5], $helper->findMultipleFilesByPidRecursive($initial));
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

        $helper->prepareImageForTemplate('uuid', ['option1' => 'value1', 'option2' => 'value2']);
    }

    /**
     * Test the tryPrepareImage method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::prepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::ensureFileModel()
     */
    public function testTryPrepareImageForTemplate()
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
            ->expects($this->any())
            ->method('findByUuid')
            ->with('the-uuid')
            ->willReturn($fileModelMock);

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
                    'additional' => 'attribute'
                ]
            )
            ->willReturnCallback(function ($image) { $image->result = 'Success!';});

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
            $helper->tryPrepareImageForTemplate('the-uuid', ['additional' => 'attribute'])->result
        );
    }

    /**
     * Test the tryPrepareImage method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::ensureFileModel()
     */
    public function testTryPrepareImageForTemplateWithoutUuid()
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
        $this->assertNull($helper->tryPrepareImageForTemplate('', []));
    }

    /**
     * Test the tryPrepareImage method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::prepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::ensureFileModel()
     */
    public function testTryPrepareImageForTemplateWithUnknownUuid()
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
        $this->assertNull($helper->tryPrepareImageForTemplate('unknown-uuid', []));
    }

    /**
     * Test the tryPrepareImage method.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::prepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImageForTemplate()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::ensureFileModel()
     */
    public function testTryPrepareImageForTemplateWithNonImageUuid()
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
        $this->assertNull($helper->tryPrepareImageForTemplate('the-uuid', []));
    }

    /**
     * Test the ensureFileModel method does try to look up a passed files model again.
     *
     * @return void
     *
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::__construct()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::tryPrepareImage()
     * @covers \MadeYourDay\RockSolidSlider\Helper\FileHelper::ensureFileModel()
     */
    public function testEnsureFileModelDoesNotConvertModel()
    {
        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $frontendAdapter   = $this->mockAdapter(['getMetaData', 'addImageToTemplate']);
        $fileModelMock     = $this->mockFileModel(['path' => 'some/path/file.ext']);

        $fileMock      = (object) [
            'isGdImage' => false,
            'isImage'   => false,
        ];

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
            ->expects($this->once())
            ->method('getFileInstance')
            ->with('some/path/file.ext')
            ->willReturn($fileMock);

        /** @var FileHelper $helper */
        $this->assertNull($helper->tryPrepareImage($fileModelMock, []));
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

    /**
     * Mock a file model.
     *
     * @param array $data The data to return.
     *
     * @return FilesModel
     */
    private function mockFileModel($data)
    {
        if (!class_exists('Model')) {
            class_alias('Contao\Model', 'Model');
        }

        $fileModelMock = $this
            ->getMockBuilder(FilesModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['__get'])
            ->getMock();
        $fileModelMock
            ->method('__get')
            ->willReturnCallback(function ($key) use ($data) {
                if (isset($data[$key])) {
                    return $data[$key];
                }
                return null;
            });

        return $fileModelMock;
    }
}
