<?php

namespace OCA\Pannellum\Service;

/*
 *  See https://developers.google.com/streetview/spherical-metadata
 */
class XmpDataReader implements IXmpDataReader {

	protected $converter;

	public function __construct() {
	}

	/**
	 * @param $path
	 * @return array
	 * @throws Exception
	 */
	public function getXmpTag($path)
	{
		$cmd = $this->getConverter() . " -json -xmp:ProjectionType -xmp:MultiResUrl "
			. "-xmp:FullPanoWidthPixels -xmp:FullPanoHeightPixels "
			. "-xmp:CroppedAreaImageWidthPixels -xmp:CroppedAreaImageHeightPixels -xmp:CroppedAreaTopPixels "
			. "-xmp:InitialViewHeadingDegrees -xmp:InitialViewPitchDegrees -xmp:InitialHorizontalFOVDegrees "
			. escapeshellarg($path);
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
