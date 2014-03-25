<?php
/**
 * File PluploadAsset.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  plupload
 * @package   sweelix.yii2.plupload
 */

namespace sweelix\yii2\plupload;
use yii\web\AssetBundle;

/**
 * Class PluploadOriginalAsset embed plupload script files
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  plupload
 * @package   sweelix.yii2.plupload
 * @since     XXX
 */
class PluploadAsset extends AssetBundle {
	public $sourcePath = '@sweelix/yii2/plupload/assets';
	public $js = [
		'sweelix.plupload.js',
	];
    public $depends = [
        'yii\web\JqueryAsset',
        'sweelix\yii2\plupload\PluploadOriginalAsset',
    ];
}
