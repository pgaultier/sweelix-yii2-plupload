Sweelix Yii2 Plupload extension
===============================

Sweelix Plupload extension for Yii 2 has been created to ease Plupload integration.

Plupload is not affiliated with Sweelix

Be carefull, Plupload (http://www.plupload.com/) is multi-licensed.
Take care of the license which applies to :

* GPLv2 : http://www.plupload.com/license/gplv2
* Commercial : http://www.plupload.com/license/oem

Installation
------------

If you use Packagist for installing packages, then you can update your composer.json like this :

``` json
{
    "require": {
        "sweelix/yii2-plupload": "*"
    }
}
```

Howto use this extension
------------------------

Once package has been installed:

activate Image management,by adding it to Yii components

``` php
// Yii2 app configuration
components => [

    // ... exiting components

    'image' => [
        'class' => 'sweelix\yii2\image\Config',
        'quality' => 80,
        'cachingMode' => sweelix\image\Image::MODE_NORMAL,
        'urlSeparator' => '/',
        'cachePath' => '@webroot/cache',
        'cacheUrl' => '@web/cache',
        'errorImage' => 'error.jpg',
    ]

    // ... exiting components

]
```

add the extension in your ```Html``` helper class :

``` php

namespace app\components;

use yii\helpers\Html as BaseHtml;
use sweelix\yii2\plupload\traits\Plupload;

class Html extends BaseHtml
{
    // adding this trait allow easy access to plupload
    use Plupload;
}
```

### Basic usage

**Sample one** : one single file upload with basic UI and Automatic upload

The controller file

``` php
namespace app\controllers;

use sweelix\yii2\plupload\components\UploadedFile;
use yii\web\Controller;
use Yii;

/**
 * This is a basic controller
 */
class SiteController extends Controller {
    public function actions() {
        // add upload / preview and delete file management
        return [
            'async-upload' => 'sweelix\yii2\plupload\actions\UploadFile',
            'async-delete' => 'sweelix\yii2\plupload\actions\DeleteFile',
            'async-preview' => 'sweelix\yii2\plupload\actions\PreviewFile',
        ];
    }

    public function actionIndex() {
        if(isset($_POST['demoUpload']) === true) {
            $uploads = UploadedFile::getInstancesByName('demoUpload');
            // retrieve all uploaded files for name demoUpload
            foreach($uploads as $uploadedFile) {
                $uploadedFile->saveAs('@webroot/resources/'.$uploadedFile->name);
            }
            // ... perform correct redirection
        }

        $this->render('index');
    }
}
```

The index view file

``` php

// ...

echo Html::asyncInput('demoUpload', isset($_POST['demoUpload'])?$_POST['demoUpload']:null, ['config' => [
        'ui' => true,
        'auto' => true,
    ]]); ?>

//...

```

**Sample two** : multi-file upload with basic UI and Automatic upload


The controller file (*nothing was changed in the controller*)

``` php
namespace app\controllers;

use sweelix\yii2\plupload\components\UploadedFile;
use yii\web\Controller;
use Yii;

/**
 * This is a basic controller
 */
class SiteController extends Controller {
    public function actions() {
        // add upload / preview and delete file management
        return [
            'async-upload' => 'sweelix\yii2\plupload\actions\UploadFile',
            'async-delete' => 'sweelix\yii2\plupload\actions\DeleteFile',
            'async-preview' => 'sweelix\yii2\plupload\actions\PreviewFile',
        ];
    }

    public function actionIndex() {
        if(isset($_POST['demoUpload']) === true) {
            $uploads = UploadedFile::getInstancesByName('demoUpload');
            // retrieve all uploaded files for name demoUpload
            foreach($uploads as $uploadedFile) {
                $uploadedFile->saveAs('@webroot/resources/'.$uploadedFile->name);
            }
            // ... perform correct redirection
        }

        $this->render('index');
    }
}
```

The index view file, the square brackets here tell plupload to use multifile upload

``` php

// ...

echo Html::asyncInput('demoUpload[]', isset($_POST['demoUpload'])?$_POST['demoUpload']:null, ['config' => [
        'ui' => true,
        'auto' => true,
    ]]); ?>

//...

```

**Config parameter** this parameter allow the developper to configure [plupload](http://www.plupload.com/docs/Options)

Here are the default configuration

| PHP name          | Plupload name       | Default value |
|-------------------|---------------------|---------------|
| runtimes          | runtimes            | html5, html4  |
| multiSelection    | multi_selection     | false         |
| maxFileSize       | max_file_size       | 0             |
| chunkSize         | chunk_size          | 10Mb          |
| uniqueNames       | unique_names        | false         |
| flashSwfUrl       | flash_swf_url       | null          |
| silverlightXapUrl | silverlight_xap_url | null          |
| browseButton      | browse_button       | null          |
| dropElement       | drop_element        | null          |
| container         | container           | null          |
| multipart         | multipart           | null          |
| multipartParams   | multipart_params    | null          |
| requiredFeatures  | required_features   | null          |
| filters           | filters             | null          |
| headers           | headers             | null          |


### Model usage with manual file management

The model file

``` php
namespace app\models;
use yii\db\ActiveRecord;
use Yii;

/**
 * Basic active record with uploadId (pkey autoincrement) and uploadFile (text)
 */
class Upload extends ActiveRecord {
    public static function tableName() {
        return '{{uploads}}';
    }
    public function rules() {

        return [
            // this rule is used to configure plupload :
            //   * maxFiles   trigger multifile upload,
            //   * extensions trigger the plupload filters
            //   * maxSize    trigger the maxFileSize
            ['uploadFile', 'file', 'extensions' => ['jpg', 'png', 'm4a'], 'maxFiles' => 1, 'maxSize' => 450*1024],
        ];
    }
    public function attributeLabels() {
        return [
            'uploadId' => Yii::t('sweelix', 'Upload ID'),
            'uploadFile' => Yii::t('sweelix', 'Uploaded File'),
        ];
    }
}
```


The controller file


``` php
namespace app\controllers;

use app\models\Upload;
use sweelix\yii2\plupload\components\UploadedFile;
use yii\web\Controller;
use Yii;

/**
 * This is a basic controller
 */
class SiteController extends Controller
{
    public function actions() {
        // add upload / preview and delete file management
        return [
            'async-upload' => 'sweelix\yii2\plupload\actions\UploadFile',
            'async-delete' => 'sweelix\yii2\plupload\actions\DeleteFile',
            'async-preview' => 'sweelix\yii2\plupload\actions\PreviewFile',
        ];
    }

    public function actionIndex() {
        $fileUpload = new Upload();
        if($fileUpload->load($_POST) === true) {
            // ... perform pre save
            $uploads = UploadedFile::getInstances($fileUpload, 'uploadFile');
            // retrieve all uploaded files for name demoUpload
            foreach($uploads as $uploadedFile) {
                // ... save file ...
                $uploadedFile->saveAs('@webroot/resources/'.$uploadedFile->name);
            }

            // ... perform post file save
            $fileUpload->save();
            // ... perform correct redirection
        }

        $this->render('index', ['fileUpload' => $fileUpload]);
    }
}
```

The index view file

``` php

// ...

<?php echo Html::activeAsyncInput($fileUpload, 'uploadFile', ['config' => [
        'ui' => true,
        'auto' => true,
    ]]); ?>

//...

```

### Model usage with automatic file management

The model file

``` php
namespace app\models;
use sweelix\yii2\plupload\behaviors\AutomaticUpload;
use yii\db\ActiveRecord;
use Yii;

/**
 * Basic active record with uploadId (pkey autoincrement) and uploadFile (text)
 */
class Upload extends ActiveRecord
{
    public static function tableName() {
        return '{{uploads}}';
    }

    public function behaviors() {
        return [
            [
                'class' => AutomaticUpload::className(),
                'attributes' => [
                    'uploadFile' => [
                        // define where to save the file
                        'basePath' => '@webroot/resources',
                        // define the url to access the file
                        'baseUrl' => '@web/resources',
                    ],
                ]
            ]
        ];
    }

    public function rules() {
        return [
            // this rule is used to configure plupload :
            //   * maxFiles   trigger multifile upload,
            //   * extensions trigger the plupload filters
            //   * maxSize    trigger the maxFileSize
            ['uploadFile', 'file', 'extensions' => ['jpg', 'png', 'm4a'], 'maxFiles' => 1, 'maxSize' => 450*1024],
        ];
    }
    public function attributeLabels() {
        return [
            'uploadId' => Yii::t('sweelix', 'Upload ID'),
            'uploadFile' => Yii::t('sweelix', 'Uploaded File'),
        ];
    }
}
```


The controller file


``` php
namespace app\controllers;

use app\models\Upload;
use sweelix\yii2\plupload\components\UploadedFile;
use yii\web\Controller;
use Yii;

/**
 * This is a basic controller
 */
class SiteController extends Controller
{
    public function actions() {
        // add upload / preview and delete file management
        return [
            'async-upload' => 'sweelix\yii2\plupload\actions\UploadFile',
            'async-delete' => 'sweelix\yii2\plupload\actions\DeleteFile',
            'async-preview' => 'sweelix\yii2\plupload\actions\PreviewFile',
        ];
    }

    public function actionIndex() {
        $fileUpload = new Upload();
        if($fileUpload->load($_POST) === true) {
            // ... file save is performed automagically
            $fileUpload->save();
            // ... perform correct redirection
        }

        $this->render('index', ['fileUpload' => $fileUpload]);
    }
}
```

The index view file

``` php

// ...

<?php echo Html::activeAsyncInput($fileUpload, 'uploadFile', ['config' => [
        'ui' => true,
        'auto' => true,
    ]]); ?>

//...

```

Contributing
------------

All code contributions - including those of people having commit access -
must go through a pull request and approved by a core developer before being
merged. This is to ensure proper review of all the code.

Fork the project, create a [feature branch ](http://nvie.com/posts/a-successful-git-branching-model/), and send us a pull request.

