<?php

namespace BW;

/**
 * General purpose utility class
 * 
 * @author Alessandro Biavati <ale@briteweb.com>
 * @package Debug.php
 * @since 1.0.0
 */

class Utils {

	/**
	 * @var cache that holds an array of all the blog objects of the current network.
	 */
	private static $blogsCache;


	/**
	 * Private construct so that this class never gets instantiated (only static)
	 */
	private function __construct() {}


	/**
	 * Encoding/Decoding utility.
	 * URL safe B64 encode
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (string) $data - data to encode
	 * @return (string) encoded data
	 */
	
	public static function url_safe_b64_encode($data) 
	{
		$b64 = base64_encode( $data );
		$b64 = str_replace( array( '+', '/', '\r', '\n', '=' ), array( '-', '_' ), $b64 );
		return $b64;

	}/* url_safe_b64_encode() */

	/**
	 * Encoding/Decoding utility.
	 * URL safe B64 decode
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (string) $b64 - endoded data to decode
	 * @return (string) decoded data
	 */
	
	public static function url_safe_b64_decode($b64) 
	{
		$b64 = str_replace( array( '-', '_' ), array( '+', '/' ), $b64 );
		return base64_decode( $b64 );

	}/* url_safe_b64_decode() */


	/**
	 * Misc function used to count the number of bytes in a post body, in the world of multi-byte chars
	 * and the unpredictability of strlen/mb_strlen/sizeof, this is the only way to do that in a sane
	 * manner at the moment.
	 *
	 * This algorithm was originally developed for the
	 * Solar Framework by Paul M. Jones
	 *
	 * @link http://solarphp.com/
	 * @link http://svn.solarphp.com/core/trunk/Solar/Json.php
	 * @link http://framework.zend.com/svn/framework/standard/trunk/library/Zend/Json/Decoder.php
	 * 
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (string) $str
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
	 * Converts an XML string to a formatted associative array
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (string) $xml - XML data to convert into array
	 * @return (array)
	 */
	
	static public function xmlToArray( $xml )
	{
		$obj = new \SimpleXMLElement($xml);

		return self::xmlObjToArray($obj);

	}/* xmlToArray */


	/**
	 * Converts a properly formatted array into an XML string
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (array) $array - associative array representing XML data, to be converted into XML data
	 * @return (string)
	 */
	
	static public function arrayToXml( $array )
	{
		$dom = self::arrayToXmlDom($array);
		
		return $dom->saveXML();

	}/* arrayToXml */


	/**
	 * Converts an XML dom object into a formatted associative array
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (SimpleXMLElement) $obj - XML object to convert into array
	 * @return (array)
	 */
	
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

	}/* xmlObjToArray() */



	/**
	 * Converts a properly formatted array into an XML dom object (SimpleXMLElement)
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (array) $array - formatted array represanting an XML object
	 * @return (SimpleXMLElement)
	 */
	
	static public function arrayToXmlDom( $array )
	{
		$dom = new \DOMDocument('1.0.0', 'UTF-8');

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

	}/* arrayToXmlDom() */



	/**
	 * Recursive function that converts an XML object into an array
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (SimpleXMLElement) $obj - XML object to convert
	 * @return (array)
	 */
	
	static private function xmlObjToArrayRecursion($obj)
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

	}/* xmlObjToArrayRecursion() */




	/**
	 * Array utility. Normalizes all keys in an array to lower-case.
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
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


	/**
	 * Array utility
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (array) $original
	 * @param (array) $insert - array to insert at $offset
	 * @param (int) $offset
	 * @return null
	 */
	static public function arrayInsertAtIndex($original, $insert, $offset)
	{
		return array_merge( array_slice($original, 0, $offset, true), $insert, array_slice($original, $offset, null, true) );

	}/* arrayInsertAtIndex() */



	/**
	 * Re-calculate taxonomy terms counts.
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (array) $taxes - taxonomies to clean (all if left empty)
	 * @return null
	 */
	static public function cleanTaxonomies( $taxes = null )
	{
		
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

	}/* cleanTaxonomies() */



	/**
	 * Transform an array to a url with query parameters
	 * starting from a given base url.
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
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


	/**
	 * Get current URL based on $_SERVER variable
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @return (string) Requested URL
	 */
	
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

	}/* getCurrentUrl() */


	/**
	 * Utility function to get an array of all the blogs in a multisite installation.
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @return (array) Array of all blogs belonging to current multisite network
	 */
	
	static public function getBlogs()
	{
		
		// check if the cached array is set
		if( !isset( static::$blogsCache ) ){

			if( is_multisite() ) 
			{
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

	}/* getBlogs() */


	/**
	 * Get a list of files and directories contained in a directory
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (string) $directory - directory to list
	 * @return (array) list of files and directories contained in $directory
	 */
	
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

	}/* getDirectoryList() */


}/* class Utils */
