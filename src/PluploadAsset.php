<?php
/**
 * File Image.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  web
 * @package   sweelix.yii2.web
 */

namespace sweelix\yii2\plupload;
use yii\web\AssetBundle;

/**
 * Class Image wraps @see sweelix\image\Image and
 * Yii into one class to inherit Yii config
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  web
 * @package   sweelix.yii2.web
 * @since     XXX
 */
class PluploadAsset extends AssetBundle {
	public $sourcePath = '@vendor/sweelix/plupload/js';
	public $js = [
		'plupload.full.min.js',
	];
	/*
    public $depends = [
        'yii\web\JqueryAsset',
    ];
    */
}
