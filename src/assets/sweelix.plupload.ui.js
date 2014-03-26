/**
 * File jquery.sweelix.plupload.ui.js
 *
 * This is the default handler for plupload
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   2.0.0
 * @link      http://www.sweelix.net
 * @category  js
 * @package   sweelix.yii1.web.js
 */

	function SweeploadBasicUI() {
		var uploader;
		var self;
		function cutName(fileName){
			var name = fileName;
			if (fileName.length > 24) {
				name = fileName.substr(0,12) + '...' + fileName.substr(fileName.length-12,fileName.length)
			}
			return name;
		}
		function getContainerId() {
			return uploader.getId()+'_list';
		}
		function getDropZoneId() {
			return uploader.getId()+'_zone';
		}
		function formatSize(size) {
			return uploader.formatSize(size)
		}
		function getDeleteUrl() {
			return uploader.getDeleteUrl();
		}
		function getPreviewUrl() {
			return uploader.getPreviewUrl();
		}
		function getConfig() {
			return uploader.getEventHandlerConfig();
		}
		this.Error = function(up, error) {
			alert(error.message);
			switch(error.code) {
				case plupload.GENERIC_ERROR:
					break;
				case plupload.HTTP_ERROR:
					break;
				case plupload.GENERIC_ERROR:
					break;
				case plupload.IO_ERROR:
					break;
				case plupload.SECURITY_ERROR:
					break;
				case plupload.INIT_ERROR:
					break;
				case plupload.FILE_SIZE_ERROR:
					break;
				case plupload.FILE_EXTENSION_ERROR:
					break;
				case plupload.IMAGE_FORMAT_ERROR:
					break;
				case plupload.IMAGE_DIMENSIONS_ERROR:
					break;
				default:
					break;
			}

		}
		this.AsyncDelete = function(file){

		}
		this.FilesRemoved = function (up, files) {
			jQuery.each(files,  function(i, file){
				jQuery('#'+file.id).fadeOut('slow', function(){ jQuery(this).remove(); });
			});
		};
		this.UploadProgress = function (up, file) {
			jQuery('#'+getContainerId()+' #'+file.id+' div.progress').css({width:file.percent+'%'});
		};

		this.PostInit = function(up) {
			uploader = up;
			jQuery('#'+up.getId()).after('<ul id="'+getContainerId()+'" class="filesContainer"> </ul>');
		}
		this.FilesAdded = function (up, files) {
			jQuery.each(files,  function(i, file){
				jQuery('#'+getContainerId()).append('<li id="'+ file.id + '" class="fileContainer" title="'+file.name+'">' + cutName(file.name.replace('tmp://', '')) + ' ('+ formatSize(file.size) +')<div class="progressBar"><div class="progress"></div></div></li>');
			});
			// up.refresh();
		};
		this.FileUploaded = function (up, file, response) {
			var json = jQuery.parseJSON(response.response);
			var name = json.tmp_name;
			if(json.error == 0) {
				jQuery('#'+getContainerId()+' #'+file.id+' div.progress').css({width:'100%'});
				var remove = jQuery('<a href="#" class="close">X</a>');
				remove.one('click', function(evt){
					evt.preventDefault();
					self.AsyncDelete(file, name);
					uploader.removeFile(file, name);
				});
				jQuery('#'+getContainerId()+' #'+file.id).prepend(remove);
				jQuery.ajax({
					'url' : getPreviewUrl(),
					'data' : {
						'tmp_name' : json.tmp_name,
						'name' : json.name,
						'mode' : 'json'
					}
				}).done(function(data){
					if(data.path != null) {
						var element = jQuery('<a href="'+data.path+'" target="_blank"><img src="'+data.url+'" /></a>')
						var config = getConfig();
						if('linkClass' in config) {
							element.addClass(config['linkClass']);
						}
						if('store' in config) {
							element.data('store', config['store'])
						}
						if(data.image == false) {
							element.after(jQuery('<br/><span>'+data.path+'</span>'));
						}
					} else {
						var element = jQuery('<img src="'+data.url+'" />');
					}
					jQuery('#'+getContainerId()+' #'+file.id).append(element);
				});

			}
		};
		self = this;

	}
