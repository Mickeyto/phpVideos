<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 21:48
 */

namespace core\Http\MessageInterface;


interface UploadFile
{
    public function getStream();

    public function moveTo($targetPath);

    public function getSize();

    public function getError();

    public function getClientFilename();

    public function getMediaType();

}