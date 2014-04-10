// Script for showing map image file size on Doma pages.
// - Finds server image file size by making an async HEAD request (NOT downloading file).
// - Tries jpg first and falls back to png if not found.
// - Handles any map image folder name.
// Installation: Include a script section pointing to it in index.php and show_map.php
$().ready(function() {
	var url = window.location.href;
	
	var roundToMbytes = function(bytes) {
		var megabytes = bytes / (1024 * 1024);
		return Math.round(megabytes * 10) / 10;
	}
	
	// Page: User Map List
	if(url.indexOf('index.php') !== -1) {
		$('.map').each(function() {
		
			var imageLink = $(this).find('img:first').attr('src');
			var imageUrlWithoutExtension = imageLink.substring(0, imageLink.indexOf('.'));
			var infoDiv = $(this).children('.info');
			
			var appendSizeElement = function(contentLength){
				infoDiv.append('<div class="dicipline"> ' + roundToMbytes(contentLength) + ' MB</div>');
			}
			
			// Try jpg first - then png. Append result to infoDiv
			var request = $.ajax({
				 type: "head",
				 url: imageUrlWithoutExtension + '.jpg',
				 success: function() {
					 appendSizeElement( request.getResponseHeader("content-length") );
				},
				error: function() {
					var retryRequest = $.ajax({
						 type: "head",
						 url: imageUrlWithoutExtension + '.png',
						 success: function () {
							appendSizeElement(retryRequest.getResponseHeader("content-length"));
						}
					});
				}
			});
		});
	}
	
	// Page: Show Map
	if(url.indexOf('show_map.php') !== -1) {
		$('#mapImage').each(function() {
		
			var request = $.ajax({
					 type: "head",
					 url: $(this).attr('src'),
					 success: function () {
						var megabytes = roundToMbytes(request.getResponseHeader("content-length"));
						$('#propertyContainer').append('<div class="property"><span class="caption">' + megabytes + ' MB</span></div>');
					}
				});
		});
	}
});