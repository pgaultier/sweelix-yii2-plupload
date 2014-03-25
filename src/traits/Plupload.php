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
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Json;
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
		/*
		if($value === null) {
			$value = UploadedFile::getInstancesByName($name);
		}
		*/
        $options['value'] = $value === null ? null : (string) $value;
        if(isset($options['id']) === false) {
        	throw new InvalidConfigException('ID for async file input is mandatory');
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
						'fileName' => $addedFile->getName(),
						'fileSize' => $addedFile->getSize(),
						'status' => true
					];
				}
			}
			if($uploadedFiles !== null) {
				$config['uploadedFiles'] = $uploadedFiles;
			}
		} elseif($values instanceof UploadedFile) {
			$config['uploadedFiles'][] = [
				'fileName' => $addedFile->getName(),
				'fileSize' => $addedFile->getSize(),
				'status' => true
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
			'runtimes' => 'html5, html4, flash', // default to html5 / html4 / flash
			// 'dropElement' => $htmlOptions['id'].'_zone',
			// 'dropText' => \Yii::t('sweelix', 'Drop files here'),
			'ui' => false,
			'multiSelection' => false,
			'url' => [
				'asyncUpload',
				'id'=>$options['id'],
				'key' => Yii::$app->getSession()->getId(),
			],
			'urlDelete' => [
				'asyncDelete',
				'id'=>$options['id'],
				'key' => Yii::$app->getSession()->getId(),
			],
			'urlPreview' => null,
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
		if($config['multiSelection'] == true) {
			$config['realName'] .= '[]';
		}

		$view = Yii::$app->getView();
		PluploadAsset::register($view);
		if($config['ui'] === false) {

		}
		if((strpos($config['runtimes'], 'flash') !== false) || (strpos($config['runtimes'], 'silverlight') !== false)){
			$pluploadAssetBundle = Yii::$app->getAssetManager()->getBundle(PluploadAsset::className());
		 	$config['flashSwfUrl'] = $pluploadAssetBundle->baseUrl.'/Moxie.swf';
		 	$config['silverlightXapUrl'] = $pluploadAssetBundle->baseUrl.'/Moxie.xap';
 		}
		return $config;
	}
}