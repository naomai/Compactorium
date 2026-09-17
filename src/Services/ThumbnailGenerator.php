<?php
namespace Naomai\Compactorium\Services;

use Exception;
use GdImage;
use InvalidArgumentException;

class ThumbnailGenerator {
    private ?array $sizeConstraints = null;

    public const RESIZE_SHRINK = 'shrink';
    public const RESIZE_ENLARGE = 'enlarge';
    public const RESIZE_BOTH = 'both';

    public function __construct(private readonly string $resizeMode=self::RESIZE_SHRINK) {

    }

    /**
     * Sets the allowed image-size constraints.
     *
     * @param array<int>|null $sizeConstraints Maximum sizes available for constrained image creation (pixels).
     */
    public function setSizeConstraints(?array $sizeConstraints) : void {
        $this->sizeConstraints = $sizeConstraints;
    }

    /**
     * Creates a front cover at the closest configured size.
     *
     * @param GdImage $image Source image.
     * @param int $size Requested size in pixels.
     * @return GdImage Resized front cover.
     */
    public function createConstrainedFrontCover(GdImage $image, int $size) : GdImage {
        $sizeDesired = self::selectCeilingSize($this->sizeConstraints, $size);
        $newImage = $this->generateForGd(image: $image, desiredShorter: $sizeDesired);

        return $newImage;
    }

    /**
     * Creates a resized image.
     *
     * @param GdImage $image Source image.
     * @param int|null $desiredX Desired width in pixels.
     * @param int|null $desiredY Desired height in pixels.
     * @param int|null $desiredShorter Desired shorter-side length in pixels.
     * @param int|null $desiredLonger Desired longer-side length in pixels.
     * @return GdImage Resized image.
     */
    public function generateForGd(GdImage $image, ?int $desiredX=null, ?int $desiredY=null, ?int $desiredShorter=null, ?int $desiredLonger=null) : GdImage {



        $srcX = imagesx($image);
        $srcY = imagesy($image);

        [$targetX, $targetY] = self::getResizeMetrics(
            inputX: $srcX, inputY: $srcY, 
            desiredX: $desiredX, desiredY: $desiredY, 
            desiredShorter: $desiredShorter, desiredLonger: $desiredLonger
        );

        if(
            ($targetX == $srcX && $targetY == $srcY)
            || ($this->resizeMode == self::RESIZE_SHRINK && $srcX < $targetX && $srcY < $targetY)
            || ($this->resizeMode == self::RESIZE_ENLARGE && $srcX > $targetX && $srcY > $targetY)
        ) {
            return $image;
        }



        $newImage = self::resizeGdImage($image, $targetX, $targetY);

        return $newImage;

    }

    /**
     * Calculates dimensions for resizing an image.
     *
     * Explicit width and height take precedence over shorter/longer side constraints.
     * Preserves the aspect ratio when only one dimension is provided.
     *
     * @param int $inputX Original width in pixels.
     * @param int $inputY Original height in pixels.
     * @param int|null $desiredX Desired width in pixels.
     * @param int|null $desiredY Desired height in pixels.
     * @param int|null $desiredShorter Desired shorter-side length in pixels.
     * @param int|null $desiredLonger Desired longer-side length in pixels.
     * @return array{0:int,1:int} Calculated width and height in pixels.
     *
     * @throws InvalidArgumentException If any provided dimension is not greater than zero.
     */
    public static function getResizeMetrics(int $inputX, int $inputY, ?int $desiredX=null, ?int $desiredY=null, ?int $desiredShorter=null, ?int $desiredLonger=null) : array {
        foreach ([$inputX, $inputY, $desiredX, $desiredY, $desiredShorter, $desiredLonger] as $dimension) {
            if ($dimension !== null && $dimension <= 0) {
                throw new InvalidArgumentException('Image dimensions must be greater than zero.');
            }
        }

        if($desiredX===null && $desiredY===null && $desiredShorter===null && $desiredLonger===null) {
            return [$inputX, $inputY];
        }

        $ratio = $inputX/$inputY;

        $constraintX = 
            $desiredX 
            ?? ($ratio <= 1 && $desiredShorter !== null ? $desiredShorter : null)
            ?? ($ratio >= 1 && $desiredLonger  !== null ? $desiredLonger  : null);

        $constraintY = 
            $desiredY 
            ?? ($ratio >= 1 && $desiredShorter !== null ? $desiredShorter : null)
            ?? ($ratio <= 1 && $desiredLonger  !== null ? $desiredLonger  : null);

        if($constraintX===null) {
            $constraintX = $constraintY * $ratio;
        } else if($constraintY===null) {
            $constraintY = $constraintX / $ratio;
        }
        
        return [(int)$constraintX, (int)$constraintY];
    }

    /**
     * Resizes a GD image to the specified dimensions.
     *
     * @param GdImage $image Image to resize.
     * @param int $targetX Target width in pixels.
     * @param int $targetY Target height in pixels.
     * @return GdImage Resized image.
     */
    private static function resizeGdImage(GdImage $image, int $targetX, int $targetY) : GdImage {
        $target = imagecreatetruecolor($targetX, $targetY);

        imagealphablending($target, false);
        imagesavealpha($target, true);

        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefill($target, 0, 0, $transparent);

        imagecopyresampled(
            $target, $image,
            0, 0,
            0, 0,
            $targetX, $targetY,
            imagesx($image),
            imagesy($image)
        );

        return $target;
    }

    /**
     * Selects the smallest available size that meets the requested size.
     *
     * @param array<int> $sizeList Available size constraints.
     * @param int $size Requested size.
     * @return int Matching size from the list.
     */
    private static function selectCeilingSize(array $sizeList, int $size) : int {
        sort($sizeList);
        $result = 0; $idx = 0;
        do {
            $result = $sizeList[$idx++];
        } while($result < $size);

        return $result;
    }


}