<?php
/**
 * DeleteFile.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.0.3
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
 * @version   1.0.3
 * @link      http://www.sweelix.net
 * @category  actions
 * @package   sweelix.yii2.plupload.actions
 * @since     1.0.0
 */
class DeleteFile extends Action
{
    /**
     * Run the action and perform the delete process
     *
     * @return void
     * @since  1.0.0
     */
    public function run()
    {
        try {
            Yii::$app->getSession()->open();
            $sessionId = Yii::$app->getSession()->getId();
            $fileName = Yii::$app->getRequest()->get('tmp_name', '');
            $name = Yii::$app->getRequest()->get('name', '');

            $id = Yii::$app->getRequest()->get('id', 'unk');
            $aliasPath = UploadedFile::$targetPath.'/'.$sessionId.'/'.$id;
            $targetFile = Yii::getAlias($aliasPath).'/'.$fileName;
            $response = [
                'name' => $name,
                'tmp_name' => $fileName,
                'type' => null,
                'size' => null,
                'error' => UPLOAD_ERR_OK,
            ];
            if ((file_exists($targetFile) == true) && (is_file($targetFile) == true)) {
                unlink($targetFile);
            } else {
                $response['error'] = UPLOAD_ERR_NO_FILE;
            }
            Yii::$app->getResponse()->format = Response::FORMAT_JSON;
            Yii::$app->getResponse()->data = $response;
            return Yii::$app->getResponse();
        } catch (Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            throw $e;
        }
    }
}
