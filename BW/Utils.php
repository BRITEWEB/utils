<?php

namespace BW;

/**
 * Class with static methods for debugging
 */
class Utils {

	private static $blogsCache;

	private function __construct() {}

	
	static public function getClassPath( $class )
	{
		$loaders = \BW\Loader\StandardAutoloader::$registry;

		foreach($loaders as $loader) {
			foreach($loader['namespaces'] as $leader => $path) {
				if (0 === strpos($class, $leader)) {
					// Trim off leader (namespace or prefix)
					$trimmed_class = substr($class, strlen($leader));

					// create filename
					$filename = self::transformClassNameToFilename($trimmed_class, $path);
					if (file_exists($filename)) {
						$path = dirname($filename);
						return $path;
					}
				}
			}
		}

	}

	static public function getPath( $file_or_class )
	{
		if ( class_exists( $file_or_class ) ){
			$path = self::getClassPath($file_or_class);
		}elseif( file_exists( $file_or_class ) ) {
			$path = dirname( $file_or_class );
		}
		return $path;
	}


	static public function getResourcesUrl( $file_or_class ) {
		$path = self::getPath( $file_or_class );
		$url = str_replace( dirname(WP_CONTENT_DIR) , home_url() . '/', $path );
		return $url . '/resources';
	}


	static public function getResourcesPath( $file_or_class ) {
		$path = self::getPath( $file_or_class );
		return $path . '/resources';
	}


	static function normalizeDirectory( $directory )
	{
		$last = $directory[strlen($directory) - 1];
		if (in_array($last, array('/', '\\'))) {
			$directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;
			return $directory;
		}
		$directory .= DIRECTORY_SEPARATOR;
		return $directory;
	}

	static function transformClassNameToFilename($class, $directory)
	{
		// $class may contain a namespace portion, in  which case we need
		// to preserve any underscores in that portion.
		$matches = array();
		preg_match('/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $class, $matches);

		$class     = (isset($matches['class'])) ? $matches['class'] : '';
		$namespace = (isset($matches['namespace'])) ? $matches['namespace'] : '';

		return $directory
			 . str_replace('\\', '/', $namespace)
			 . str_replace('_', '/', $class)
			 . '.php';
	}



	static public function checked($selected, $current, $echo = true){
		if(is_array($selected)) { // checkbox
			$return = in_array($current, $selected) ? 'checked="checked"' : '';
		}else{ // radio
			$return = $current == $selected ? 'checked="checked"' : '';
		}
		if( $echo ){
			echo $return;
		}else{
			return $return;
		}
	}

	static public function selected($selected, $current, $echo = true){
		if(is_array($selected)) { // checkbox
			$return = in_array($current, $selected) ? 'selected="selected"' : '';
		}else{ // radio
			$return = $current == $selected ? 'selected="selected"' : '';
		}
		if( $echo ){
			echo $return;
		}else{
			return $return;
		}
	}


	public static function url_safe_b64_encode($data) 
	{
	  $b64 = base64_encode($data);
	  $b64 = str_replace(array('+', '/', '\r', '\n', '='),
						 array('-', '_'),
						 $b64);
	  return $b64;
	}

	public static function url_safe_b64_decode($b64) 
	{
	  $b64 = str_replace(array('-', '_'),
						 array('+', '/'),
						 $b64);
	  return base64_decode($b64);
	}


	/**
	 * Misc function used to count the number of bytes in a post body, in the world of multi-byte chars
	 * and the unpredictability of strlen/mb_strlen/sizeof, this is the only way to do that in a sane
	 * manner at the moment.
	 *
	 * This algorithm was originally developed for the
	 * Solar Framework by Paul M. Jones
	 *
	 * @link   http://solarphp.com/
	 * @link   http://svn.solarphp.com/core/trunk/Solar/Json.php
	 * @link   http://framework.zend.com/svn/framework/standard/trunk/library/Zend/Json/Decoder.php
	 * 
	 * @author Alessandro Biavati <@alebiavati>
	 * @package Utils.php
	 * @since 1.0.0
	 * @param  (string) $str
	 * @return (int) The number of bytes in a string.
	 */
	static public function getStrLen($str) 
	{
		$strlen_var = strlen($str);
		$d = $ret = 0;
		for ($count = 0; $count < $strlen_var; ++ $count) {
			$ordinal_value = ord($str{$ret});
			switch (true) {
				case (($ordinal_value >= 0x20) && ($ordinal_value <= 0x7F)):
					// characters U-00000000 - U-0000007F (same as ASCII)
					$ret ++;
					break;

				case (($ordinal_value & 0xE0) == 0xC0):
					// characters U-00000080 - U-000007FF, mask 110XXXXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$ret += 2;
					break;

				case (($ordinal_value & 0xF0) == 0xE0):
					// characters U-00000800 - U-0000FFFF, mask 1110XXXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$ret += 3;
					break;

				case (($ordinal_value & 0xF8) == 0xF0):
					// characters U-00010000 - U-001FFFFF, mask 11110XXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$ret += 4;
					break;

				case (($ordinal_value & 0xFC) == 0xF8):
					// characters U-00200000 - U-03FFFFFF, mask 111110XX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$ret += 5;
					break;

				case (($ordinal_value & 0xFE) == 0xFC):
					// characters U-04000000 - U-7FFFFFFF, mask 1111110X
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$ret += 6;
					break;

				default:
					$ret ++;
					break;
			}
		}
		
		return $ret;

	}/* getStrLen() */



	/**
	 * Normalize all keys in an array to lower-case.
	 *
	 * @author Alessandro Biavati <@alebiavati>
	 * @package Utils.php
	 * @since 1.0.0
	 * @param (array) $arr
	 * @return (array) Normalized array.
	 */

	public static function normalizeArrayKeys($arr) 
	{
		if (!is_array($arr))
			return array();

		if (empty($arr))
			return $arr;

		$normalized = array();
		foreach ($arr as $key => $val) {
			$normalized[strtolower($key)] = $val;
		}

		return $normalized;

	}/* normalizeArrayKeys() */




	static public function xmlObjToArrayRecursion($obj)
	{
		$doc_namespaces = $obj->getDocNamespaces(true);
		$doc_namespaces[NULL] = NULL;

		$children = array();
		$attributes = array();
		$name = strtolower((string)$obj->getName());

		$text = trim((string)$obj);
		if( strlen($text) <= 0 ) {
			$text = NULL;
		}

		// get info for all namespaces
		if(is_object($obj)) {
			foreach( $doc_namespaces as $ns=>$ns_url ) {
				// atributes
				$obj_attributes = $obj->attributes($ns, true);
				foreach( $obj_attributes as $attribute_name => $attribute_value ) {
					$attrib_name = trim((string)$attribute_name);
					$attrib_val = trim((string)$attribute_value);
					if (!empty($ns)) {
						$attrib_name = $ns . ':' . $attrib_name;
					}
					$attributes[$attrib_name] = $attrib_val;
				}

				// children
				$obj_children = $obj->children($ns, true);
				foreach( $obj_children as $child_name=>$child ) {
					$child_name = (string)$child_name;
					if( !empty($ns) ) {
						$child_name = $ns.':'.$child_name;
					}
					$children[$child_name][] = self::xmlObjToArrayRecursion($child);
				}
			}
		}

		return array(
			'name'=>$name,
			'text'=>$text,
			'attributes'=>$attributes,
			'children'=>$children
		);

	}

	static public function xmlObjToArray($obj)
	{
		// add namespaces to top element
		$doc_namespaces = $obj->getDocNamespaces(true);
		$namespaces = array();
		foreach( $doc_namespaces as $ns => $ns_url ) {
			$ns = trim((string)$ns);
			$ns_url = trim((string)$ns_url);
			if (empty($ns)) {
				$ns = 'xmlns';
			}else{
				$ns = 'xmlns:' . $ns;
			}
			$namespaces[$ns] = $ns_url;
		}
		$array = self::xmlObjToArrayRecursion($obj);
		$array['namespaces'] = $namespaces;
		return $array;
	}

	static public function xmlToArray( $xml )
	{
		$obj = new \SimpleXMLElement($xml);
		return self::xmlObjToArray($obj);
	}

	static public function arrayToXmlDom( $array )
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');

		// create element
		if(empty($array['text'])) {
			$node = $dom->createElement($array['name']);
		}else{
			$node = $dom->createElement($array['name'], $array['text']);
		}

		// children
		if(!empty($array['children'])){
			foreach($array['children'] as $child_name => $child_array) {
				// recrusive call.
				$child_array[0]['name'] = $child_name;
				$child_dom = self::arr_to_xml_dom($child_array[0]);
				$child_node = $dom->importNode($child_dom->documentElement, true);
				$node->appendChild($child_node);
			}
		}

		$root = $dom->appendChild($node);

		// namespaces
		if(!empty($array['namespaces'])){
			foreach($array['namespaces'] as $ns => $ns_url) {
				$root->setAttribute($ns, $ns_url);
			}
		}

		// attributes
		if(!empty($array['attributes'])){
			foreach($array['attributes'] as $attribute_name => $attribute_value) {
				$root->setAttribute($attribute_name, $attribute_value);
			}
		}

		return $dom;
	}

	static public function arrayToXml( $array )
	{
		$dom = self::arrayToXmlDom($array);
		return $dom->saveXML();
	}


	static public function arrayInsertAtIndex($original, $insert, $offset)
	{
		return array_merge( array_slice($original, 0, $offset, true), $insert, array_slice($original, $offset, null, true) );
	}



	static public function cleanTaxonomies( $taxes = null )
	{
		/**
		 * get taxonomies
		**/
		if ( empty( $taxes ) ) $taxonomies = get_taxonomies( array(), 'names' );
		else if ( is_array( $taxes ) ) $taxonomies = $taxes;
		else $taxonomies = array( $taxes );


		foreach($taxonomies as $tax){

			$args = array(
				'hide_empty' => 0,
			);

			if($tax == 'category') {
				$terms = get_categories( $args );
			}else {
				$terms = get_terms( $tax, $args );
			}

			if(!empty($terms)){
				foreach($terms as $t){
					wp_update_term_count_now( array( $t->term_id ), $tax );
				}
			}

		}

	}



	/**
	 * Transform an array to a url with query parameters
	 * starting from a given base url.
	 *
	 * @author Alessandro Biavati <@alebiavati>
	 * @package Utils.php
	 * @since 1.0
	 * @param (string) $base_url - base url to build from
	 * @param (array) $url_args - arguments to use to build url query
	 * @return (string) new url with query parameters
	 */
	static public function arrayToUrl( $base_url, $url_args = array() )
	{
		$args = array();

		foreach($url_args as $key => $arg){
			if($arg) $args[] = $key .'='. $arg;
		}
		
		$args = implode('&',$args);

		$base_url .= strpos($base_url,'?') === false ? '?' : '&';

		return $base_url . $args;

	}/* arrayToUrl() */


	static public function getCurrentUrl() 
	{
		$s = empty($_SERVER["HTTPS"]) ? ''
			: ($_SERVER["HTTPS"] == "on") ? "s"
			: "";

		$protocol = static::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;

		$port = ($_SERVER["SERVER_PORT"] == "80") ? ""
			: (":".$_SERVER["SERVER_PORT"]);

		$url = $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];

		return $url;
	}


	static public function isInternalUrl($url, $in_network = false)
	{
		$blogs = static::get_blogs();

		foreach($blogs as $blog) {
			if( strpos( $url, $blog->domain ) !== false ) {
				return true;
			}
		}
	}

	static public function getBlogs($random = false)
	{
		if( !isset(static::$blogsCache)){
			if(is_multisite()) {
				global $wpdb;
				switch_to_blog(1);
				$tbl_blogs = $wpdb->prefix ."blogs";
				static::$blogsCache = $wpdb->get_results( "SELECT blog_id, domain FROM $tbl_blogs" );
				restore_current_blog();
			}else{
				$urlparts = parse_url(home_url());
				$domain = str_replace($urlparts['scheme'] . '://', '', home_url());
				$blog = (object) array(
					'blog_id' => 1,
					'domain' => $domain,
				);

				static::$blogsCache = array(static::$blog);
			}
		}

		return static::$blogsCache;
	}

	static public function getDirectoryList( $directory )
	{
		// create an array to hold directory list
		$results = array();

		// create a handler for the directory
		try {
			$handler = opendir($directory);
			if (!$handler) return false;
		} catch (Exception $e) {
			return false;
		}

		// open directory and walk through the filenames
		while ($file = readdir($handler)) {
		  // if file isn't this directory or its parent, add it to the results
		  if ($file != "." && $file != "..") {
			$results[] = $file;
		  }
		}

		// tidy up: close the handler
		closedir($handler);

		// done!
		return $results;
	}


}
