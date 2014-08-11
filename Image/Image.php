<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 08/08/14
 * Time: 11:41
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Image;


use Foundation\Exception;
use Phalcon\Image\Adapter\Imagick;
use Phalcon\Image as PhImg;

class Image extends Imagick {

    /** {@link resize()} only shrinks images */
    const SHRINK_ONLY = 1;

    /** {@link resize()} will ignore aspect ratio */
    const STRETCH = 2;

    /** {@link resize()} fits in given area so its dimensions are less than or equal to the required dimensions */
    const FIT = 0;

    /** {@link resize()} fills given area so its dimensions are greater than or equal to the required dimensions */
    const FILL = 4;

    /** {@link resize()} fills given area exactly */
    const EXACT = 8;

    /** @int image types {@link send()} */
    const JPEG = IMAGETYPE_JPEG,
        PNG = IMAGETYPE_PNG,
        GIF = IMAGETYPE_GIF;

    const EMPTY_GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";

    public static $types = array(
        self::JPEG => 'jpeg',
        self::GIF  => 'gif',
        self::PNG  => 'png'
    );

    public static $typesConv = array(
        'jpg' => self::JPEG,
        'gif'  => self::GIF,
        'png'  => self::PNG,
        'jpeg' => self::JPEG
    );

    /**
     *
     * @param type $width
     * @param type $height
     * @param type $flags
     * @return \Foundation\Utils\ImImage
     */
    public function resize($width = null, $height = null, $flags = self::FIT) {

        if ($flags & self::EXACT) {
            return $this->resize($width, $height, self::FILL)->crop('50%', '50%', $width, $height);
        }

        list($newWidth, $newHeight) = self::calculateSize($this->getWidth(), $this->getHeight(), $width, $height, $flags);

        if ($newWidth !== $this->getWidth() || $newHeight !== $this->getHeight()) {
            $this->getImage()->resizeimage($newWidth, $newHeight, \Imagick::FILTER_CATROM, 1);
        }

        if ($width < 0) {
            $this->flip(PhImg::HORIZONTAL);
        }

        if ($height < 0) {
            $this->flip(PhImg::VERTICAL);
        }

        return $this;
    }

    /**
     * Calculates dimensions of resized image.
     *
     * @authod dg
     * borrowed from nette image
     *
     * @param  mixed  source width
     * @param  mixed  source height
     * @param  mixed  width in pixels or percent
     * @param  mixed  height in pixels or percent
     * @param  int    flags
     * @return array
     */
    public static function calculateSize($srcWidth, $srcHeight, $newWidth, $newHeight, $flags = self::FIT)
    {
        if (substr($newWidth, -1) === '%') {
            $newWidth = round($srcWidth / 100 * abs($newWidth));
            $percents = TRUE;
        } else {
            $newWidth = (int) abs($newWidth);
        }

        if (substr($newHeight, -1) === '%') {
            $newHeight = round($srcHeight / 100 * abs($newHeight));
            $flags |= empty($percents) ? 0 : self::STRETCH;
        } else {
            $newHeight = (int) abs($newHeight);
        }

        if ($flags & self::STRETCH) { // non-proportional
            if (empty($newWidth) || empty($newHeight)) {
                throw new Exception('For stretching must be both width and height specified.');
            }

            if ($flags & self::SHRINK_ONLY) {
                $newWidth = round($srcWidth * min(1, $newWidth / $srcWidth));
                $newHeight = round($srcHeight * min(1, $newHeight / $srcHeight));
            }

        } else {  // proportional
            if (empty($newWidth) && empty($newHeight)) {
                throw new Exceptionn('At least width or height must be specified.');
            }

            $scale = array();
            if ($newWidth > 0) { // fit width
                $scale[] = $newWidth / $srcWidth;
            }

            if ($newHeight > 0) { // fit height
                $scale[] = $newHeight / $srcHeight;
            }

            if ($flags & self::FILL) {
                $scale = array(max($scale));
            }

            if ($flags & self::SHRINK_ONLY) {
                $scale[] = 1;
            }

            $scale = min($scale);
            $newWidth = round($srcWidth * $scale);
            $newHeight = round($srcHeight * $scale);
        }

        return array(max((int) $newWidth, 1), max((int) $newHeight, 1));
    }

}