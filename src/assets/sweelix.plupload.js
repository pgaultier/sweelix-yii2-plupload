/**
 * File jquery.sweelix.plupload.js
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

(function (window, jQuery, plupload) {

	function prepareFakeAttribute(hiddenId, config) {
		var fakeAttribute = [],
			fieldName = config.realName;
		fieldName = fieldName.replace(/\[[0-9]*\]/, '');
		jQuery('#'+hiddenId+' div.file-block').each(function(idx, el) {
			fakeAttribute.push(jQuery(el).find('input[type=hidden]:first').val());
		});
		jQuery('#'+hiddenId+' input.fake-attribute').remove();
		jQuery('#'+hiddenId).append('<input type="hidden" class="fake-attribute" name="' + fieldName + '" value="' + fakeAttribute.join() + '" />');
	}

	function createHiddenField(up, file, hiddenId, config) {
		var el, fieldName, offset, checkModel;
		if(parseInt(file.extendedData.error, 10) === 0) {
			jQuery('.sweeploadEmpty').remove();
			if(up.getMultiSelection() === false) {
				jQuery('#'+hiddenId+' div.file-block').each(function(idx, el){
					var fileId = jQuery(el).attr('id');
					fileId = fileId.substring(1);
					up.asyncDelete(up.getFile(fileId), jQuery(el).find('input[type=hidden]:first').val());
				});
				fieldName = config.realName;
				offset = '';
			} else {
				offset = '[' + jQuery('#'+hiddenId+' div.file-block').length + ']';
				fieldName = config.realName;
				fieldName = fieldName.replace(/\[[0-9]*\]/, '');
			}
			checkModel = fieldName.match(/([a-z0-9]+)\[([^\]]+)\]/i);
			if((checkModel !== null) && (checkModel.length === 3)) {
				// Model[field]
				// Model][field
				fieldName = checkModel[1] + '][' + checkModel[2];
			}
			el = jQuery('<div class="file-block" id="h'+file.id+'"></div>');
			el.append('<input type="hidden" name="_plupload['+fieldName+'][name]'+offset+'" value="'+file.extendedData.name+'" />');
			el.append('<input type="hidden" name="_plupload['+fieldName+'][tmp_name]'+offset+'" value="'+file.extendedData.tmp_name+'" />');
			el.append('<input type="hidden" name="_plupload['+fieldName+'][type]'+offset+'" value="'+file.extendedData.type+'" />');
			el.append('<input type="hidden" name="_plupload['+fieldName+'][size]'+offset+'" value="'+file.extendedData.size+'" />');
			el.append('<input type="hidden" name="_plupload['+fieldName+'][error]'+offset+'" value="'+file.extendedData.error+'" />');
			jQuery('#'+hiddenId).append(el);
			prepareFakeAttribute(hiddenId, config);
		}
	}


	jQuery.fn.asyncUpload = function (config) {
		config = config||{};

		var baseConfig = {
			'runtimes' : (!!config.runtimes)?config.runtimes:'flash',
			'multi_selection': (!!config.multiSelection)?config.multiSelection:false,
			'max_file_size': (!!config.maxFileSize)?config.maxFileSize:'10mb',
			'chunk_size':(!!config.chunkSize)?config.chunkSize:'10mb',
			'unique_names':(!!config.uniqueNames)?config.uniqueNames:false,
			'url':config.url,
			'flash_swf_url':(!!config.flashSwfUrl)?config.flashSwfUrl:null,
			'silverlight_xap_url':(!!config.silverlightXapUrl)?config.silverlightXapUrl:null,
			'browse_button':(!!config.browseButton)?config.browseButton:null,
			'drop_element':(!!config.dropElement)?config.dropElement:null,
			'container':(!!config.container)?config.container:null,
			'multipart':(!!config.multipart)?config.multipart:null,
			'multipart_params':(!!config.multipartParams)?config.multipartParams:null,
			'required_features':(!!config.requiredFeatures)?config.requiredFeatures:null,
			'headers':(!!config.headers)?config.headers:null
		},
		eventsHandler = {},
		uploadedFiles = (!!config.uploadedFiles)?config.uploadedFiles:null;
		if(baseConfig.container === null) {
			delete baseConfig.container;
		}
		if(!!config.filters) {
			baseConfig.filters = config.filters;
		}
		if(baseConfig.hasOwnProperty('filters') === false) {
			baseConfig.filters = {};
		}
		baseConfig.filters.prevent_duplicates = true;

		if(baseConfig.hasOwnProperty('headers') === false) {
			baseConfig.headers = {};
		}
		if(baseConfig.headers.hasOwnProperty('X-Requested-With') === false) {
			baseConfig.headers['X-Requested-With'] = 'XMLHttpRequest';
		}
		//jQuery.extend(baseConfig, {'headers':{'X-Requested-With':'XMLHttpRequest'}});

		return this.each(function () {
			// prepare element : button + hidden container
			var id, hiddenId, uploader;
			id = jQuery(this).attr('id');
			hiddenId = jQuery(this).attr('id')+'_hidden';
			baseConfig.browse_button = id;
			uploader = new plupload.Uploader(baseConfig);

			if(config.ui === true) {
				if(typeof(SweeploadBasicUI) == 'function') {
					eventsHandler = new SweeploadBasicUI();
				}
			} else if(typeof(config.ui) === 'object') {
				eventsHandler = config.ui;
				if(typeof(eventsHandler.setInitialised) === 'function') {
					eventsHandler.setInitialised(false);
				}
			}

			// extend the puloader to return needed elements
			uploader.getId = function() {
				return id;
			};
			uploader.getHiddenId = function() {
				return hiddenId;
			};
			uploader.getDeleteUrl = function() {
				return (!!config.urlDelete)?config.urlDelete:null;
			};
			uploader.getPreviewUrl = function() {
				return (!!config.urlPreview)?config.urlPreview:null;
			};
			uploader.getEventHandlerConfig = function() {
				return (!!config.eventHandlerConfig)?config.eventHandlerConfig:{};
			};
			uploader.getMultiSelection = function() {
				return baseConfig.multi_selection;
			};

			uploader.formatSize = plupload.formatSize;

			uploader.asyncDelete = function(file){
				if(uploader.getDeleteUrl() !== null) {
					jQuery.ajax({
						'url' : uploader.getDeleteUrl(),
						'data' : file.extendedData,
					}).done(function(data, textStatus, jqXHR){
						if(eventsHandler.hasOwnProperty('AsyncDelete') && (typeof(eventsHandler.AsyncDelete) === 'function')) {
							eventsHandler.AsyncDelete(file);
						}
					}).always(function() {
						uploader.removeFile(file);
					});
				} else {
					uploader.removeFile(file);
				}
			};


			if(eventsHandler.hasOwnProperty('PostInit')) {
				uploader.bind('PostInit', eventsHandler.PostInit);
				// we should not delete events.
				// delete events['PostInit'];
			}


			uploader.init();
			jQuery('#'+id).append('<div style="display:none;" id="'+hiddenId+'" ><input type="hidden" class="sweeploadEmpty" name="'+config.realName+'" value="" /></div>');

			jQuery.each(eventsHandler, function(key, callback) {
				// do not rebind post init
				if((key !=='PostInit') && (key !== 'AsyncDelete')) {
					uploader.bind(key, callback);
				}
			});

			uploader.bind('FileUploaded', function(up, file, response) {
				var json;
				if(typeof(response.response) === 'string') {
					json = jQuery.parseJSON(response.response);
				} else {
					json = response.response;
				}

				file.extendedData = json;
				// for each uploaded file create the support hidden field
				createHiddenField(uploader, file, hiddenId, config);
			});

			uploader.bind('FilesRemoved', function(up, files) {
				jQuery.each(files,  function(i, file){
					if(jQuery('#h'+file.id).length > 0) {
						// we should handle delete only for temp files
						uploader.asyncDelete(file);
						jQuery('#h'+file.id).remove();
					}
					if(jQuery('#'+hiddenId+' div.file-block').length === 0) {
						jQuery('#'+hiddenId).append('<input type="hidden" class="sweeploadEmpty" name="'+config.realName+'" value="" />');
					}
				});
				prepareFakeAttribute(hiddenId, config);

			});

			if(!!uploadedFiles) {
				uploader.bind('PostInit',
				function() {
					// if we have files to show, we should present them as uploaded
					var jsFiles = [];
					jQuery.each(uploadedFiles, function(idx, file) {
						//TODO: add missing properties
						var jsFile = new plupload.File(plupload.guid());
						jsFile.name = file.name;
						jsFile.size = file.size;
						jsFile.origSize = file.size;
						jsFile.extendedData = file;
						jsFile.status = plupload.DONE;
						jsFile.percent = 100;
						jsFile.destroy = function(){}; // fake the original destroy function
						jsFiles.push(jsFile);
					});

					uploader.trigger('FilesAdded', jsFiles);

					jQuery.each(jsFiles, function(idx, jsFile){
						var response = {
							'response' : jsFile.extendedData,
							'status' : true
						};


						uploader.trigger('FileUploaded', jsFile, response);
					});

					uploader.trigger('UploadComplete', jsFiles);
					uploader.refresh(); // not sure if this is needed
				}
				);
			}

			if((typeof( eventsHandler) === 'object') && (typeof(eventsHandler.setInitialised) === 'function')) {
				eventsHandler.setInitialised(true);
			}
			// is it linked to the ui ? probably
			if(!!config.auto) {
				uploader.bind('FilesAdded', function(up, file) {
					up.refresh();
					up.start();
				});
			}
			window['uploader_'+uploader.getId()] = uploader;
		});
	};
})(window, jQuery, plupload);
