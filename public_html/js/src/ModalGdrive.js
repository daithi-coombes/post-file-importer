var ci_post_importer_gdrive;

jQuery(document).ready(function($){
	
	ci_post_importer_gdrive = new CityIndexPostImporterGdrive($);
	
});

var CityIndexPostImporterGdrive = function($){
	
	/**
	 * Returns array of url vars
	 *
	 * @method
	 * @memberof CityIndexPostImporterGdrive
	 * @link http://papermashup.com/read-url-get-variables-withjavascript/
	 */
	function getUrlVars() {
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}
};