var ci_post_importer_gdrive;

jQuery(document).ready(function($){
	
	ci_post_importer_gdrive = new CityIndexPostImporterGdrive($);
	$('#file-list').jstree( ci_post_importer_gdrive.jstree_settings() );
	
});

/**
 * This is the main class.
 */
var CityIndexPostImporterGdrive = function($){
	
	/**
	 * Returns array of url vars.
	 *
	 * @method
	 * @public
	 * @memberof CityIndexPostImporterGdrive
	 * @link http://papermashup.com/read-url-get-variables-withjavascript/
	 */
	this.getUrlVars = function() {
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
	 * @public
	 * @memberof CityIndexPostImporterGdrive
	 * @link http://www.jstree.com/documentation/
	 * @return object
	 */
	this.jstree_settings = function(){
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
	};
	
	/**
	 * Gets document data and pos4ts into editor.
	 * 
	 * @method
	 * @public
	 * @memberof CityIndexPostImporterGdrive
	 * @param {string} url The download url for the file.
	 * @param {string} ajaxurl The ajaxurl of the blog.
	 * @return void
	 */
	this.get_document_data = function(url, ajaxurl){
		
		$.post(
			ajaxurl,
			{
				url: ajaxurl,
				action: 'ci_post_importer_load_service',
				service: 'Gdrive',
				downloadUrl: url,
				_wpnonce: this.getUrlVars()['_wpnonce']
			},
			insert_to_editor,
			'json'
		);
	};
	
	/**
	 * Inserts returned document data into editor.
	 * 
	 * @method
	 * @private
	 * @memberof CityIndexPostImporterGdrive
	 * @param {json} j The json result.
	 * @return void
	 */
	function insert_to_editor(j){
		console.log('insert_to_editor response:');
		console.log(j);
	};
};