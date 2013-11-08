<?php

namespace BW;


/**
 * Debug utility class
 *
 * @author Alessandro Biavati <ale@briteweb.com>
 * @package briteweb/utils
 * @since 1.0.0
 */

class Debug {
	
	/**
	 * @var string
	 */
	protected static $_sapi = null;


	/**
	 * Get the current value of the debug output environment.
	 * This defaults to the value of PHP_SAPI.
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (string) $sapi
	 * @return (string)
	 */
	public static function getSapi()
	{
		if (self::$_sapi === null) {
			self::$_sapi = PHP_SAPI;
		}
		return self::$_sapi;

	}/* getSapi() */

	
	/**
	 * Set the debug ouput environment.
	 * Setting a value of null causes Zend_Debug to use PHP_SAPI.
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (string) $sapi
	 * @return null
	 */

	public static function setSapi($sapi)
	{
		self::$_sapi = $sapi;

	}/* setSapi() */
	

	/**
	 * Prints inline script to output the input parameter to the browser console.
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param (mixed) $var - variable to print to console log
	 * @return null
	 */
	
	public static function log($var)
	{   
		$script = '<script>console.debug(' . json_encode($var) . ');</script>';
		if(function_exists('add_action')) {
			add_action('admin_footer',function() use($script) {
				echo $script;
			}, 0);
			add_action('wp_footer',function() use($script) {
				echo $script;
			}, 0);
		}else {
			echo $script;
		}
		
	}/* log() */
	
	

	/**
	 * wrapper for the dump function
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param  (mixed) $var - The variable to dump.
	 * @param  (string) $label - OPTIONAL Label to prepend to output.
	 * @param  (bool) $echo - OPTIONAL Echo output if true.
	 * @param  (bool) $use_print_r - OPTIONAL Use print_r instead of var_dump if true.
	 * @return string
	 */
	
	public static function pre($var, $label=null, $echo=true, $use_print_r=false)
	{
		static::dump($var, $label, $echo, $use_print_r);

	}/* pre() */
	
	/**
	 * Debug helper function.  This is a wrapper for var_dump() that adds
	 * the <pre /> tags, cleans up newlines and indents, and runs
	 * htmlentities() before output.
	 *
	 * @author Alessandro Biavati <ale@briteweb.com>
	 * @package briteweb/utils
	 * @since 1.0.0
	 * @param  (mixed) $var - The variable to dump.
	 * @param  (string) $label - OPTIONAL Label to prepend to output.
	 * @param  (bool) $echo - OPTIONAL Echo output if true.
	 * @param  (bool) $use_print_r - OPTIONAL Use print_r instead of var_dump if true.
	 * @return (string)
	 */

	public static function dump($var, $label=null, $echo=true, $use_print_r=false)
	{
		// format the label
		$label = ($label===null) ? '' : rtrim($label) . ' ';

		// var_dump the variable into a buffer and keep the output
		ob_start();
		if($use_print_r) {
			print_r($var);
		}else {
			var_dump($var);
		}
		$output = ob_get_clean();

		// neaten the newlines and indents
		$output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
		if(!$use_print_r) $output = preg_replace("/  /m", "    ", $output);
		if (self::getSapi() == 'cli') {
			$output = PHP_EOL . $label
					. PHP_EOL . $output
					. PHP_EOL;
		} else {
			if(!extension_loaded('xdebug')) {
				$output = htmlspecialchars($output, ENT_QUOTES);
			}

			$output = '<pre>'
					. $label
					. $output
					. '</pre>';
		}

		if ($echo)
			echo($output);

		return $output;

	}/* dump() */
	
	
}/* class Debug */
