<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Helper;

use Contao\CoreBundle\Framework\Adapter;
use Contao\File;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\Model\Collection;

/**
 * This class is a helper service for processing files.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class FileHelper
{
    /**
     * @var Adapter|FilesModel
     */
    private $filesModelAdapter;

    /**
     * @var Adapter|Frontend
     */
    private $frontendAdapter;

    /**
     * Create a new instance.
     *
     * @param Adapter|FilesModel $filesModelAdapter
     * @param Adapter|Frontend   $frontendAdapter
     */
    public function __construct(Adapter $filesModelAdapter, Adapter $frontendAdapter)
    {
        $this->filesModelAdapter = $filesModelAdapter;
        $this->frontendAdapter   = $frontendAdapter;
    }

    /**
     * Find multiple files by their UUIDs.
     *
     * @param array $uuids   An array of UUIDs.
     * @param array $options An optional options array.
     *
     * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no files.
     */
    public function findMultipleFilesByUuids($uuids, array $options=array())
    {
        return $this->filesModelAdapter->findMultipleByUuids($uuids, $options);
    }

    /**
     * Gateway to frontend adapter.
     *
     * @param array $data The image data as array.
     *
     * @return \stdClass
     */
    public function prepareImageForTemplate($data)
    {
        $image = new \stdClass;
        $this->frontendAdapter->addImageToTemplate($image, $data);

        return $image;
    }

    /**
     * Try to prepare the file with the passed uuid.
     *
     * @param string $uuid       The uuid of the file.
     * @param array  $attributes The attributes to pass to Frontend::addImageToTemplate().
     * @param bool $addMeta      If true, the Meta information attributes will also get added
     *                          'alt'      => meta['title']
     *                          'imageUrl' => meta['link'],
     *                          'caption'  => meta['caption']
     *
     * @return null|\stdClass
     */
    public function tryPrepareImage($uuid, $attributes, $addMeta = false)
    {
        if (!trim($uuid)) {
            return null;
        }
        if (null === ($file = $this->filesModelAdapter->findByUuid($uuid))) {
            return null;
        }
        $fileObject = $this->getFileInstance($file->path);
        // FIXME: Why check for isGdImage here? if isImage == true it is always also isGdImage == true?
        if (!$fileObject->isGdImage && !$fileObject->isImage) {
            return null;
        }

        if ($addMeta) {
            global $objPage;
            $meta                   = $this->frontendAdapter->getMetaData($file->meta, $objPage->language);
            $attributes['alt']      = $meta['title'];
            $attributes['imageUrl'] = $meta['link'];
            $attributes['caption']  = $meta['caption'];
        }

        return $this->prepareImageForTemplate(array_merge([
            'id'        => $file->id,
            'uuid'      => isset($file->uuid) ? $file->uuid : null,
            'name'      => $fileObject->basename,
            'singleSRC' => $file->path,
        ], $attributes));
    }

    /**
     * Instantiate a file instance and return it.
     *
     * The purpose of this function is to be able to mock it in tests and to replace it when needed.
     *
     * @param string $path The path below TL_ROOT.
     *
     * @return File
     */
    public function getFileInstance($path)
    {
        return new File($path);
    }
}
