<?php

  namespace BW\Utils;

  class Template
  {
    /**
     * Default location of views
     */
    static private $viewsDirname = 'src/views';

    /**
     * Private construct so that this class never gets instantiated (only static)
     */
    private function __construct() {}


    /**
     * Render Twig templates using the timber method, but allowing us to not specify a file extension
     *
     * @author Andrew Vivash <andrew@briteweb.com>
     * @package briteweb/base-project
     * @since 1.0.0
     * @param (string) $arg - Description
     * @return (object) Description
     */

    static public function render( $views, $context = array() )
    {
      if ( empty( $views ) )
        return;

      // check if we are requesting a php template
      if ( is_string( $views ) && strpos( $views, '.php' ) !== false )
      {
        if ( !file_exists( static::$viewsDirname . '/' . $views ) )
          return;

        global $bw_vars, $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

        if ( is_array( $wp_query->query_vars ) )
          extract( $wp_query->query_vars, EXTR_SKIP );

        // set context in global variable
        $bw_vars = $context;

        // require template
        require( static::$viewsDirname . '/' . $views );

      }
      else
      {
        // Check to see if Timber exists within the project and if a template has been specificed
        if ( !class_exists( 'Timber' ) )
          return;

        // get default context and merge with the provided context
        $timberContext = \Timber::get_context();
        $context = array_merge( $timberContext, $context );

        // Feed our template and values into the Timber render function
        \Timber::render( $views, $context );
      }

    }/* render */

    /**
     * Initialize Timber's options. Only run before the first time render() is called
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/base-project
     * @since 1.0.0
     * @return null
     */

    static public function initTimber($viewsDirname = null)
    {
      if ( !is_null( $viewsDirname ) )
        static::$viewsDirname = rtrim( $viewsDirname, '/' );

      if ( strpos( static::$viewsDirname, '/' ) === 0 )
      {
        // absolute path
        if ( !is_array( \Timber::$locations ) )
        {
          if ( empty( \Timber::$locations ) )
            \Timber::$locations = array();
          else
            \Timber::$locations = array( \Timber::$locations );
        }

        // set the custom location
        array_unshift( \Timber::$locations, static::$viewsDirname );

      }
      else
      {
        // relative path
        \Timber::$dirname[] = static::$viewsDirname;
      }

    }/* initTimber() */


    /**
     * Load twig view for a custom WP Template
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/base-project
     * @since 1.0.0
     * @param (string) $templateName - name of the template to load
     * @return null
     */

    public function renderWPCustomTemplate($templateName, $postClass = null)
    {
      // check if the post class specified is defined
      if ( !is_null( $postClass ) && ( empty( $postClass ) || !class_exists( $postClass ) ) )
        $postClass = null;

      $post = Timber::get_post( $postClass );
      $context['post'] = $post;

      if ( post_password_required( $post->ID ) )
      {
        static::render( 'single-password.twig', $context );
      }
      else
      {
        // get template name
        $template = str_replace( '.php', '', $templateName );

        // render twig
        $views = array('templates/' . $template . '.twig', 'pages/' . $post->post_name . '.twig', 'page.twig');
        static::render( $views, $context );
      }

    } /* renderWPCustomTemplate() */


  }/* class Template */
