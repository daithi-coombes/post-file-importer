<?php
namespace CityIndex\WP\PostImporter\Modules;

//make sure api connection manager is loaded
@require_once (WP_PLUGIN_DIR . "/api-connection-manager/class-api-connection-manager.php");

/**
 * Description of Github
 *
 * @author daithi
 */
class Github {
	
	public $plugin_url;
	private $api;
	
	/**
	 * Construct. 
	 */
	public function __construct(){
		$this->api = new \API_Connection_Manager();
	}

	/**
	 * Parses github requests and returns formated results.
	 * 
	 * @return \stdClass 
	 */
	public function get_content() {
		
		$contents = new \stdClass();
		
		//default to showing repos
		if (!@$_REQUEST['type']) {

			$response = $this->api->request($_REQUEST['service'], array(
				'uri' => "https://api.github.com/user/repos",
				'method' => 'GET',
				'body' => array(
					'type' => 'all',
					'sort' => 'full_name',
					'direction' => 'asc',
					'access_token' => true
				)
					));
			$repos = json_decode($response['body']);
			foreach ($repos as $repo) {
				$contents->dirs[] = array(
					'title' => $repo->full_name,
					'id' => $repo->full_name,
					'type' => 'repo'
				);
			}
		}

		//list contents
		else {
			(@$_REQUEST['path']) ?
							$path = $_REQUEST['path'] :
							$path = "";

			//if getting contents
			$uri = "https://api.github.com/repos/{$_REQUEST['id']}/contents/{$path}";
			$response = $this->api->request($_REQUEST['service'], array(
				'method' => 'get',
				'uri' => $uri
					));
			$results = json_decode($response['body']);
			
			//if file
			if (@$_REQUEST['type'] == 'file') {

				//get data
				$file = $results;

				if ('base64' == $file->encoding){
					$contents = base64_decode($file->content);
					$contents = str_replace("\n", "<br/>", $contents);
				}
			}


			else {
				foreach ($results as $item) {
					if ($item->type == 'dir')
						$contents->dirs[] = array(
							'title' => @$item->name,
							'id' => @$_REQUEST['id'],
							'type' => @$item->type,
							'path' => @$item->path
						);
					else
						$contents->files[] = array(
							'title' => @$item->name,
							'id' => @$_REQUEST['id'],
							'type' => @$item->type,
							'path' => @$item->path
						);
				}
			}

		}
		return $contents;
	}

	/**
	 * Builds the html.
	 * 
	 * Builds a breadcrumb including the contents list.
	 * 
	 * @param \stdClass $contents
	 * @return string 
	 */
	public function get_html($contents){
		
		$uri_current = 'http';
		if(@$_SERVER["HTTPS"] == "on")
			$uri_current .= "s";
		$uri_current .= "://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		/**
		 * Breadcrumb
		 */
		$breadcrumbs = array();
		$params = $_GET;
		if(@$_REQUEST['breadcrumbs'])
			$breadcrumbs = unserialize(stripslashes($_REQUEST['breadcrumbs']));

		//root link
		$breadcrumb = "/<a href=\"/wp-admin/admin-ajax.php?action={$_GET['action']}&service={$_GET['service']}&_wpnonce={$_GET['_wpnonce']}\">
			GitHub</a>\n";

		//get params for this folder
		if(@$_GET['id']){
			unset($params['breadcrumbs']);
			unset($params['action']);
			unset($params['service']);
			unset($params['_wpnonce']);
			$breadcrumbs[] = $params;
		}

		//build up list of links
		$foo = $breadcrumbs;
		$links = array();
		while(count($foo)){
			$query = array_pop($foo);
			$query['breadcrumbs'] = serialize(array_reverse($foo));
			$links[] = array(
				'query' => $query,
				'title' => $query['title']
			);
		}
		$links = array_reverse($links);

		//build breadcrumb
		foreach($links as $link){
			$link['query']['action'] = $_REQUEST['action'];
			$link['query']['service'] = $_REQUEST['service'];
			$link['query']['_wpnonce'] = $_REQUEST['_wpnonce'];
			$breadcrumb .= "/<a href=\"/wp-admin/admin-ajax.php?" . http_build_query($link['query']) . "\">{$link['title']}</a>\n";
		}
		//end Breadcrumb

		$html = "<h4>{$breadcrumb}</h4>
			<ul class=\"content-list\">\n";
		if(is_array(@$contents->dirs))
			foreach($contents->dirs as $file){
				if(count($breadcrumbs)) $file['breadcrumbs'] = serialize($breadcrumbs);
				$uri = url_query_append($uri_current, $file);
				$html .= "<li>
					<img src=\"{$this->plugin_url}/public_html/images/dir.png\" alt=\"dir\"/>
					<a href=\"$uri\">{$file['title']}</a>
					</li>";
			}
		$html .= "</ul>\n";

		$html .= "<ul class=\"content-list\">\n";
		if(is_array(@$contents->files))
			foreach($contents->files as $file){
				if(count($breadcrumbs)) $file['breadcrumbs'] = serialize($breadcrumbs);
				$uri = url_query_append($uri_current, $file);
				$html .= "<li>
					<img src=\"{$this->plugin_url}/public_html/images/file.png\" alt=\"file\"/>
					<a href=\"$uri\">{$file['title']}</a>
					</li>";
			}
		$html .= "</ul>\n";
		
		return $html;
	}
}

?>
