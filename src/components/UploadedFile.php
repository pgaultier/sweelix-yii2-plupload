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
use yii\helpers\Html;

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
	/**
	 * @var string where uploaded files are stored
	 */
	public static $targetPath='@app/runtime/upload';

	private static $_files;
    /**
	 * @var string the original name of the file being uploaded
	 */
    public $name;
    /**
	 * @var string the path of the uploaded file on the server.
	 * Note, this is a temporary file which will be automatically deleted by PHP
	 * after the current request is processed.
	 */
    public $tempName;
    /**
	 * @var string the MIME-type of the uploaded file (such as "image/gif").
	 * Since this MIME type is not checked on the server side, do not take this value for granted.
	 * Instead, use [[FileHelper::getMimeType()]] to determine the exact MIME type.
	 */
    public $type;
    /**
	 * @var integer the actual size of the uploaded file in bytes
	 */
    public $size;
    /**
	 * @var integer an error code describing the status of this file uploading.
	 * @see http://www.php.net/manual/en/features.file-upload.errors.php
	 */
    public $error;

	/**
	 * Returns an uploaded file for the given model attribute.
	 * The file should be uploaded using [[ActiveForm::fileInput()]].
	 * @param \yii\base\Model $model the data model
	 * @param string $attribute the attribute name. The attribute name may contain array indexes.
	 * For example, '[1]file' for tabular file uploading; and 'file[1]' for an element in a file array.
	 * @return UploadedFile the instance of the uploaded file.
	 * Null is returned if no file is uploaded for the specified model attribute.
	 * @see getInstanceByName()
	 */
    public static function getInstance($model, $attribute) {
        $name = Html::getInputName($model, $attribute);

        return static::getInstanceByName($name);
    }








	protected static $_targetPath;


	protected $_name;
	protected $_tempName;
	protected $_extensionName;
	protected $_size;
	protected $_model;
	protected $_attribute;

	/**
	 * Define the path where files will be temporary saved
	 *
	 * @return string
	 * @since  1.1.0
	 */
	public static function getTargetPath() {
		if(self::$_targetPath === null) {
			self::$_targetPath = \Yii::getAlias(self::$targetPath);
			self::$_targetPath .= DIRECTORY_SEPARATOR.\Yii::app()->getSession()->getSessionId();
		}
		return self::$_targetPath;
	}

	/**
	 * Returns an instance of the first uploaded file for selected attribute.
	 * The file should be uploaded using {@link Html::asyncFileUpload}.
	 *
	 * @param CModel $model     the model instance
	 * @param string $attribute the attribute name. Tabular file uploading is supported.
	 *
	 * @return UploadedFile the instance of the uploaded file.
	 * @since  1.1.0
	 */
	public static function getInstance($model, $attribute) {
		$results = static::getInstances($model, $attribute);
		if(count($results)>0) {
			return $results[0];
		} else {
			return null;
		}
	}
	/**
	 * Returns an instance of the specified uploaded file.
	 * The name can be a plain string or a string like an array element (e.g. 'Post[imageFile]', or 'Post[0][imageFile]').
	 * @param string $name the name of the file input field.
	 * @return CUploadedFile the instance of the uploaded file.
	 * Null is returned if no file is uploaded for the specified name.
	 */
	public static function getInstanceByName($name) {
		$results = static::getInstancesByName($name);
		if(count($results)>0) {
			return $results[0];
		} else {
			return null;
		}
	}

	/**
	 * Returns all uploaded files for the given model attribute. Usefull for multi-upload
	 *
	 * @param CModel $model     the model instance
	 * @param string $attribute the attribute name.
	 *
	 * @return array array of UploadedFile objects.
	 * @since  1.1.0
	 */
	public static function getInstances($model, $attribute) {
		$infos = [];
		$infos['original'] = $attribute;
		\CHtml::resolveNameID($model, $attribute, $infos);
		if(method_exists('\CHtml', 'modelName') === true) {
			$infos['class'] = \CHtml::modelName($model);
		} else {
			$infos['class'] = get_class($model);
		}
		$infos['attribute'] = $attribute;
		$infos['namelen'] = strlen($infos['name']);
		$files = [];
		if((isset($_POST[$infos['class']]) == true) && (isset(self::$_files[$infos['class']][$attribute]) == false)) {
			self::$_files[$infos['class']][$attribute] = [];
			self::searchData($infos, $_POST[$infos['class']]);
		}
		if(isset(self::$_files[$infos['class']][$attribute]) == true) {
			$files = self::$_files[$infos['class']][$infos['attribute']];
		}
		$results = [];
		foreach($files as $key => $value) {
			if(strncmp($key, $infos['name'], $infos['namelen']) === 0) {
				$results[] = $value;
			}
		}
		return $results;
	}


    /**
	 * Creates UploadedFile instances from $_FILE.
	 * @return array the UploadedFile instances
	 */
    private static function loadFiles()
    {
        if (self::$_files === null) {
            self::$_files = [];
            if (isset($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $class => $info) {
                    self::loadFilesRecursive($class, $info['name'], $info['tmp_name'], $info['type'], $info['size'], $info['error']);
                }
            }
        }

        return self::$_files;
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
    private static function loadFilesRecursive($key, $names, $tempNames, $types, $sizes, $errors)
    {
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
	 * Returns an array of instances for the specified array name.
	 *
	 * If multiple files were uploaded and saved as 'Files[0]', 'Files[1]',
	 * 'Files[n]'..., you can have them all by passing 'Files' as array name.
	 * @param string $name the name of the array of files
	 * @return array the array of CUploadedFile objects. Empty array is returned
	 * if no adequate upload was found. Please note that this array will contain
	 * all files from all subarrays regardless how deeply nested they are.
	 */
	public static function getInstancesByName($name) {
		$infos = [];
		$infos['original'] = $name;
		$infos['namelen'] = strlen($name);
		$files = [];
		if((isset($_POST[$name]) == true) && (isset(self::$_files[$name]) == false)) {
			self::$_files[$name] = array();
			self::searchDataByName($name, $_POST);
		}
		if(isset(self::$_files[$name]) === false) {
			$files = array();
		} else {
			$files = self::$_files[$name];
		}
		$results = [];
		if($files !== null) {
			foreach($files as $key => $value) {
				if(strncmp($key, $name, $infos['namelen']) === 0) {
					$results[] = $value;
				}
			}
		}
		return $results;
	}
	protected static function searchDataByName($name, $postData, $prevKey='') {
		$id = \CHtml::getIdByName($name);
		foreach($postData as $key => $value) {
			if($key === $name) {
				if(is_array($value) == true) {
					foreach($value as $idx => $data) {
						$myFile = self::getTargetPath().DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$data;
						if((file_exists($myFile)===true) && (is_file($myFile)==true)) {
							$fileInfo = pathinfo($myFile);
							self::$_files[$name][$name.'_'.$idx] = new static($data, $myFile, $fileInfo['extension'], filesize($myFile));
						}
					}
				} else {
					// single upload
					$myFile = self::getTargetPath().DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$value;
					if((file_exists($myFile)===true) && (is_file($myFile)==true)) {
						$fileInfo = pathinfo($myFile);
						self::$_files[$name] = new static($value, $myFile, $fileInfo['extension'], filesize($myFile));
					}
				}
			} elseif(is_array($value) == true) {
				self::searchData($name, $value, $prevKey.'['.$key.']');
			}
		}
	}
	/**
	 * Build correct path for current file
	 *
	 * @param string $targetFileUrl file url like : tmp://xxx or resource://xxx
	 * @param string $id            id of current target file
	 *
	 * @return string
	 * @since  2.0.0
	 */
	protected static function buildFilePath($targetFileUrl, $id=null) {
		if(strncasecmp('tmp://', $targetFileUrl, 6) === 0) {
			$targetFileUrl = str_replace('tmp://', static::getTargetPath().DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR, $targetFileUrl);
		} else {
			$targetFileUrl = false;
		}
		return $targetFileUrl;
	}

	/**
	 * Recursive method used to collect info data.
	 * The original method cannot be used anymore because $_FILES is not used.
	 *
	 *
	 * @param unknown_type $infos    model / attribute infos
	 * @param unknown_type $postData data to search in
	 * @param unknown_type $prevKey  concat keys to build correct name
	 */
	protected static function searchData($infos, $postData, $prevKey='') {
		foreach($postData as $key => $value) {
			if($key === $infos['attribute']) {
				if(is_array($value) == true) {
					// multi upload
					$testName = $infos['class'].$prevKey.'['.$infos['attribute'].']';
					$id = \CHtml::getIdByName($testName);

					foreach($value as $idx => $data) {
						// $myFile = self::getTargetPath().DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$data;
						$myFile = self::buildFilePath($data, $id);

						if(($myFile !== false) && (file_exists($myFile)===true) && (is_file($myFile)==true)) {
							$fileInfo = pathinfo($myFile);

							self::$_files[$infos['class']][$infos['attribute']][$testName.'_'.$idx] = new static($data, $myFile, $fileInfo['extension'], filesize($myFile), $infos['class'], $infos['attribute']);
						}
					}
				} else {
					$testName = $infos['class'].$prevKey.'['.$infos['attribute'].']';
					$id = \CHtml::getIdByName($testName);
					// single upload
					// $myFile = self::getTargetPath().DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$value;
					$myFile = self::buildFilePath($value, $id);
					if(($myFile !== false) && (file_exists($myFile)===true) && (is_file($myFile)==true)) {
						$fileInfo = pathinfo($myFile);
						self::$_files[$infos['class']][$infos['attribute']][$testName] = new static($value, $myFile, $fileInfo['extension'], filesize($myFile), $infos['class'], $infos['attribute']);
					}
				}
			} elseif(is_array($value) == true) {
				self::searchData($infos, $value, $prevKey.'['.$key.']');
			}
		}
	}

	/**
	 * Cleans up the loaded UploadedFile instances.
	 * This method is mainly used by test scripts to set up a fixture.
	 *
	 * @return void
	 * @since  1.1.0
	 */
	public static function reset() {
		self::$_files=null;
	}

	/**
	 * Constructor.
	 *
	 * Use {@link getInstance} to get an instance of an uploaded file.
	 *
	 * @param string  $name      the original name of the file being uploaded
	 * @param string  $tempName  the path of the uploaded file on the server.
	 * @param string  $extension the extension of the uploaded file
	 * @param integer $size      the actual size of the uploaded file in bytes
	 *
	 * @return UploadedFile
	 * @since  1.1.0
	 */
	public function __construct($name,$tempName,$extension,$size, $model, $attribute) {
		$this->_name=$name;
		$this->_tempName=$tempName;
		$this->_extensionName=$extension;
		$this->_size=$size;
		$this->_model = $model;
		$this->_attribute = $attribute;
	}

	/**
	 * String output.
	 * This is PHP magic method that returns string representation of an object.
	 * The implementation here returns the uploaded file's name.
	 *
	 * @return string
	 * @since 1.1
	 */
	public function __toString() {
		return $this->_name;
	}

	/**
	 * Saves the uploaded file.
	 *
	 * @param string  $file           the file path used to save the uploaded file
	 * @param boolean $deleteTempFile whether to delete the temporary file after saving.
	 *
	 * @return boolean
	 * @since  1.1.0
	 */
	public function saveAs($file,$deleteTempFile=true)	{
		if($deleteTempFile) {
			$result = copy($this->_tempName, $file);
			unlink($this->_tempName);
			if ($result === true) {
				$data  = $this->cleanUpPost($_POST);
				$_POST = $data;
				$this->cleanUpFiles();
			}
			return $result;
		}
		else if(is_uploaded_file($this->_tempName)) {
			if (copy($this->_tempName, $file) === true) {
				$data  = $this->cleanUpPost($_POST);
				$_POST = $data;
				$this->cleanUpFiles();
				return true;
			} else {
				return false;
			}
		}
		else
			return false;
	}

	/**
	 * This function remove the asyncfile attribute from post (To avoid double file rendering [datarendering + postrendering])
	 * and return the filtered data
	 *
	 * @param array $data data to filter out
	 *
	 * @return array
	 * @since  2.0.0
	 */
	private function cleanUpPost($data) {
		$cleanedData = [];
		if(is_array($data) === true) {
			foreach ($data as $key => $value) {
				if ($key === $this->_model) {

					foreach ($value as $attribute => $attrValue) {
						if ($attribute === $this->_attribute) {
							if (is_array($attrValue) === true) {
								foreach ($attrValue as $index => $fileName) {
									if ($fileName !== $this->getName()) {
										$cleanedData[$key][$attribute][] = $fileName;
									}
								}
							}
						} else {
							$cleanedData[$key][$attribute] = $attrValue;
						}
					}

				} elseif (is_array($value) === true) {
					$cleanedData[$key] = $this->cleanUpPost($value);
				} else {
					$cleanedData[$key] = $value;
				}
			}
		}
		return $cleanedData;
	}


	/**
	 * This function remove current file from the instance storage
	 *
	 * @return void
	 * @since  2.0.0
	 */
	private function cleanUpFiles() {
		$data = self::$_files;
		if (isset($data[$this->_model]) === true && isset($data[$this->_model][$this->_attribute]) === true) {
			foreach ($data[$this->_model][$this->_attribute] as $key => $file) {

				if ($file === $this) {
					unset($data[$this->_model][$this->_attribute][$key]);
				}

			}
			if (empty($data[$this->_model][$this->_attribute]) === true) {
				unset($data[$this->_model][$this->_attribute]);
			}
			if (empty($data[$this->_model]) === true) {
				unset($data[$this->_model]);
			}
			self::$_files = $data;
		}

	}

	/**
	 * Delete temporary file
	 *
	 * @return void
	 * @since  1.1.0
	 */
	public function delete() {
		if(file_exists($this->_tempName) == true) {
			unlink($this->_tempName);
		}
	}
	/**
	 * Get current file name of the file being uploaded
	 *
	 * @param boolean true to remove the 'tmp://' part
	 *
	 * @return string
	 * @since  2.0.0
	 */
	public function getName($clean=false) {
		if($clean === true) {
			return str_replace('tmp://', '', $this->_name);
		} else {
			return $this->_name;
		}
	}

	/**
	 * @return string the path of the uploaded file on the server.
	 *
	 * Note: we need to create some kind of garbage collector
	 */
	public function getTempName() {
		return $this->_tempName;
	}

	/**
	 * @return integer the actual size of the uploaded file in bytes
	 */
	public function getSize() {
		return $this->_size;
	}

	/**
	 * @return string the file extension name for {@link name}.
	 * The extension name does not include the dot character. An empty string
	 * is returned if {@link name} does not have an extension name.
	 */
	public function getExtensionName() {
		return $this->_extensionName;
	}

	/**
	 * Return the associate model.
	 *
	 * @return string
	 * @since  2.0.0
	 */
	public function getModel() {
		return $this->_model;
	}

	/**
	 * Return the associate attribute of the model.
	 *
	 * @return string
	 * @since  2.0.0
	 */
	public function getAttribute() {
		return $this->_attribute;
	}
}
