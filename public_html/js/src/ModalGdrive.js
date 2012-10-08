var ci_post_importer_gdrive;

jQuery(document).ready(function($){
	
	ci_post_importer_gdrive = new CityIndexPostImporterGdrive($);
	$('#file-list').jstree( ci_post_importer_gdrive.jstree_settings() );
	
});

/**
 * 
 */
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
	};
	
	/**
	 * Returns settings object for jquery.jsTree plugin.
	 * 
	 * @method
	 * @memberof CityIndexPostImporterGdrive
	 * @link http://www.jstree.com/documentation/
	 * @return object
	 */
	function jstree_settings(){
		return {
			"types": {
				"valid_children" : ["folder","file"],
				"types" : {
					"file" : {
						"valid_children" : "none",
						"icon" : {
							"image" : "https://ssl.gstatic.com/docs/doclist/images/icon_10_document_list.png"
						}
					},
					"folder" : {
						"valid_children" : "all",
						"icon" : {
							"image": "https://ssl.gstatic.com/docs/doclist/images/collectionsprite_1.png"
						}
					}
				}
			},
			plugins: [ "themes", "html_data", "json_data", "ui", "types" ]
		}
	}
};