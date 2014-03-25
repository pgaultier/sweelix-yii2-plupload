<?php
/**
 * File UploadedFile.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  components
 * @package   sweelix.yii2.plupload.components
 */

namespace sweelix\yii2\plupload\components;
use yii\web\UploadedFile as BaseUploadedFile;

/**
 * Class UploadedFile
 *
 * This component allow the user to retrieve files which where
 * uploaded using the plupload stuff
 *
 * <code>
 * 	...
 * 		// file was created as Html::asyncFileUpload($file, 'uploadedFile', $options)
 * 		$file = new MyFileModel();
 * 		if(isset($_POST['MyFileModel']) == true) {
 * 			// get instances : retrieve the file uploaded for current property
 * 			// we can retrieve the first uploaded file
 * 			$uplodadedFile = UploadedFile::getInstance($file, 'uploadedFile');
 * 			// $uploadedFile is an UploadedFile
 * 			if($uploadedFile !== null) {
 * 				$uploadedFile->saveAs('targetDirectory/'.$uploadedFile->getName());
 * 			}
 * 		}
 * 	...
 * </code>
 *
 * <code>
 * 	...
 * 		// file was created as multi file upload : Html::asyncFileUpload($file, 'uploadedFile', array(..., multiSelection=>true,...)
 * 		$file = new MyFileModel();
 * 		if(isset($_POST['MyFileModel']) == true) {
 * 			// get instances : retrieve all files uploaded for current property
 * 			$uplodadedFiles = UploadedFile::getInstances($file, 'uploadedFile');
 * 			// $uplodadedFiles is an array
 * 			foreach($uplodadedFiles as $uploadedFile) {
 * 				$uploadedFile->saveAs('targetDirectory/'.$uploadedFile->getName());
 * 			}
 * 		}
 * 	...
 * </code>
 *
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  components
 * @package   sweelix.yii2.plupload.components
 * @since     XXX
 */
class UploadedFile extends BaseUploadedFile {
	public static $targetPath='@app/runtime/upload';
}
