<?php

  namespace BW\Utils;

  class Url
  {

    /**
     * Private construct so that this class never gets instantiated (only static)
     */
    private function __construct() {}


    /**
     * Find the URL of the resources folder next to a particular class
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (mixed) $class - Filename or class to base the search on.
     * @return (string) Url of resources folder of specified file, package or class
     */

    static public function getClassUrl( $class )
    {
      // initialize default result. If the return is false, it means that the
      // class in question is not inside the WP plugins folder
      $classUrl = false;

      // check if the argument is a path or a class
      if ( strpos( $class, '/' ) !== false )
        $classPath = dirname( $class );
      else
        $classPath = static::getClassPath( $class );


      // check if the class is inside the plugins folder
      if ( strpos( $classPath, WP_PLUGIN_DIR ) === 0 )
      {
        $pluginClassPath = str_replace( WP_PLUGIN_DIR, '', $classPath );
        $classUrl = plugins_url( $pluginClassPath );
      }
      elseif ( strpos( $classPath, $themeDir = get_stylesheet_directory() ) === 0 )
      {
        $themeUrl = get_stylesheet_directory_uri();
        $classUrl = str_replace( $themeDir, $themeUrl, $classPath );
      }
      else
      {
        // find Root path (without WP)
        $homePath = Utils\String::findCommonSubString( array( ABSPATH, WP_CONTENT_DIR ) );

        // check if the class is inside the plugins folder
        if( strpos( $classPath, $homePath ) === 0 )
        {
          $themeUrl = get_stylesheet_directory_uri();
          $classUrl = str_replace( rtrim($homePath, '/'), rtrim(home_url(), '/'), $classPath );
        }

      }

      // retrun URL
      return $classUrl;

    }/* getClassUrl() */


    /**
     * Find the path of the resources folder next to a particular class
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (mixed) $class - Filename or class to base the search on.
     * @return (string) Path of resources folder of specified file, package or class
     */

    static public function getClassPath( $class )
    {

      // get class filename
      $filename = static::getFilename( $class );

      // add the resources folder
      $resourcesPath = dirname( $filename );

      return $resourcesPath;

    }/* getClassPath() */

    /**
     * Find the path of the resources folder next to a particular class
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (mixed) $class - Filename or class to base the search on.
     * @return (string) Path of resources folder of specified file, package or class
     */

    static public function getFilename( $class )
    {
      if( class_exists( $class ) )
      {
        $reflector = new \ReflectionClass( $class );
        $filename = $reflector->getFileName();
      }
      else
      {
        $filename = $class;
      }

      return $filename;

    }/* getFilename() */


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
    static public function arrayToUrl( $baseUrl, $urlArgs = array() )
    {
      $args = array();

      if ( !empty( $urlArgs ) ) {
        foreach($urlArgs as $key => $arg){
          if($arg) $args[] = $key .'='. $arg;
        }
      }

      $args = implode('&',$args);

      $baseUrl .= strpos($baseUrl,'?') === false ? '?' : '&';

      return $baseUrl . $args;

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

      $sp = strtolower($_SERVER["SERVER_PROTOCOL"])
      $protocol = substr($sp, 0, strpos($sp, '/')) . $s;

      $port = ($_SERVER["SERVER_PORT"] == "80") ? ""
        : (":".$_SERVER["SERVER_PORT"]);

      $url = $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];

      return $url;

    }/* getCurrentUrl() */


  }/* class Url */
