<?php

namespace OCA\Pannellum\Preview;

require __DIR__ . '/../../../camerarawpreviews/vendor/autoload.php';

use Intervention\Image\ImageManagerStatic as Image;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\ILogger;
use OC\Preview\ProviderV2;

class PanoPreviewIProviderV2 extends ProviderV2
{
    const DRIVER_IMAGICK = 'imagick';
    const DRIVER_GD = 'gd';
    const CHUNK_SIZE = 8192;
    protected $converter;
    protected $driver;

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
        $tagData = $this->getXmpTag($tmpPath);
        $previewImageTmp = \OC::$server->getTempManager()->getTemporaryFile();
        $previewImageTmpName = basename($previewImageTmp);
        $previewImageTmpPath = dirname($previewImageTmp);

// TODO: error handle
        $handle = popen("/usr/bin/docker run -i --rm -v ". dirname($tmpPath) . ":/data -v " . $previewImageTmpPath . ":/tmp --entrypoint /usr/bin/nona generate-panorama -o /tmp/" . $previewImageTmpName . " /dev/stdin 2>&1", "w");
        fprintf($handle,
            "p f0 w%d h%d v%d  n\"JPEG q80\"\n".
            "m i0\n".
            "o w%d h%d f4 Tpp0 Tpy0 TrX0 TrY0 TrZ0 a0 b0 c0 d0 e%d g0 p%d r24 t0 v360 y%d  n\"/data/%s\"\n",
            $maxX, $maxY,
            $tagData["InitialHorizontalFOVDegrees"],
            $tagData["CroppedAreaImageWidthPixels"], $tagData["CroppedAreaImageHeightPixels"], $tagData["CroppedAreaTopPixels"] / 2,
            $tagData["InitialViewPitchDegrees"], $tagData["InitialViewHeadingDegrees"],
            basename($tmpPath)
        );
        pclose($handle);
        return $previewImageTmp . ".jpg";
    }

    /**
     * @param $tmpPath
     * @return array
     * @throws Exception
     */
    private function getXmpTag($tmpPath)
    {

        $cmd = $this->getConverter() . " -json -xmp:FullPanoWidthPixels -xmp:FullPanoHeightPixels "
          . "-xmp:CroppedAreaImageWidthPixels -xmp:CroppedAreaImageHeightPixels -xmp:CroppedAreaTopPixels "
          . "-xmp:InitialViewHeadingDegrees -xmp:InitialViewPitchDegrees -xmp:InitialHorizontalFOVDegrees "
          . escapeshellarg($tmpPath);
        $json = shell_exec($cmd);
        // get all available previews and the file type
        $previewData = json_decode($json, true);
        return $previewData[0];
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getConverter()
    {
        if (!is_null($this->converter)) {
            return $this->converter;
        }

        $exiftoolBin = \OC_Helper::findBinaryPath('exiftool');
        if (!is_null($exiftoolBin)) {
            $this->converter = $exiftoolBin;
            return $this->converter;
        }

        $exiftoolBin = exec("command -v exiftool");
        if (!empty($exiftoolBin)) {
            $this->converter = $exiftoolBin;
            return $this->converter;
        }

        throw new Exception('No perl executable found. Pannellum app will not work.');
    }
}
