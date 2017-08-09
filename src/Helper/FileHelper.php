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
use Contao\StringUtil;
use Contao\Validator;

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
    public function findMultipleFilesByUuids($uuids, array $options = [])
    {
        return $this->filesModelAdapter->findMultipleByUuids($uuids, $options);
    }

    /**
     * Find multiple files by their UUIDs.
     *
     * @param string[]|Collection|FilesModel[] $uuids   An array of UUIDs to be used as pid.
     * @param array                            $options An optional options array.
     *
     * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no files.
     */
    public function findMultipleFilesByUuidRecursive($uuids, array $options = [])
    {
        $result = [];
        $dirs   = [];
        if (!$uuids instanceof Collection) {
            $uuids = $this->findMultipleFilesByUuids($uuids, $options);
        }

        foreach ($uuids as $file) {
            if ($file->type === 'file') {
                $result[] = $file;
                continue;
            }

            $dirs[] = $file->uuid;
        }
        if (empty($dirs)) {
            return $result;
        }

        return array_merge($result, $this->findMultipleFilesByPidRecursive($dirs, $options));
    }

    /**
     * Find multiple files by their UUID-pid.
     *
     * @param string[]|Collection|FilesModel[] $pids    An array of UUIDs to be used as pid.
     * @param array                            $options An optional options array.
     *
     * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no files.
     */
    public function findMultipleFilesByPidRecursive($pids, array $options = [])
    {
        $result = [];
        $dirs   = [];
        if (!$pids instanceof Collection) {
            $pids = $this->filesModelAdapter->findBy([
                $this->filesModelAdapter->getTable() . '.pid IN (' .
                implode(',', array_fill(0, count($pids), 'UNHEX(?)')) . ')'
            ], array_map(function ($id) {
                return Validator::isStringUuid($id) ? bin2hex(StringUtil::uuidToBin($id)) : bin2hex($id);
            }, $pids), $options);
        }

        foreach ($pids as $file) {
            if ($file->type === 'file') {
                $result[] = $file;
                continue;
            }

            $dirs[] = $file->uuid;
        }

        if (empty($dirs)) {
            return $result;
        }

        return array_merge($result, $this->findMultipleFilesByPidRecursive($dirs, $options));
    }

    /**
     * Gateway to frontend adapter.
     *
     * @param array $data The image data as array.
     *
     * @return \stdClass
     */
    public function prepareImageForTemplate($uuidOrModel, $data)
    {
        $image = new \stdClass;
        $this->frontendAdapter->addImageToTemplate($image, $data, null, null, $this->ensureFileModel($uuidOrModel));

        return $image;
    }

    /**
     * Try to prepare the file with the passed uuid.
     *
     * @param string|FilesModel $uuidOrModel  The uuid of the file.
     * @param array             $attributes   The attributes to pass to Frontend::addImageToTemplate().
     * @param bool              $addMeta      If true, the Meta information attributes will also get added
     *                                        'alt'      => meta['title']
     *                                        'imageUrl' => meta['link'],
     *                                        'caption'  => meta['caption']
     *
     * @return null|array
     */
    public function tryPrepareImage($uuidOrModel, $attributes, $addMeta = false)
    {
        if (null === ($file = $this->ensureFileModel($uuidOrModel))) {
            return null;
        }
        $fileObject = $this->getFileInstance($file->path);
        // FIXME: Why check for isGdImage here? if isImage == true it is always also isGdImage == true?
        if (!$fileObject->isGdImage && !$fileObject->isImage) {
            return null;
        }

        if ($addMeta) {
            // FIXME: this is only needed because of the language, we need it via another way!
            global $objPage;
            $meta                   = $this->frontendAdapter->getMetaData($file->meta, $objPage->language);
            $attributes['alt']      = $meta['title'];
            $attributes['imageUrl'] = $meta['link'];
            $attributes['caption']  = $meta['caption'];
        }

        return array_merge([
            'id'        => $file->id,
            'uuid'      => isset($file->uuid) ? $file->uuid : null,
            'name'      => $fileObject->basename,
            'singleSRC' => $file->path,
        ], $attributes);
    }

    /**
     * Try to prepare the file with the passed uuid.
     *
     * @param string|FilesModel $uuidOrModel  The uuid of the file.
     * @param array             $attributes   The attributes to pass to Frontend::addImageToTemplate().
     * @param bool              $addMeta      If true, the Meta information attributes will also get added
     *                                        'alt'      => meta['title']
     *                                        'imageUrl' => meta['link'],
     *                                        'caption'  => meta['caption']
     *
     * @return null|\stdClass
     */
    public function tryPrepareImageForTemplate($uuidOrModel, $attributes, $addMeta = false)
    {
        if (null === ($imageData = $this->tryPrepareImage($uuidOrModel, $attributes, $addMeta))) {
            return null;
        }

        return $this->prepareImageForTemplate($uuidOrModel, $imageData);
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

    /**
     * Convert an uuid to a FilesModel instance if not one already.
     *
     * @param string|FilesModel $uuidOrModel
     *
     * @return FilesModel|null
     */
    private function ensureFileModel($uuidOrModel)
    {
        if ($uuidOrModel instanceof FilesModel) {
            return $uuidOrModel;
        }

        if (!trim($uuidOrModel)) {
            return null;
        }

        return $this->filesModelAdapter->findByUuid($uuidOrModel);
    }
}
