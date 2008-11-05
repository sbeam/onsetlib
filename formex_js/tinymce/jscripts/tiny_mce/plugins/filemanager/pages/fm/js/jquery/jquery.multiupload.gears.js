/**
 * $Id: jquery.multiupload.gears.js 528 2008-10-29 17:58:01Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

(function($) {
	if (!$.multiUpload.initialized && window.google && google.gears) {
		$.multiUpload.initialized = 1;

		// Override init function
		$.extend($.multiUpload.prototype, {
			init : function() {
				var up = this;

				$(up).bind('multiUpload:selectFiles', function(e) {
					var up = this, desk = google.gears.factory.create('beta.desktop');

					desk.openFiles(function(f) {
						var sf = [];

						if (f.length) {
							up._fireEvent('multiUpload:beforeFilesSelected');

							$(f).each(function() {
								var fo = {
									id : up.generateID(),
									name : this.name,
									blob : this.blob,
									size : this.blob.length,
									loaded : 0
								};

								up.files.push(fo);
								sf.push(fo);
							});

							up._fireEvent('multiUpload:filesSelected', [{files : sf}]);
							up._fireEvent('multiUpload:filesChanged');
						} else
							up._fireEvent('multiUpload:filesSelectionCancelled');
					}, {filter : $.map(up.settings.filter, function(v) {return '.' + v})});
				});

				$(up).bind('multiUpload:uploadFile', function(e, fo) {
					var req, up = this, chunkSize, chunk = 0, chunks, i, start, loaded = 0, curChunkSize;

					chunkSize = 1024 * 1024;
					chunks = Math.ceil(fo.blob.length / chunkSize);

					uploadNextChunk();

					function uploadNextChunk() {
						var url = up.settings.upload_url;

						if (fo.status)
							return;

						curChunkSize = Math.min(chunkSize, fo.blob.length - (chunk  * chunkSize));

						req = google.gears.factory.create('beta.httprequest');
						req.open('POST', url + (url.indexOf('?') == -1 ? '?' : '&') + 'name=' + escape(fo.name) + '&chunk=' + chunk + '&chunks=' + chunks + '&path=' + escape(up.settings.path));

						req.setRequestHeader('Content-Disposition', 'attachment; filename="' + fo.name + '"');
						req.setRequestHeader('Content-Type', 'application/octet-stream');
						req.setRequestHeader('Content-Range', 'bytes ' + chunk * chunkSize);

						req.upload.onprogress = function(pr) {
							fo.loaded = loaded + pr.loaded;
							up._fireEvent('multiUpload:fileUploadProgress', [{file : fo, loaded : fo.loaded, total : fo.size}]);
						};

						req.onreadystatechange = function() {
							var ar;

							if (req.readyState == 4) {
								if (req.status == 200) {
									ar = {file : fo, chunk : chunk, chunks : chunks, response : req.responseText};

									up._fireEvent('multiUpload:chunkUploaded', [ar]);

									if (ar.cancel) {
										fo.status = 2;
										up.uploadNext();
										return;
									}

									loaded += curChunkSize;
									chunk++;

									if (chunk >= chunks) {
										fo.status = 1;
										
										up._fireEvent('multiUpload:fileUploaded', [{file : fo, response : req.responseText}]);
										up._fireEvent('multiUpload:filesChanged');

										up.uploadNext();
									} else
										uploadNextChunk();
								} else
									up._fireEvent('multiUpload:uploadChunkError', [{file : fo, chunk : chunk, chunks : chunks, error : 'Status: ' + req.status}]);
							}
						};

						if (chunk < chunks)
							req.send(fo.blob.slice(chunk * chunkSize, curChunkSize));
					};
				});
			},

			_fireEvent : function(ev, ar) {
				var up = this;

				// Detach from Gears to ease up firebug debugging
				window.setTimeout(function() {
					$(up).trigger(ev, ar);
				}, 0);
			}
		});
	}
})(jQuery);