<?php
/**
 * UploadFile.php
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
use Yii;
use Exception;


/**
 * This UploadFile handle the xhr /swfupload process
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
class UploadFile extends Action {
	/**
	 * @var string define locale used for transliteration
	 */
	public $locale = 'fr_FR.UTF8';
	/**
	 * Run the action and perform the upload process
	 *
	 * @return void
	 * @since  XXX
	 */
	public function run() {
		try {
			Yii::$app->getSession()->open();
			$sessionId =  Yii::$app->getRequest()->get('key', Yii::$app->getSession()->getId());
			$chunk = Yii::$app->getRequest()->get('chunk', 0);
			$chunks =  Yii::$app->getRequest()->get('chunks', 0);
			$fileName =  Yii::$app->getRequest()->get('name', '');
			$id = Yii::$app->getRequest()->get('id', 'unk');

			setlocale(LC_ALL, $this->locale);
			$fileName = iconv('utf-8','ASCII//TRANSLIT//IGNORE', $fileName);
			setlocale(LC_ALL, 0);

			$fileName = strtolower($fileName);
			$fileName = preg_replace('/([^a-z0-9\._\-])+/iu', '-', $fileName);

			$targetPath = Yii::getAlias(UploadedFile::$targetPath).DIRECTORY_SEPARATOR.$sessionId.DIRECTORY_SEPARATOR.$id;

			if(is_dir($targetPath) == false) {
				mkdir($targetPath, 0777, true);
			}

			// create unique fileName only if chunking is disabled
			if (($chunks < 2) && (file_exists($targetPath . DIRECTORY_SEPARATOR . $fileName) == true)) {
				$fileNameInfo = pathinfo($fileName);
				$count = 1;
				while (file_exists($targetPath . DIRECTORY_SEPARATOR . $fileNameInfo['filename'] . '_' . $count . '.' . $fileNameInfo['extension'])) {
					$count++;
				}
				$fileName = $fileNameInfo['filename'] . '_' . $count . '.' . $fileNameInfo['extension'];
			}
			$response = array('fileName' => 'tmp://'.$fileName, 'status' => true, 'fileSize' => null);
			// Look for the content type header
			$contentType = null;
			if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
				$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
			}
			if (isset($_SERVER["CONTENT_TYPE"]) == true) {
				$contentType = $_SERVER["CONTENT_TYPE"];
			}
			if (strpos($contentType, "multipart") !== false) {
				if ((isset($_FILES['file']['tmp_name']) == true) && (is_uploaded_file($_FILES['file']['tmp_name']) == true)) {
					// Open temp file
					$out = fopen($targetPath . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
					if ($out !== false) {
						// Read binary input stream and append it to temp file
						$in = fopen($_FILES['file']['tmp_name'], "rb");
						if ($in !== false) {
							while (($buff = fread($in, 4096))) {
								fwrite($out, $buff);
							}
						} else {
							$response['status'] = false;
						}
						fclose($in);
						fclose($out);
						@unlink($_FILES['file']['tmp_name']);
						$response['fileSize'] = filesize($targetPath . DIRECTORY_SEPARATOR . $fileName);
					} else {
						$response['status'] = false;
					}
				} else {
					$response['status'] = false;

				}
			} else {
				// Open temp file
				$out = fopen($targetPath . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
				if ($out !== false) {
					// Read binary input stream and append it to temp file
					$in = fopen("php://input", "rb");
					if ($in !== false) {
						while (($buff = fread($in, 4096))) {
							fwrite($out, $buff);
						}
						$response['fileSize'] = filesize($targetPath . DIRECTORY_SEPARATOR . $fileName);
					} else {
						$response['status'] = false;
					}
					fclose($in);
					fclose($out);
				} else {
					$response['status'] = false;
				}
			}
			Yii::$app->getResponse()->format = Response::FORMAT_JSON;
			return $response;
		}
		catch(Exception $e) {
			Yii::error($e->getMessage(), __METHOD__);
			throw $e;
		}
	}
}