<?php
namespace CityIndex\WP\PostImporter\Modules;

//make sure api connection manager is loaded
@require_once (WP_PLUGIN_DIR . "/api-connection-manager/class-api-connection-manager.php");

/**
 * Description of Gdrive
 *
 * @author daithi
 */
class Gdrive {

	public $plugin_url;
	private $api;

	function __construct() {
		$this->api = new \API_Connection_Manager();
	}
	
	public function get_file($id){
		
		//get file data
		$file = $this->api->request($_REQUEST['service'], array(
			'uri' => "https://www.googleapis.com/drive/v2/files/{$id}",
			'headers' => array(
				'Authorization' => 'Bearer <!--[--access-token--]-->'
			),
			'method' => 'GET'
		));
		$res = json_decode($file['body']);
		$export_links = (array) $res->exportLinks;
		
		//look for export links
		if($export_links['text/html'])
			$data = $this->api->request($_REQUEST['service'], array(
				'uri' => $export_links['text/html'],
				'headers' => array(
					'Authorization' => 'Bearer <!--[--access-token--]-->'
				),
				'method' => 'GET'
			));
		
		return $data['body'];
	}

	public function get_files() {

		if (@$_GET['type'] != 'file')
			$res = $this->api->request($_REQUEST['service'], array(
				'uri' => 'https://www.googleapis.com/drive/v2/files/',
				'method' => 'GET',
				'body' => array(
					'access_token' => true
					)));

		$contents = json_decode($res['body']);
		$gdrive_dirs = array();
		$gdrive_files = array();
		foreach ($contents->items as $item) {

			//work out title
			if (@$item->originalFilename)
				$title = $item->originalFilename;
			else
				$title = $item->title;

			//dir or file
			if ($item->mimeType == "application/vnd.google-apps.folder")
				$gdrive_dirs[$item->id] = array(
					'title' => $title,
					'type' => "dir",
					'parents' => $item->parents,
					'children' => array()/* ,
						  'id' => $item->id,
						  'parents' => $item->parents,
						  'mime' => $item->mimeType */
				);
			else
				$gdrive_files[$item->id] = array(
					'title' => $title,
					'parents' => $item->parents,
					'type' => "file" ,
						  'id' => $item->id,
						  'mime' => $item->mimeType
				);
		}
		asort($gdrive_files);

		//put files in directories
		foreach ($gdrive_files as $id => $file) {
			foreach ($file['parents'] as $parent)
				$gdrive_dirs[$parent->id]['children'][] = $file;
			unset($gdrive_files[$id]);
			asort($gdrive_dirs[$parent->id]['children']);
		}

		//organise directories
		foreach ($gdrive_dirs as $id => $dir) {
			if (is_array(@$dir['parents']))
				foreach ($dir['parents'] as $parent)
					if ($gdrive_dirs[$parent->id]) {
						$gdrive_dirs[$parent->id]['children'] = array_merge(array($dir), $gdrive_dirs[$parent->id]['children']);
						unset($gdrive_dirs[$id]);
					}
		}
		return $gdrive_dirs;
	}

	public function get_html($contents) {
		$html = '';
		foreach ($contents['children'] as $content) {

			if (@$content['type'] == 'dir') {
				$html .= "<li rel='folder'>";
				$html .= "<a href=\"#\">{$content['title']}</a><ul>";
				$html .= $this->get_html($content);
				$html .= "</ul>\n";
				$html .= "</li>\n";
			} else {
				$params = $_GET;
				$params['type'] = 'file';
				$params['id'] = $content['id'];
				$url = "/wp-admin/admin-ajax.php?".http_build_query($params);
				$html .= "<li rel='file'>
					<a href=\"{$url}\">{$content['title']}</a>
				</li>
				";
			}
		}

		return $html;
	}

	public function get_tree($contents) {
		//load js tree
		wp_enqueue_script('post-file-importer-jstree', $this->plugin_url . "/application/includes/jstree/jquery.jstree.js", array('jquery'));
		//wp_enqueue_style($this->config->plugin_url."/application/includes/jstree/jquery.jstree.js", array('jquery'));
		//get html
		$html = "<div id=\"gdrive-list\"><ul>\n";
		foreach ($contents as $id => $content)
			if (@$content['type'] == 'dir') { //dir user has permissions to from another account
				$html .= "<li rel='folder'><a href=\"{$id}\">{$content['title']}</a><ul>";
				$html .= $this->get_html($content);
				$html .= "</ul></li>\n";
			} else {	  //root of users account
				$html .= "<li rel='folder'><a href=\"root\">root</a><ul>";
				$html .= $this->get_html($content);
				$html .= "</ul></li>\n";
			}
		$html .= "</ul></div>\n";

		//build jstree
		$html .= "<script type=\"text/javascript\">
							jQuery('#gdrive-list').jstree({
								'plugins' : [ 'themes', 'html_data', 'types' ],
								'types' : {
									'valid_children' : [ 'folder', 'file' ],
									'types' : {
										'folder' : {
											'icon' : {
												'image' : '" . $this->plugin_url . "/public_html/images/dir.png'
											},
											'valid_children' : [ 'folder', 'file' ]
										},
										'file' : {
											'icon' : {
												'image' : '" . $this->plugin_url . "/public_html/images/file.png'
											},
											'valid_children' : 'none'
										}
									}
								}
							});
						</script>";
		
		return $html;
	}

}