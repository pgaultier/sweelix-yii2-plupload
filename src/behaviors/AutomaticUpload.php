<?php
/**
 * AutomaticUpload.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  behaviors
 * @package   sweelix.yii2.plupload.behaviors
 */

namespace sweelix\yii2\plupload\behaviors;
use sweelix\yii2\plupload\components\UploadedFile;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use Yii;
use Exception;
/**
 * This UploadedFile handle automagically the upload process in
 * models
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  behaviors
 * @package   sweelix.yii2.plupload.behaviors
 * @since     XXX
 */
class AutomaticUpload extends Behavior {
	public $attributes=[];

	public static $sanitizeLocale = 'fr_FR.UTF8';

	protected $shouldResaveFileWithArgs = false;
	public function events() {
		return [
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
			ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
			// ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
		];
	}

	public static function sanitize($name) {
		// we can sanitize file a litlle better anyway, this should tdo the trick with all noob users
		setlocale(LC_ALL, self::$sanitizeLocale);
		$name = iconv('utf-8','ASCII//TRANSLIT//IGNORE', $name);
		setlocale(LC_ALL, 0);
		return preg_replace('/([^a-z0-9\._\-])+/iu', '-', $name);
	}

	public function beforeInsert() {
		foreach($this->attributes as $attribute => $config) {
			if(isset($config['basePath']) === true) {
				$basePath = Yii::getAlias($config['basePath']);
			} else {
				$basePath = Yii::getAlias('@webroot');
			}
			if(isset($config['baseUrl']) === true) {
				$baseUrl = Yii::getAlias($config['baseUrl']);
			} else {
				$baseUrl = Yii::getAlias('@web');
			}

			$nbMatches = preg_match_all('/{([^}]+)}/', $basePath);
			// I don't know why they could differ but probably someone will use it someday :-D
			$nbMatches = $nbMatches + preg_match_all('/{([^}]+)}/', $baseUrl);
			if($nbMatches == 0) {
				// we can save everything now
				if(is_dir($basePath) == false) {
					if(mkdir($basePath, 0777, true) === false) {
						throw new Exception('Cannot create target directory');
					}
				}

				$attributeName = Html::getInputName($this->owner, $attribute);
				echo '<pre>';
				var_dump($attributeName);
				var_dump($_POST);
				$uploadedFiles = UploadedFile::getInstancesByName($attributeName);
				var_dump($uploadedFiles);
				echo '</pre>';
				$fileNames = $this->owner->{$attribute};
				$selectedFiles = preg_split('/[,]+/', $fileNames, -1, PREG_SPLIT_NO_EMPTY);
				$selectedFiles = array_map(function($el) {
					return trim($el);
				}, $selectedFiles);
				$savedFiles = [];
				foreach($uploadedFiles as $instance) {
					if(in_array($instance->name, $selectedFiles) === true) {
						$fileName = static::sanitize($instance->name);
						$targetFile = $basePath.DIRECTORY_SEPARATOR.$fileName;
						if($instance->saveAs($targetFile)) {
							$savedFiles[] = $baseUrl.'/'.$fileName;
						}
					}
				}
				$this->owner->{$attribute} = implode(',', $savedFiles);
			} else {
				$this->shouldResaveFileWithArgs[$attribute] = true;
			}
		}
	}

	public function afterInsert() {
		if(is_array($this->shouldResaveFileWithArgs) === true) {
			$updatedList = [];
			foreach($this->shouldResaveFileWithArgs as $attribute => $status) {
				if($status === true) {
					$config = $this->attributes[$attribute];
					if(isset($config['basePath']) === true) {
						$basePath = Yii::getAlias($config['basePath']);
					} else {
						$basePath = Yii::getAlias('@webroot');
					}
					if(isset($config['baseUrl']) === true) {
						$baseUrl = Yii::getAlias($config['baseUrl']);
					} else {
						$baseUrl = Yii::getAlias('@web');
					}
					$attributesToExpand = [];
					$nbMatches = preg_match_all('/{([^}]+)}/', $basePath, $matches);
					if($nbMatches > 0) {
						foreach($matches[1] as $expandAttribute) {
							$attributesToExpand['{'.$expandAttribute.'}'] = $this->owner->{$expandAttribute};
						}
					}
					$nbMatches = preg_match_all('/{([^}]+)}/', $baseUrl, $matches);
					if($nbMatches > 0) {
						foreach($matches[1] as $expandAttribute) {
							$attributesToExpand['{'.$expandAttribute.'}'] = $this->owner->{$expandAttribute};
						}
					}
					$search = array_keys($attributesToExpand);
					$replace = array_values($attributesToExpand);

					$basePath = str_replace($search, $replace, $basePath);
					$baseUrl = str_replace($search, $replace, $baseUrl);

					if(is_dir($basePath) == false) {
						if(mkdir($basePath, 0777, true) === false) {
							throw new Exception('Cannot create target directory');
						}
					}


					$attributeName = Html::getInputName($this->owner, $attribute);

					$uploadedFiles = UploadedFile::getInstancesByName($attributeName);

					$fileNames = $this->owner->{$attribute};
					$selectedFiles = preg_split('/[,]+/', $fileNames, -1, PREG_SPLIT_NO_EMPTY);
					$selectedFiles = array_map(function($el) {
						return trim($el);
					}, $selectedFiles);
					$savedFiles = [];
					foreach($uploadedFiles as $instance) {
						if(in_array($instance->name, $selectedFiles) === true) {

							$fileName = static::sanitize($instance->name);
							$targetFile = $basePath.DIRECTORY_SEPARATOR.$fileName;
							if($instance->saveAs($targetFile)) {
								$targetUrl = $baseUrl.'/'.$fileName;
								$savedFiles[] = $targetUrl;
							}
						}
					}
					$updatedList[$attribute] = $this->owner->{$attribute} = implode(',', $savedFiles);;
				}
			}
			if(empty($updatedList) === false) {
				// we cannot edit the model, we must reload it and resave
				$model = $this->owner;
				$class = $mode::className();
				$target = $class::find($this->owner->getPrimaryKey());
				$target->updateAttributes($updatedList);
			}
		}
	}

	public function beforeUpdate() {
		foreach($this->attributes as $attribute => $config) {
			if(isset($config['basePath']) === true) {
				$basePath = Yii::getAlias($config['basePath']);
			} else {
				$basePath = Yii::getAlias('@webroot');
			}
			if(isset($config['baseUrl']) === true) {
				$baseUrl = Yii::getAlias($config['baseUrl']);
			} else {
				$baseUrl = Yii::getAlias('@web');
			}


			if(is_dir($basePath) == false) {
				if(mkdir($basePath, 0777, true) === false) {
					throw new Exception('Cannot create target directory');
				}
			}

			$attributeName = Html::getInputName($this->owner, $attribute);
			$uploadedFiles = UploadedFile::getInstancesByName($attributeName);

			// case 1, there is no upload. Do nothing
			// var_dump($this->owner->{$attribute}, $uploadedFiles); die();
			$fileNames = $this->owner->{$attribute};
			$selectedFiles = preg_split('/[,]+/', $fileNames, -1, PREG_SPLIT_NO_EMPTY);
			$selectedFiles = array_map(function($el) {
				return trim($el);
			}, $selectedFiles);
			$savedFiles = [];
			foreach($uploadedFiles as $instance) {
				if(in_array($instance->name, $selectedFiles) === true) {
					if(empty($instance->tempName) === true) {
						// image was uploaded earlier
						// we should probably check if image is always available
						$fileName = static::sanitize($instance->name);
						$savedFiles[] = $baseUrl.'/'.$fileName;
					} else {
						$fileName = static::sanitize($instance->name);
						$targetFile = $basePath.DIRECTORY_SEPARATOR.$fileName;
						if($instance->saveAs($targetFile)) {
							$savedFiles[] = $baseUrl.'/'.$fileName;
						}
					}
				}
			}
			$this->owner->{$attribute} = implode(',', $savedFiles);
		}

	}


	public function isAutomatic($attribute) {
		return array_key_exists($attribute, $this->attributes);
	}

	public function getBasePath($attribute) {
		if(isset($this->attributes[$attribute]['basePath']) === true) {
			return $this->attributes[$attribute]['basePath'];
		} else {
			return null;
		}
	}
	public function getBaseUrl($attribute) {
		if(isset($this->attributes[$attribute]['baseUrl']) === true) {
			return $this->attributes[$attribute]['baseUrl'];
		} else {
			return null;
		}
	}
}