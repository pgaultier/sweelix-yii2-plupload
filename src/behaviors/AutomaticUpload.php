<?php
/**
 * AutomaticUpload.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.0.0
 * @link      http://www.sweelix.net
 * @category  behaviors
 * @package   sweelix.yii2.plupload.behaviors
 */

namespace sweelix\yii2\plupload\behaviors;
use sweelix\yii2\plupload\components\UploadedFile;
use yii\base\Behavior;
use yii\validators\FileValidator;
use yii\db\ActiveRecord;
use yii\helpers\Json;
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
 * @version   1.0.0
 * @link      http://www.sweelix.net
 * @category  behaviors
 * @package   sweelix.yii2.plupload.behaviors
 * @since     1.0.0
 */
class AutomaticUpload extends Behavior {
	/**
	 * @var array handled attributes
	 */
	public $attributes=[];

	/**
	 * @var string define locale for sanitize (it would probably be better to use Yii::$app locale)
	 */
	public static $sanitizeLocale = 'fr_FR.UTF8';

	/**
	 * @var callable function used to serialize attributes. default to json
	 */
	public $serializeCallback;

	/**
	 * @var callable function used to unserialize attributes. default to json
	 */
	public $unserializeCallback;

	/**
	 * @var boolean check if we must wayt for afterInsert to save the files
	 */
	protected $modelShouldBeSaved = false;

	/**
	 * @var boolean check if we are "re-saving" data in afterSave (when we recompute the path)
	 */
	protected $modelIsUpdating = false;

	/**
	 * List of tracked events
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function events() {
		return [
			// ActiveRecord::EVENT_INIT => 'afterInit',
			ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
			ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
			ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
			ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
		];
	}

	/**
	 * Serialize file attribute when multifile upload is active
	 *
	 * @param string $attribute the attribute name
	 *
	 * @return void
	 * @since  1.0.0
	 */
	protected function serializeAttribute($attribute) {
		if($this->isMultifile($attribute) === true) {
			if(is_callable($this->serializeCallback) === true) {
				$this->owner->{$attribute} = call_user_func(array($this, 'serializeCallback'), $this->owner->{$attribute});
			} else {
				$this->owner->{$attribute} = Json::encode($this->owner->{$attribute});
			}
		} else {
			$realData = $this->owner->{$attribute};
			if(is_array($realData) === true) {
				$realData = array_pop($realData);
			}
			$this->owner->{$attribute} = $realData;
		}
	}

	/**
	 * Unserialize file attribute when multifile upload is active
	 *
	 * @param string $attribute the attribute name
	 *
	 * @return void
	 * @since  1.0.0
	 */
	protected function unserializeAttribute($attribute) {
		if(self::isMultifile($attribute) === true) {
			if(is_callable($this->unserializeCallback) === true) {
				$attributeContent = call_user_func(array($this, 'unserializeCallback'), $this->owner->{$attribute});
			} else {
				$attributeContent = Json::decode($this->owner->{$attribute});
			}

			if((is_array($attributeContent) === false) && (empty($attributeContent) === false)) {
				$attributeContent = [$attributeContent];
			}
			$this->owner->{$attribute} = $attributeContent;
		}
	}

	/**
	 * Clean up file name
	 *
	 * @param string $name file name to sanitize
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public static function sanitize($name) {
		// we can sanitize file a litlle better anyway, this should tdo the trick with all noob users
		setlocale(LC_ALL, self::$sanitizeLocale);
		$name = iconv('utf-8','ASCII//TRANSLIT//IGNORE', $name);
		setlocale(LC_ALL, 0);
		return preg_replace('/([^a-z0-9\._\-])+/iu', '-', $name);
	}

	/**
	 * Check if current attribute support multifile
	 *
	 * @param string $attribute check if file attribute support multifile
	 *
	 * @return boolean
	 * @since  1.0.0
	 */
	public function isMultifile($attribute) {
		$config = $this->getValidatorConfig($attribute);
		return $config['multiFile'];
	}

	/**
	 * Get max file size allowed for the attribute
	 *
	 * @param string $attribute the source attribute
	 *
	 * @return integer
	 * @since  XXX
	 */
	public function getMaxFileSize($attribute) {
		$config = $this->getValidatorConfig($attribute);
		return (int)$config['maxFileSize'];
	}

	/**
	 * Get file type allowed for the attribute
	 *
	 * @param string $attribute the source attribute
	 *
	 * @return string
	 * @since  XXX
	 */
	public function getFileTypes($attribute) {
		$config = $this->getValidatorConfig($attribute);
		return $config['allowedTypes'];
	}

	/**
	 * @var array lazy loaded file information
	 */
	private static $_validatorConfig = [];

	/**
	 * Retrieve the validator configuration
	 *
	 * @param string $attribute attribute which is validated
	 *
	 * @return array
	 * @since  XXX
	 */
	protected function getValidatorConfig($attribute) {
		if(isset(self::$_validatorConfig[$attribute]) === false) {
			self::$_validatorConfig[$attribute] = false;
			$config = [
				'multiFile' => false,
				'maxFileSize' => 0,
				'allowedTypes' => null,
			];
			foreach($this->owner->getActiveValidators($attribute) as $validator) {
				if($validator instanceof FileValidator) {
					// we can set all the parameters
					if($validator->maxFiles > 1) {
						// multi add brackets
						$config['multiFile'] = true;
					}
					if(empty($validator->types) === false) {
						$config['allowedTypes'] = implode(',', $validator->types);
					}
					if(($validator->maxSize !== null) && ($validator->maxSize > 0)) {
						$config['maxFileSize'] = $validator->maxSize;
					}
					// we should not have multiple file validators for the same file
					break;
				}
			}
			self::$_validatorConfig[$attribute] = $config;
		}
		return self::$_validatorConfig[$attribute];
	}
	/**
	 * Perform file save before insert if we can
	 * If not, we delay the file processing on after insert
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function beforeInsert() {
		if($this->modelIsUpdating === false) {
			foreach($this->attributes as $attribute => $config) {
				if($this->shouldExpandAliasPath($attribute) === true) {
					$this->modelShouldBeSaved = true;
					break;
				}
			}
			if($this->modelShouldBeSaved === false) {
				$this->beforeUpdate();
			} else {
				foreach($this->attributes as $attribute => $config) {
					$this->serializeAttribute($attribute);
				}
			}
		}
	}

	/**
	 * Perform file save after insert if we need to recompute the path
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function afterInsert() {
		if($this->modelIsUpdating === false) {
			if($this->modelShouldBeSaved === true) {
				// avoid to save everything twice
				$this->modelShouldBeSaved = false;

				foreach($this->attributes as $attribute => $config) {
					$this->unserializeAttribute($attribute);
				}
				$this->beforeUpdate();
				$attributes = array_keys($this->attributes);
				$this->modelIsUpdating = true;
				$this->owner->updateAttributes($attributes);
				$this->modelIsUpdating = false;
				foreach($this->attributes as $attribute => $config) {
					$this->unserializeAttribute($attribute);
				}
			}
		}
	}

	/**
	 * Unserialize attributes
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function afterFind() {
		foreach($this->attributes as $attribute => $config) {
			$this->unserializeAttribute($attribute);
		}
	}

	/**
	 * Remove useless data from the properties and return a clean array
	 *
	 * @param array $propertyData property files
	 *
	 * @return array
	 * @since  1.0.0
	 */
	protected function cleanUpProperty($propertyData) {
		$propertyData = array_map(function($el) {
			return trim($el);
		}, $propertyData);
		return array_filter($propertyData);
	}

	/**
	 * Like insert but we will never need to recompute the key
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function beforeUpdate() {
		if($this->modelIsUpdating === false) {
			foreach($this->attributes as $attribute => $config) {
				$aliasPath = $this->getAliasPath($attribute, true);

				if(is_array($this->owner->{$attribute}) === false) {
					$this->owner->{$attribute} = [$this->owner->{$attribute}];
				}
				// clean up attributes
				$this->owner->{$attribute} = $this->cleanUpProperty($this->owner->{$attribute});
				$selectedFiles = $this->owner->{$attribute};

				$savedFiles = [];
				$attributeName = Html::getInputName($this->owner, $attribute);
				$uploadedFiles = UploadedFile::getInstancesByName($attributeName);

				foreach($uploadedFiles as $instance) {
					if(in_array($instance->name, $selectedFiles) === true) {
						$fileName = static::sanitize($instance->name);
						if(empty($instance->tempName) === true) {
							// image was uploaded earlier
							// we should probably check if image is always available
							$savedFiles[] = $fileName;
						} else {
							$targetFile = Yii::getAlias($aliasPath.'/'.$fileName);
							$targetPath = pathinfo($targetFile, PATHINFO_DIRNAME);
							if(is_dir($targetPath) === false) {
								if(mkdir($targetPath, 0755, true) === false) {
									throw new Exception('Unable to create path : "'.$targetPath.'"');
								}
							}
							if($instance->saveAs($targetFile) === true) {
								//TODO: saved files must be removed - correct place would be in UploadedFile
								$savedFiles[] = $fileName;
							}
						}
					}
				}
				$this->owner->{$attribute} = $savedFiles;
				$this->serializeAttribute($attribute);
			}
		}
	}

	/**
	 * Should only reset attributes as expected
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function afterUpdate() {
		// UploadedFile::reset();
		if($this->modelIsUpdating === false) {
			foreach($this->attributes as $attribute => $config) {
				$this->unserializeAttribute($attribute);
			}
		}
	}

	/**
	 * Check if attibute is handled automagically
	 *
	 * @param string $attribute attribute to check
	 *
	 * @return boolean
	 * @since  1.0.0
	 */
	public function isAutomatic($attribute) {
		return array_key_exists($attribute, $this->attributes);
	}

	/**
	 * Return current file(s) attribute with the(ir) full path
	 *
	 * @param string  $attribute attribute to retrieve
	 * @param boolean $expanded  should we expand parameters if they are used in the path
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function getAsFilePath($attribute, $expanded=false) {
		if(($this->isMultifile($attribute) === true) && (is_array($this->owner->{$attribute}) === true) && (empty($this->owner->{$attribute}) === false)) {
			return array_map(function($el) use ($attribute, $expanded) {
				return Yii::getAlias($this->getAliasPath($attribute, $expanded).'/'.$el);
			}, $this->owner->{$attribute});
		} elseif(empty($this->owner->{$attribute}) === false) {
			return Yii::getAlias($this->getAliasPath($attribute, $expanded).'/'.$this->owner->{$attribute});
		} else {
			return null;
		}
	}

	/**
	 * Return current file(s) attribute with the(ir) full url
	 *
	 * @param string  $attribute attribute to retrieve
	 * @param boolean $expanded  should we expand parameters if they are used in the url
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function getAsFileUrl($attribute, $expanded=false) {
		if(($this->isMultifile($attribute) === true) && (is_array($this->owner->{$attribute}) === true)) {
			return array_map(function($el) use ($attribute, $expanded) {
				return Yii::getAlias($this->getAliasUrl($attribute, $expanded).'/'.$el);
			}, $this->owner->{$attribute});
		} elseif(empty($this->owner->{$attribute}) === false) {
			return Yii::getAlias($this->getAliasUrl($attribute, $expanded).'/'.$this->owner->{$attribute});
		} else {
			return null;
		}
	}

	/**
	 * Get Alias path for selected attribute
	 *
	 * @param  string $attribute name of selected attribute
	 * @param boolean $expand    should we expand the alias path with model values
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function getAliasPath($attribute, $expand=false) {
		if(isset($this->attributes[$attribute]['basePath']) === true) {
			$basePath = $this->attributes[$attribute]['basePath'];
			if($expand === true) {
				$expansionVars = $this->getAliasPathExpansionVars($attribute);
				if(empty($expansionVars) === false) {
					$basePath = str_replace(array_keys($expansionVars), array_values($expansionVars), $basePath);
				}
			}
			return $basePath;
		} else {
			return '@webroot';
		}
	}

	/**
	 * Check if current path should be expanded
	 *
	 * @param string $attribute attribute to check
	 *
	 * @return boolean
	 * @since  1.0.0
	 */
	public function shouldExpandAliasPath($attribute) {
		$aliasPath = $this->getAliasPath($attribute);
		return (preg_match_all('/{([^}]+)}/', $aliasPath)>0);
	}

	/**
	 * Get variables used for path expansion
	 *
	 * @param string $attribute attribute to check
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function getAliasPathExpansionVars($attribute) {
		$expansionVars = [];
		$aliasPath = $this->getAliasPath($attribute);
		if($aliasPath !== null) {
			$nbMatches = preg_match_all('/{([^}]+)}/', $aliasPath, $matches);
			if($nbMatches > 0) {
				foreach($matches[1] as $expandAttribute) {
					$expansionVars['{'.$expandAttribute.'}'] = $this->owner->{$expandAttribute};
				}
			}
		}
		return (empty($expansionVars) === true)?null:$expansionVars;
	}

	/**
	 * Get Alias URL for selected attribute
	 *
	 * @param string  $attribute name of selected attribute
	 * @param boolean $expand    should we expand the alias url with model values
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function getAliasUrl($attribute, $expand=false) {
		if(isset($this->attributes[$attribute]['baseUrl']) === true) {
			$baseUrl = $this->attributes[$attribute]['baseUrl'];
			if($expand === true) {
				$expansionVars = $this->getAliasUrlExpansionVars($attribute);
				if(empty($expansionVars) === false) {
					$baseUrl = str_replace(array_keys($expansionVars), array_values($expansionVars), $baseUrl);
				}
			}
			return $baseUrl;
		} else {
			return '@web';
		}
	}

	/**
	 * Check if current URL should be expanded
	 *
	 * @param string $attribute attribute to check
	 *
	 * @return boolean
	 * @since  1.0.0
	 */
	public function shouldExpandAliasUrl($attribute) {
		$aliasUrl = $this->getAliasUrl($attribute);
		return (preg_match_all('/{([^}]+)}/', $aliasUrl)>0);
	}

	/**
	 * Get variables used for URL expansion
	 *
	 * @param string $attribute attribute to check
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function getAliasUrlExpansionVars($attribute) {
		$expansionVars = [];
		$aliasUrl = $this->getAliasUrl($attribute);
		if($aliasUrl !== null) {
			$nbMatches = preg_match_all('/{([^}]+)}/', $aliasUrl, $matches);
			if($nbMatches > 0) {
				foreach($matches[1] as $expandAttribute) {
					$expansionVars['{'.$expandAttribute.'}'] = $this->owner->{$expandAttribute};
				}
			}
		}
		return (empty($expansionVars) === true)?null:$expansionVars;
	}
}
