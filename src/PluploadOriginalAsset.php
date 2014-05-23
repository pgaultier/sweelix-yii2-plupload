<?php
/**
 * File PluploadOriginalAsset.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.0.3
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
 * @version   1.0.3
 * @link      http://www.sweelix.net
 * @category  plupload
 * @package   sweelix.yii2.plupload
 * @since     1.0.0
 */
class PluploadOriginalAsset extends AssetBundle
{
    public $sourcePath = '@vendor/sweelix/plupload/js';
    public $js = [
        'plupload.full.min.js',
    ];
}
