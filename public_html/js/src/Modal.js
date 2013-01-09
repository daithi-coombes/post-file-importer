var post_importer;

jQuery(document).ready(function($){
	
	post_importer = new PostFileImporter($);
	_post_importer.init();
});

/**
 * @class The main class for the post file importer
 * @namespace PostFileImporter
 */
var PostFileImporter = function($){
	
	/**
	 * Init method call
	 * 
	 * @public
	 * @memberof CityIndexPostImporter
	 */
	this.init = function(){
		;
	};
	
	/**
	 * Link callback to load service in modal window iframe.
	 *
	 * @public
	 * @memberof CityIndexPostImporter
	 * @deprecated iframes not allowed for most oauth2 grant requests
	 */
	this.connect = function( service ){
		$('#service-pane').attr('src', ci_post_importer_ajaxurl
			+'?action=ci_post_importer_load_service'
			+ '&service='+service
			+ '&_wpnonce='+ci_post_importer_nonces.services);
	};
};