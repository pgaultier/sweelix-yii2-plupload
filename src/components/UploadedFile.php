<?php
/**
 * File UploadedFile.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.0.1
 * @link      http://www.sweelix.net
 * @category  components
 * @package   sweelix.yii2.plupload.components
 */

namespace sweelix\yii2\plupload\components;
use yii\web\UploadedFile as BaseUploadedFile;
use Yii;

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
 * @version   1.0.1
 * @link      http://www.sweelix.net
 * @category  components
 * @package   sweelix.yii2.plupload.components
 * @since     1.0.0
 */
class UploadedFile extends BaseUploadedFile {
	public static $targetPath='@app/runtime/upload';
	protected static $_files;

	/**
	 * Creates UploadedFile instances from $_FILE.
	 * @return array the UploadedFile instances
	 */
	protected static function loadFiles() {
		if (self::$_files === null) {
			self::$_files = [];
			if (isset($_POST['_plupload']) && is_array($_POST['_plupload'])) {
				foreach ($_POST['_plupload'] as $class => $info) {
					// patch for model handling
					if(array_key_exists('name', $info) === false) {
						foreach($info as $modelAttribute => $realInfo) {
							$realClass = $class.'['.$modelAttribute.']';
							self::loadFilesRecursive($realClass, $realInfo['name'], $realInfo['tmp_name'], $realInfo['type'], $realInfo['size'], $realInfo['error']);
						}
					} else {
						self::loadFilesRecursive($class, $info['name'], $info['tmp_name'], $info['type'], $info['size'], $info['error']);
					}
				}
			}
		}
		return self::$_files;
	}

	/**
	 * Cleans up the loaded UploadedFile instances.
	 * This method is mainly used by test scripts to set up a fixture.
	 */
	public static function reset() {
		self::$_files = null;
	}

    public static function getInstance($model, $attribute)
    {
        $name = Html::getInputName($model, $attribute);
        return static::getInstanceByName($name);
    }

    public static function getInstances($model, $attribute)
    {
        $name = Html::getInputName($model, $attribute);
        return static::getInstancesByName($name);
    }

    public static function getInstanceByName($name) {
        $files = self::loadFiles();
        return isset($files[$name]) ? $files[$name] : null;
    }
    public static function getInstancesByName($name) {
        $files = self::loadFiles();
        if (isset($files[$name])) {
            return [$files[$name]];
        }
        $results = [];
        foreach ($files as $key => $file) {
            if (strpos($key, "{$name}[") === 0) {
                $results[] = $file;
            }
        }
        return $results;
    }
	/**
	 * Creates UploadedFile instances from $_FILE recursively.
	 * @param string $key key for identifying uploaded file: class name and sub-array indexes
	 * @param mixed $names file names provided by PHP
	 * @param mixed $tempNames temporary file names provided by PHP
	 * @param mixed $types file types provided by PHP
	 * @param mixed $sizes file sizes provided by PHP
	 * @param mixed $errors uploading issues provided by PHP
	 */
	protected static function loadFilesRecursive($key, $names, $tempNames, $types, $sizes, $errors) {
		if (is_array($names)) {
			foreach ($names as $i => $name) {
				self::loadFilesRecursive($key . '[' . $i . ']', $name, $tempNames[$i], $types[$i], $sizes[$i], $errors[$i]);
			}
		} else {
			self::$_files[$key] = new static([
				'name' => $names,
				'tempName' => $tempNames,
				'type' => $types,
				'size' => $sizes,
				'error' => $errors,
			]);
		}
	}

	/**
	 * Saves the uploaded file.
	 * Note that this method uses php's move_uploaded_file() method. If the target file `$file`
	 * already exists, it will be overwritten.
	 *
	 * @param string  $file           the file path used to save the uploaded file
	 * @param boolean $deleteTempFile whether to delete the temporary file after saving.
	 *
	 * @return boolean true whether the file is saved successfully
	 * @since  1.0.0
	 */
	public function saveAs($file, $deleteTempFile = true) {
		$result = false;
		if ($this->error == UPLOAD_ERR_OK) {
			$originalFile = Yii::getAlias(self::$targetPath).DIRECTORY_SEPARATOR.$this->tempName;
			$targetFile = Yii::getAlias($file);
			if(file_exists($originalFile) === true) {
				if ($deleteTempFile) {
					$result = rename($originalFile , $targetFile);
					if($result === true) {
						// should be changed as we have saved the file
						$this->tempName = null;
						$this->name = pathinfo($file, PATHINFO_BASENAME);
					}
				} else {
					$result = copy($originalFile , $targetFile);
				}
			}
		}
		return $result;
	}
}
