<?php
/**
 * File PluploadTrait.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  traits
 * @package   sweelix.yii2.plupload.traits
 */

namespace sweelix\yii2\plupload\traits;
use sweelix\yii2\plupload\components\UploadedFile;
use sweelix\yii2\plupload\PluploadAsset;
use sweelix\yii2\plupload\PluploadUiAsset;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\Request;
use yii\validators\FileValidator;
use Yii;

/*
 * This trait allow easy integration of plupload in Html
 *
 * To use the trait, add <code>use PluploadTrait;</code> in you "Html" class
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  traits
 * @package   sweelix.yii2.plupload.traits
 * @since     XXX
 */
trait Plupload {

	public static function activeAsyncInput($model, $attribute, $options = []) {
		$name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : static::getAttributeValue($model, $attribute);
		// handled by AutomaticUpload otherwise targetPathAlias must be set manually
		if($model->hasMethod('isAutomatic') === true) {
			if(($model->isAutomatic($attribute) === true) && ($model->getAliasPath($attribute) !== null)) {
				$options['config']['targetPathAlias'] = $model->getAliasPath($attribute);
				$options['config']['additionalParameters'] = $model->getAliasPathExpansionVars($attribute);
			}
		}
		$filters = [];
		foreach($model->getActiveValidators($attribute) as $validator) {
			if($validator instanceof FileValidator) {
				// we can set all the parameters
				if(empty($validator->types) === false) {
					$filters['mime_types'] = [
						[
							'title' => Yii::t('sweelix', 'Allowed files'),
							'extensions' => implode(',', $validator->types),
						]
					];
				}
				if(($validator->maxSize !== null) && ($validator->maxSize > 0)) {
					$filters['max_file_size'] = $validator->maxSize;
				}
				if($validator->maxFiles > 1) {
					// multi add brackets
					$name = $name.'[]';
				}
			}
		}
		$options['config']['filters'] = $filters;
		if (!array_key_exists('id', $options)) {
			$options['id'] = static::getInputId($model, $attribute);
		}
		return static::asyncInput($name, $value, $options);

	}


	/**
	 * Create an asynchronous file upload.
	 * Plupload specific configuration should be set in $options['config']
	 *
	 * @param  string       $name    name of the input (append [] for multifile upload)
	 * @param  string|array $value   the file(s) already uploaded (array for multifile upload)
	 * @param  array        $options @see static::input()
	 *
	 * @return string
	 * @since  XXX
	 */
	public static function asyncInput($name, $value = null, $options = []) {
		// prepare data
		$originalName = $name;
		if(empty($value) === true) {
			$value = null;
		}
		// prepare option data
		$options['name'] = $name;
		$options['config']['multiSelection'] = false;
		if(isset($options['id']) === false) {
			$options['id'] = self::getIdByName($name);
		}


		// remove the trailing [] which is just annoying except to send multiple files
		// we rely on [] to define the multiple file upload
		if(substr($name, -2) === '[]') {
			$originalName = substr($name, 0, -2);
			$options['config']['multiSelection'] = true;
			if($value !== null) {
				if(is_array($value) === false) {
					// force array for multifile
					$value = [$value];
				}
				$value = array_map(function($el) {
					// remove everything which is path related. It must be handled by the developper / model
					return pathinfo(trim($el), PATHINFO_BASENAME);
				}, $value);
			}
		} else {
			if($value !== null) {
				$value = pathinfo((string)$value, PATHINFO_BASENAME);
			}
		}

		if($value !== null) {
			// we must retrieve original data
			$instances = UploadedFile::getInstancesByName($originalName);

			// force check with arrays
			$affectedFiles = (is_array($value) === true)?$value:[$value];

			//translate everything into UploadedFile(s)
			$translatedValues = [];
			foreach($instances as $instance) {
				if(in_array($instance->name, $affectedFiles) === true) {
					$affectedFilesKeys = array_keys($affectedFiles, $instance->name);
					unset($affectedFiles[$affectedFilesKeys[0]]);
					$translatedValues[] = $instance;
				}
			}
			foreach($affectedFiles as $remainingFile) {
				// we are looking at already saved files. Reinit as expected
				$translatedValues[] = new UploadedFile([
	                'name' => $remainingFile,
	                'tempName' => null,
	                'type' => 'application/octet-stream',
	                'size' => null,
	                'error' => 0,
	            ]);
			}

			$value = $translatedValues;

			if($options['config']['multiSelection'] === false) {
				// get first value in case we are not multi
				$value = array_pop($value);
			}
		}

		$config = static::prepareAsyncInput($options);
		return static::renderAsyncInput($value, $options, $config);
	}

	protected static function renderAsyncInput($values, $options, $config) {
		if(is_array($values) == true) {
			$uploadedFiles = null;
			foreach($values as $addedFile) {
				if($addedFile instanceof UploadedFile) {
					$uploadedFiles[] = [
						'name' => (string)$addedFile->name,
						'tmp_name' => (string)$addedFile->tempName,
						'type' => (string)$addedFile->type,
						'error' => (int)$addedFile->error,
						'size' => (int)$addedFile->size,
					];
				}
			}
			if($uploadedFiles !== null) {
				$config['uploadedFiles'] = $uploadedFiles;
			}
		} elseif($values instanceof UploadedFile) {
			$config['uploadedFiles'][] = [
				'name' => (string)$values->name,
				'tmp_name' => (string)$values->tempName,
				'type' => (string)$values->type,
				'error' => (int)$values->error,
				'size' => (int)$values->size,
			];
		}
		unset($options['name']);

		if(isset($options['tag']) == true) {
			$tag = $options['tag'];
			unset($options['tag']);
		} else {
			$tag = 'button';
			if(isset($options['type']) == false) {
				$options['type'] = 'button';
			}
		}
		if(isset($options['content']) == true) {
			$content = $options['content'];
			unset($options['content']);
		} else {
			$content = Yii::t('sweelix', 'Browse ...');
		}

		$js = 'jQuery(\'#'.$options['id'].'\').asyncUpload('.Json::encode($config).');';
		unset($options['uploadOptions']);
		unset($options['value']);

		// append needed javascript to current view
		Yii::$app->getView()->registerJs($js);
		return static::tag($tag, $content, $options);
	}

	/**
	 * Prepare everything for plupload, attach javascript, ...
	 *
	 * @param  array &$options raw option values (plupload specific parts will be removed)
	 *
	 * @return array
	 * @since  XXX
	 */
	protected static function prepareAsyncInput(&$options) {
		Yii::$app->getSession()->open();
		$config = [
			'runtimes' => 'html5, html4', // default to html5 / html4 / flash
			// 'dropElement' => $htmlOptions['id'].'_zone',
			// 'dropText' => \Yii::t('sweelix', 'Drop files here'),
			'ui' => false,
			'multiSelection' => false,
			'url' => [
				'async-upload',
				'id'=>$options['id'],
				'key' => Yii::$app->getSession()->getId(),
			],
			'urlDelete' => [
				'async-delete',
				'id'=>$options['id'],
				'key' => Yii::$app->getSession()->getId(),
			],
			'urlPreview' => [
				'async-preview',
				'id'=>$options['id'],
				'key' => Yii::$app->getSession()->getId(),
			],
			'headers' => [
				Request::CSRF_HEADER => Yii::$app->getRequest()->getCsrfToken()
			],
		];

		if(isset($options['config']) == true) {
			if(isset($options['config']['targetPathAlias']) === true) {
				$config['urlPreview']['targetPathAlias'] = $options['config']['targetPathAlias'];
			}
			if(isset($options['config']['additionalParameters']) === true) {
				if(is_array($options['config']['additionalParameters']) === true) {
					foreach($options['config']['additionalParameters'] as $key => $value) {
						$options['config']['urlPreview'][$key] = $value;
					}
				}
				unset($options['config']['additionalParameters']);
			}
			if(isset($options['config']['urlPreview']) && is_array($options['config']['urlPreview']) === true) {
				$options['config']['urlPreview'] = array_merge(
					$options['config']['urlPreview'],
					[
						'id'=>$options['id'],
						'key' => Yii::$app->getSession()->getId(),
					]
				);
			}
			if(isset($options['config']['urlDelete']) && is_array($options['config']['urlDelete']) === true) {
				$options['config']['urlDelete'] = array_merge(
					$options['config']['urlDelete'],
					[
						'id'=>$options['id'],
						'key' => Yii::$app->getSession()->getId(),
					]
				);
			}
			if(isset($options['config']['url']) && is_array($options['config']['url']) === true) {
				$options['config']['url'] = array_merge(
					$options['config']['url'],
					[
						'id'=>$options['id'],
						'key' => Yii::$app->getSession()->getId(),
					]
				);
			}

			$config = ArrayHelper::merge($config, $options['config']);
			unset($options['config']);
		}
		foreach(['url', 'urlDelete', 'urlPreview'] as $rawUrl) {
			if(isset($config[$rawUrl]) === true) {
				$config[$rawUrl] = Url::to($config[$rawUrl]);
			}
		}
		$config['realName'] = $options['name'];
		if($config['ui'] === true) {
			$view = Yii::$app->getView();
			PluploadUiAsset::register($view);
		} else {
			$view = Yii::$app->getView();
			PluploadAsset::register($view);
		}
		$pluploadAssetBundle = Yii::$app->getAssetManager()->getBundle(PluploadAsset::className());
		if((strpos($config['runtimes'], 'flash') !== false) || (strpos($config['runtimes'], 'silverlight') !== false)){
			$config['flashSwfUrl'] = $pluploadAssetBundle->baseUrl.'/Moxie.swf';
			$config['silverlightXapUrl'] = $pluploadAssetBundle->baseUrl.'/Moxie.xap';
		}
		return $config;
	}

	/**
	 * Generates a valid HTML ID based on name.
	 * @param string $name name from which to generate HTML ID
	 * @return string the ID generated based on name.
	 */
	private static function getIdByName($name) {
		return str_replace(array('[]','][','[',']',' '),array('','_','_','','_'),$name);
	}
}