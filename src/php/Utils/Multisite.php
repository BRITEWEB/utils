<?php

  namespace BW\Utils;

  class Multisite
  {

    /**
     * @var cache that holds an array of all the blog objects of the current network.
     */
    private static $blogsCache;

    /**
     * Private construct so that this class never gets instantiated (only static)
     */
    private function __construct() {}

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
     * get main site in a multisite installation
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @return (int) Main blog ID
     */

    static public function getMainBlogId()
    {
      if( BLOG_ID_CURRENT_SITE )
        return BLOG_ID_CURRENT_SITE;
      else
        return 1;

    }/* getMainBlogId() */


    /**
     * Normalized search for a specific blog id.
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (int|string) $blog - Blog to find. It can be any of the following identifiers:
     *                             - 'network' -> returns 'network'
     *                             - 'site' -> returns the current site id
     *                             - 'main' -> returns the main site id
     *                             - (int) -> returns the input blog id
     *                             - 'example.org' -> the input is considered a domain,
     *                                        optional path can be specified
     *                             - null -> returns $_POST['blogId'] if set.
     *                                   Otherwise it returns the current site id
     * @return (int|string) either a blog id or "network"
     */

    static public function getBlogId( $blogId = null, $path = null )
    {
      // verify that we are in multisite. If not multisite, we return 1.
      if ( !is_multisite() )
        return 1;

      if ( is_numeric( $blogId ) || $blogId === 'network' ) {

        // do nothing

      }elseif( $blogId == 'site' ){

        $blogId = get_current_blog_id();

      }elseif ( $blogId === 'main' ) {

        $blogId = static::getMainBlogId();

      }elseif ( is_string( $blogId ) ) {

        $blogId = get_blog_id_from_url( $blogId, $path );

      }elseif ( is_null( $blogId ) && isset( $_POST['blogId'] ) ) {

        $blogId = intval( $_POST['blogId'] );

      }else{

        $blogId = get_current_blog_id();

      }

      return $blogId;

    }/* verifyBlogId() */


  }/* class Multisite */

