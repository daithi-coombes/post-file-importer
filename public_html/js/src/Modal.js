var ci_post_importer;

jQuery(document).ready(function($){
	
	ci_post_importer = new CityIndexPostImporter($);
	ci_post_importer.init();
});

/**
 * @class The main javascript class for cityindex post importer
 * @namespace
 */
var CityIndexPostImporter = function($){
	
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
	 */
	this.connect = function( service ){
		$('#service-pane').attr('src', ci_post_importer_ajaxurl
			+'?action=ci_post_importer_load_service'
			+ '&service='+service
			+ '&_wpnonce='+ci_post_importer_nonces.services);
	};
};