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
	public static function asyncInput($name, $value = null, $options = []) {
		$options['name'] = $name;

		// remove the trailing [] which is just annoying except to send multiple files
		if(substr($name, -2) === '[]') {
			$originalName = substr($name, 0, -2);
			$options['config']['multiSelection'] = true;
		} else {
			$originalName = $name;
			$options['config']['multiSelection'] = false;
		}
		if($value !== null) {
			$affectedFiles = preg_split('/[,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
			$instances = UploadedFile::getInstancesByName($originalName);
			$value = [];
			foreach($instances as $instance) {
				if(in_array($instance->name, $affectedFiles) === true) {
					$value[] = $instance;
				}
			}
		}
		if(isset($options['id']) === false) {
			$options['id'] = self::getIdByName($name);
		}
		$config = static::prepareAsyncInput($options);
		return static::renderAsyncInput($value, $options, $config);
	}

	public static function activeAsyncInput($model, $attribute, $options = []) {
		$name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : static::getAttributeValue($model, $attribute);
		$filters = [];
		foreach($model->getActiveValidators($attribute) as $validator) {
			if($validator instanceof FileValidator) {
				// we can set all the parameters
				if(empty($validator->types) === false) {
					$filters['mime_types'] = [
						[
							'title' => 'Allowed files',
							'extensions' => implode(',', $validator->types),
						]
					];
				}
				if($validator->maxSize !== null) {
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
	protected static function renderAsyncInput($values, $options, $config) {
		if(is_array($values) == true) {
			$uploadedFiles = null;
			foreach($values as $addedFile) {
				if($addedFile instanceof UploadedFile) {
					$uploadedFiles[] = [
						'name' => $addedFile->name,
						'tmp_name' => $addedFile->tempName,
						'type' => $addedFile->type,
						'error' => $addedFile->error,
						'size' => $addedFile->size,
					];
				}
			}
			if($uploadedFiles !== null) {
				$config['uploadedFiles'] = $uploadedFiles;
			}
		} elseif($values instanceof UploadedFile) {
			$config['uploadedFiles'][] = [
				'name' => $addedFile->name,
				'tmp_name' => $addedFile->tempName,
				'type' => $addedFile->type,
				'error' => $addedFile->error,
				'size' => $addedFile->size,
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

		$htmlTag = static::tag($tag, $content, $options);
		if((Yii::$app->getRequest()->isAjax === false) && (Yii::$app->getRequest()->isPjax === false)) {
			Yii::$app->getView()->registerJs($js);
			// Yii::$app->clientScript->registerScript($htmlOptions['id'], $js);
		} else {
			$htmlTag = $htmlTag."\n".static::script($js);
		}
		return $htmlTag;
	}


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
	public static function getIdByName($name) {
		return str_replace(array('[]','][','[',']',' '),array('','_','_','','_'),$name);
	}
}