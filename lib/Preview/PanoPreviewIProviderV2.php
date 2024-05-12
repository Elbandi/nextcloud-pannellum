<?php

namespace OCA\Pannellum\Preview;

require __DIR__ . '/../../../camerarawpreviews/vendor/autoload.php';

use Intervention\Image\ImageManagerStatic as Image;

use OCA\Pannellum\Service\IXmpDataReader;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\ILogger;
use OC\Preview\ProviderV2;

class PanoPreviewIProviderV2 extends ProviderV2
{
    const CHUNK_SIZE = 8192;

    public function getMimeType(): string
    {
        return '/^image\/x-3d/';
    }

    public function isAvailable(FileInfo $file): bool
    {
        return $file->getSize() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
        $tmpPath = $this->getLocalFile($file);

        // Creates \Imagick object from the heic file
        try {
            $previewFile = $this->getResizedPreview($tmpPath, $maxX, $maxY);
        } catch (\Exception $e) {
            \OC::$server->getLogger()->logException($e, [
                'message' => 'File: ' . $file->getPath() . ' Imagick says:',
                'level' => ILogger::ERROR,
                'app' => 'core',
            ]);
            return null;
        }

        //new bitmap image object
        $image = new \OC_Image();
        $image->loadFromFile($previewFile);
        unlink($previewFile);
        $this->cleanTmpFiles();
        //check if image object is valid
        return $image->valid() ? $image : null;
    }

    /**
     * Returns a preview of maxX times maxY dimensions in PNG format
     *
     *    * The default resolution is already 72dpi, no need to change it for a bitmap output
     *    * It's possible to have proper colour conversion using profileimage().
     *    ICC profiles are here: http://www.color.org/srgbprofiles.xalter
     *    * It's possible to Gamma-correct an image via gammaImage()
     *
     * @param string $tmpPath the location of the file to convert
     * @param int $maxX
     * @param int $maxY
     *
     * @return \Imagick
     */
    private function getResizedPreview($tmpPath, $maxX, $maxY) {
        $pannellumImage = \OC::$server->getConfig()->getSystemValueString("pannellum_image", "generate-panorama");
        $xmpDataReader = \OC::$server->query(IXmpDataReader::class);
        $tagData = $xmpDataReader->getXmpTag($tmpPath);
        $previewImageTmp = \OC::$server->getTempManager()->getTemporaryFile();
        $previewImageTmpName = basename($previewImageTmp);
        $previewImageTmpPath = dirname($previewImageTmp);

// TODO: error handle
        // Convert Pannellum [yaw, pitch, roll] to Hugin [yaw, pitch, roll]
        $handle = popen(sprintf("/usr/bin/docker run -i --rm --entrypoint /usr/bin/python3 %s -c \"\n".
            "import numpy as np\n".
            "from scipy.spatial.transform import Rotation as R\n".
            "pnlm_ypr = [%d, %d, 0]\n".
            "hugin_ypr = R.from_euler('ZYX', pnlm_ypr, degrees=True).as_euler('zyx', degrees=True) * np.array([-1, -1, -1])\n".
            "print(hugin_ypr.tolist())\"", $pannellumImage, $tagData["InitialViewHeadingDegrees"], $tagData["InitialViewPitchDegrees"]), "r");
        $read = fread($handle, self::CHUNK_SIZE);
        pclose($handle);
        $hugin_ypr = json_decode($read);

        $handle = popen(sprintf("/usr/bin/docker run -i --rm -v \"%s:/data\" -v \"%s:/tmp\" --entrypoint /usr/bin/nona %s -o /tmp/%s /dev/stdin 2>&1",
            dirname($tmpPath), $previewImageTmpPath, $pannellumImage, $previewImageTmpName), "w");
        fprintf($handle,
            "p f0 w%d h%d v%d  n\"JPEG q80\"\n".
            "m i0\n".
            "o w%d h%d f4 Tpp0 Tpy0 TrX0 TrY0 TrZ0 a0 b0 c0 d0 e-%d g0 p%d r%d t0 v360 y%d  n\"/data/%s\"\n",
            $maxX, $maxY,
            $tagData["InitialHorizontalFOVDegrees"],
            $tagData["CroppedAreaImageWidthPixels"], $tagData["CroppedAreaImageHeightPixels"], $tagData["CroppedAreaTopPixels"] / 2,
            $hugin_ypr[1], $hugin_ypr[2], $hugin_ypr[0],
            basename($tmpPath)
        );
        pclose($handle);
        return $previewImageTmp . ".jpg";
    }
}
