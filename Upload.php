<?php

namespace egor\file_upload;

use Exception;

/**
 * Upload save uploaded image from remote hosts by URL.
 *
 * Usage:
 *
 * $image = new Upload('path/to/image/storage', $max_size);
 * $image->uploadImage('http://example.com/image.jpg);
 *
 * @author Egor <belemets.egor@gmail.com>
 * @since 1.0
 */
class Upload implements UploadInterface
{
    /*
     * @var array allowed images mime types and extension
     */
    public $allows_images_type = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];

    /*
     * @var int Max file size to upload, by default 3Mb
     */
    public $max_size = 3145728;

    /*
     * @var string path to image storage
     */
    public $path;

    /*
     * @var int file size to upload
     */
    protected $size;

    /*
     * @var string uploading file mime type
     */
    protected $mime_type;

    /**
     * Upload constructor.
     * @param string $path
     * @param int|null $max_size
     */
    public function __construct(string $path, int $max_size = null)
    {
        $this->path = $path;
        if ($max_size) {
            $this->max_size = $max_size;
        }
    }

    /**
     * @param string $url path to image located on remote host
     * @return bool
     * @throws Exception
     */
    public function uploadImage(string $url) : bool
    {
        if ($this->getImageData($url) && $this->validate()) {
            $image_path = $this->generateImageName();
            $content = file_get_contents($url);
            if (!$this->saveImage($content, $image_path)) {
                throw new Exception('Image was not saved');
            }
        }
        return true;
    }

    /**
     * @return string
     */
    protected function generateImageName() : string
    {
        return $this->path . DIRECTORY_SEPARATOR . md5(time()) . '.' . $this->allows_images_type[$this->mime_type];
    }

    /**
     * @param string $content
     * @param string $image_name
     * @return bool
     * @throws Exception
     */
    protected function saveImage(string $content, string $image_name) : bool
    {

        if (is_dir($this->path) === false) {
            if (mkdir($this->path) === false) {
                throw new Exception('Image storage directory is absent and can not be created');
            }
        }

        if (!is_writable($this->path)) {
            throw new Exception("You don't have permission to {$this->path} directory");
        }

        return file_put_contents($image_name, $content);
    }

    /**
     * @param string $url
     * @return bool
     */
    protected function getImageData(string $url) : bool
    {
        if (!($fp = fopen($url, "r"))) {
            return false;
        }
        $info = stream_get_meta_data($fp);
        fclose($fp);
        foreach ($info["wrapper_data"] as $value) {
            if (stristr($value, "content-length")) {
                $value = explode(":", $value);
                $this->size = trim($value[1]);
                continue;
            }
            if (stristr($value, "content-type")) {
                $value = explode(":", $value);
                $this->mime_type = trim($value[1]);
            }
        }
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function validate() : bool
    {
        if (!isset($this->allows_images_type[$this->mime_type])) {
            throw new Exception('Not allowed file type');
        }

        if ($this->size > $this->max_size) {
            throw new Exception('This file is too big');
        }

        return true;
    }
}
