<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 20:35
 */

namespace core\Http\MessageInterface;


interface Stream
{
    public function close();

    public function detach();

    public function getSize();

    public function tell();

    public function eof();

    /**
     * @return bool
     */
    public function isSeekable():bool ;

    public function seek($offset, $whence=SEEK_SET);

    public function rewind();

    /**
     * @return bool
     */
    public function isWritable():bool ;

    public function write($string);

    /**
     * 是否可读
     * @return bool
     */
    public function isReadable():bool ;

    public function read($length);

    public function getContents();

    public function getMetadata($key=null);

    public function __toString();

}