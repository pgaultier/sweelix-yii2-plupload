<?php
/**
 * DeleteFile.php
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
 * This DeleteFile handle the xhr / swfupload process
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
class DeleteFile extends Action {
	/**
	 * Run the action and perform the delete process
	 *
	 * @return void
	 * @since  XXX
	 */
	public function run() {
		try {
			Yii::$app->getSession()->open();
			$sessionId = Yii::$app->getSession()->getId();
			$fileName = Yii::$app->getRequest()->get('name', '');
			if (strncmp($fileName, 'tmp://', 6) === 0) {
				$fileName = str_replace('tmp://', '', $fileName);
			}

			$id = Yii::$app->getRequest()->get('id', 'unk');
			$targetFile = Yii::getAlias(UploadedFile::$targetPath).DIRECTORY_SEPARATOR.$sessionId.DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$fileName;
			$response = [
				'fileName' => $fileName,
				'status' => false,
				'fileSize' => null,
			];
			if((file_exists($targetFile) == true) && (is_file($targetFile) == true)) {
				unlink($targetFile);
				$response['status'] = true;
			}
			Yii::$app->getResponse()->format = Response::FORMAT_JSON;
			Yii::$app->getResponse()->data = $response;
			return Yii::$app->getResponse()->send();
		}
		catch(Exception $e) {
			Yii::error($e->getMessage(), __METHOD__);
			throw $e;
		}
	}
}