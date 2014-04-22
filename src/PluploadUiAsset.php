<?php
/**
 * File PluploadUiAsset.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.0.1
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
 * @version   1.0.1
 * @link      http://www.sweelix.net
 * @category  plupload
 * @package   sweelix.yii2.plupload
 * @since     1.0.0
 */
class PluploadUiAsset extends AssetBundle
{
    public $sourcePath = '@sweelix/yii2/plupload/assets';
    public $js = [
        'sweelix.plupload.ui.js',
    ];
    public $css = [
        'sweelix.plupload.ui.css',
    ];
    public $depends = [
        'sweelix\yii2\plupload\PluploadAsset',
    ];
}
