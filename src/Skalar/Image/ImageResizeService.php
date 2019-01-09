<?php

namespace Skalar\Image;

use Skalar\Image\SkalarImageResize as ImageResize;

/**
 * Class ImageResizeService
 * @package Skalar\Image
 */
class ImageResizeService
{
    /**
     * @var ImageResize
     */
    public $image;
    /**
     * @var
     */
    private $height;
    /**
     * @var int
     */
    private $quality = 100;
    /**
     * @var string
     */
    private $resizeFileNameFolder = '';
    /**
     * @var string
     */
    private $fileNamePng = '';
    /**
     * @var string
     */
    private $fileNameWebp = '';
    /**
     * @var string
     */
    private $fileNameJpg = '';
    /**
     * @var string
     */
    private $fileName = '';

    /**
     * ImageResizeService constructor.
     * @param $fileName
     * @param $height
     * @param int $quality
     */
    public function __construct($fileName, $height, $quality = 100)
    {
        $this->fileName = $fileName;
        try {
            $this->image = new ImageResize($_SERVER['DOCUMENT_ROOT'] . $fileName);
        } catch (\Exception $e) {
            $this->image = null;
        }
        $this->height = $height;
        $this->quality = $quality;
        $this->resizeFileNameFolder = $this->getResizeImageFolder($fileName);
        $this->setResizeFileNames($fileName);
    }

    /**
     * @param $fileId
     * @param $height
     * @param int $quality
     * @return ImageResizeService
     */
    public static function createById($fileId, $height, $quality = 100)
    {
        return new ImageResizeService(\CFile::getPath($fileId), $height, $quality);
    }

    /**
     * @return mixed
     */
    public function getPng()
    {
        return $this->getSrc(IMAGETYPE_PNG);
    }

    /**
     * @return mixed
     */
    public function getWebp()
    {
        return $this->getSrc(IMAGETYPE_WEBP);
    }

    /**
     * @return mixed
     */
    public function getJpeg()
    {
        return $this->getSrc(IMAGETYPE_JPEG);
    }

    /**
     * @param $imageType
     * @return mixed
     */
    private function getSrc($imageType)
    {
        if (empty($this->fileName)) {
            return '';
        }
        $resizeFileName = $this->resizeFileNameFolder . '/';
        switch ($imageType) {
            case IMAGETYPE_PNG:
                $resizeFileName .= $this->fileNamePng;
                break;
            case IMAGETYPE_WEBP:
                $resizeFileName .= $this->fileNameWebp;
                break;
            case IMAGETYPE_JPEG:
                $resizeFileName .= $this->fileNameJpg;
                break;
        }
        $src = $this->getFromCache($resizeFileName);
        if (empty($src)) {
            $this->createCache($resizeFileName, $imageType);
            $src = $resizeFileName;
        }
        return $this->getRelativeSrc($src);
    }

    /**
     * @param mixed $quality
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
    }

    /**
     * @param mixed $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @param $resizeFileName
     * @return string
     */
    private function getFromCache($resizeFileName)
    {
        return file_exists($resizeFileName) ? $resizeFileName : '';
    }

    /**
     * @param $resizeFileName
     * @param $imageType
     * @param bool $useResize
     */
    private function createCache($resizeFileName, $imageType, $useResize = true)
    {
        if ($this->image) {
            if (!file_exists($this->resizeFileNameFolder)) {
                mkdir($this->resizeFileNameFolder, 0775, true);
            }
            if ($useResize) {
                $this->image->resizeToHeight($this->height);
            }
            $this->image->save($resizeFileName, $imageType, $this->quality);
        }
    }

    /**
     * @param $fileName
     * @return string
     */
    private function getResizeImageFolder($fileName)
    {
        $arPath = explode('/', trim($fileName, '/'));
        if ($arPath[0] == 'upload') {
            array_shift($arPath);
        }
        array_pop($arPath);
        $arPath[] = $this->height . '_' . $this->quality;
        return $_SERVER['DOCUMENT_ROOT'] . '/upload/resize_cache/' . implode('/', $arPath);
    }

    /**
     * @param $fileName
     */
    private function setResizeFileNames($fileName)
    {
        $info = new \SplFileInfo($fileName);
        $baseName = $info->getBasename('.' . $info->getExtension());
        $this->fileNamePng = $baseName . '.png';
        $this->fileNameWebp = $baseName . '.webp';
        $this->fileNameJpg = $baseName . '.jpeg';
    }

    /**
     * @param $src
     * @return mixed
     */
    private function getRelativeSrc($src)
    {
        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $src);
    }
}