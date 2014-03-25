<?php
/**
 * PreviewFile.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  actions
 * @package   sweelix.yii2.plupload.actions
 */

namespace sweelix\yii2\plupload\actions;
use sweelix\yii2\plupload\components\UploadedFile;
use yii\web\Response;
use yii\base\Action;
use yii\helpers\Url;
use Yii;
use Exception;

/**
 * This PreviewFile handle the xhr / swfupload process for preview
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  actions
 * @package   sweelix.yii2.plupload.actions
 * @since     XXX
 */
class PreviewFile extends Action {
	public $width = 100;
	public $height = 100;
	public $fit = true;

	/**
	 * Run the action and perform the preview process
	 *
	 * @return void
	 * @since  XXX
	 */
	public function run($fileName, $mode=null) {
		try {
			if($mode == 'json') {
				$this->generateJson($fileName);
			} elseif($mode == 'raw') {
				$this->generateImage($fileName);
			}
		} catch(Exception $e) {
			Yii::error($e->getMessage(), __METHOD__);
			throw $e;
		}
	}

	/**
	 * first pass, prepare json file
	 *
	 * @param string $fileName filename
	 *
	 * @return void
	 * @since  XXX
	 */
	public function generateJson($fileName) {
		try {
			$tempFile = false;
			Yii::$app->getSession()->open();
			$sessionId =  Yii::$app->getRequest()->get('key', Yii::$app->getSession()->getId());
			$id = Yii::$app->getRequest()->get('id', 'unk');

			if (strncmp($fileName, 'tmp://', 6) === 0) {
				$tempFile = true;
				$fileName = str_replace('tmp://', '', $fileName);
				$targetPath = Yii::getAlias(UploadedFile::$targetPath).DIRECTORY_SEPARATOR.$sessionId.DIRECTORY_SEPARATOR.$id;
			} else {
				$targetPath = Yii::getAlias(Yii::$app->getRequest()->get('targetPathAlias', '@webroot'));
			}
			if($tempFile === false) {
				$replacement = [];
				if(preg_match_all('/{([^}]+)}/', Yii::$app->getRequest()->get('targetPathAlias', '@webroot'), $matches) > 0) {
					if(isset($matches[1]) === true) {
						foreach($matches[1] as $repKey) {
							$replacement['{'.$repKey.'}'] = Yii::$app->getRequest()->get($repKey, '');
						}
						$targetPath = str_replace(array_keys($replacement), array_values($replacement), $targetPath);
					}
				}
			}
			$file = $targetPath.DIRECTORY_SEPARATOR.$fileName;
			$response = ['status' => false];
			if(is_file($file) === true) {
				$width = Yii::$app->getRequest()->get('width', $this->width);
				$height = Yii::$app->getRequest()->get('height', $this->height);
				$fit = Yii::$app->getRequest()->get('fit', $this->fit);
				if(($fit === 'true') || ($fit === 1) || ($fit === true)) {
					$fit = true;
				} else {
					$fit = false;
				}
				$fit = ($fit === true)?'true':'false';
				$response['status'] = true;
				//TODO: we should remove the bad @
				$imageInfo = @getimagesize($file);
				if(($imageInfo !== false) && (in_array($imageInfo[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG]) === true)) {
					$response['image'] = true;
				} else {
					$response['image'] = false;
				}
				if($tempFile === true) {
					$relativeFile = 'tmp://'.$fileName;
					$response['url'] = Url::to([$this->id,
							'mode' => 'raw',
							'fileName' =>$relativeFile,
							'key' => $sessionId,
							'id' => $id,
							'width' => $width,
							'height' => $height,
							'fit' => $fit,
					]);
					$response['path'] = null;
				} else {
					$basePath = Yii::getAlias('@webroot');
					$relativeFile = ltrim(str_replace($basePath, '', $file), '/');
					$response['url'] = Url::to([$this->id,
							'mode' => 'raw',
							'fileName' =>$relativeFile,
							'width' => $width,
							'height' => $height,
							'fit' => $fit,
					]);
					$response['path'] = $relativeFile;
				}
				$response['name'] = $fileName;
			}

			Yii::$app->getResponse()->format = Response::FORMAT_JSON;
			return $response;

		} catch(Exception $e) {
			Yii::error($e->getMessage(), __METHOD__);
			throw $e;
		}
	}

	/**
	 * second pass, generate file
	 *
	 * @param string $fileName filename
	 *
	 * @return void
	 * @since  XXX
	 */
	public function generateImage($fileName) {
		try {
			$tempFile = false;
			Yii::$app->getSession()->open();
			$sessionId =  Yii::$app->getRequest()->get('key', Yii::$app->getSession()->getId());
			$id = Yii::$app->getRequest()->get('id', 'unk');
			if (strncmp($fileName, 'tmp://', 6) === 0) {
				$tempFile = true;
				$fileName = str_replace('tmp://', '', $fileName);
				$targetPath = Yii::getAlias(UploadedFile::$targetPath).DIRECTORY_SEPARATOR.$sessionId.DIRECTORY_SEPARATOR.$id;
			} else {
				$targetPath = Yii::getAlias(\Yii::app()->getRequest()->get('targetPathAlias', '@webroot'));
				$replacement = [];
				if(preg_match_all('/{([^}]+)}/', Yii::app()->getRequest()->get('targetPathAlias', '@webroot'), $matches) > 0) {
					if(isset($matches[1]) === true) {
						foreach($matches[1] as $repKey) {
							$replacement['{'.$repKey.'}'] = \Yii::app()->getRequest()->get($repKey, '');
						}
						$targetPath = str_replace(array_keys($replacement), array_values($replacement), $targetPath);
					}
				}
			}
			$file = $targetPath.DIRECTORY_SEPARATOR.$fileName;
			if(is_file($file) === true) {
				$width = Yii::$app->getRequest()->get('width', $this->width);
				$height = Yii::$app->getRequest()->get('height', $this->height);
				$fit = Yii::$app->getRequest()->get('fit', $this->fit);
				if(($fit === 'true') || ($fit === 1) || ($fit === true)) {
					$fit = true;
				} else {
					$fit = false;
				}
				//TODO: we should remove the bad @
				$imageInfo = @getimagesize($file);
				if(($imageInfo !== false) && (in_array($imageInfo[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG]) === true)) {
					if($tempFile === false) {
						$image = Image::create($file)->resize($width, $height)->setFit($fit);
						$imageContentType = $image->getContentType();
						$imageData = file_get_contents($image->getUrl(true));
					} else {
						$image = Image::create($file)->resize($width, $height)->setFit($fit);
						$imageContentType = $image->getContentType();
						$imageData = $image->liveRender();
					}
				} else {
					$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
					//TODO:handle default image
					$imageName = Yii::getAlias('@sweelix/yii2/plupload/icons').DIRECTORY_SEPARATOR.$ext.'.png';
					// $imageName = dirname(__DIR__).DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.$ext.'.png';
					if(file_exists($imageName)) {
						$image = Image::create($imageName)->resize($width, $height)->setFit($fit);
						$imageContentType = $image->getContentType();
						$imageData = file_get_contents($image->getUrl(true));
					}
				}
			}
			return Yii::$app->getResponse()->sendContentAsFile($imageData, $fileName, $imageContentType);
		}
		catch(Exception $e) {
			Yii::error($e->getMessage(), __METHOD__);
			throw $e;
		}
	}
}