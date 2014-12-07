<?php

  namespace BW\Utils;

  class LoopPattern
  {
    private $loop_blocks;
    private $pattern;
    private $page_loops;
    private $patterns_per_page;
    private $posts_in_page;

    /**
     * Class that allows to define a pattern of posts to run on a series of
     * loops.
     *
     * It calculates paging.
     *
     * @author Alessandro Biavati <@alebiavati>
     * @package LoopPattern
     * @since 1.0
     * @param (string) $loop_blocks - array( loop_name => template_block_path )
     * @param (string) $pattern - the path for the block that is loaded for the output of this loop
     * @param (string) $patterns_per_page - number of repetition of the defined pattern on each page
     * @return null
     */

    public function __construct( $loop_blocks = false, $pattern = false, $patterns_per_page = 1 )
    {
      // validation
      if( !$loop_blocks || !$pattern )
        return;

      // save variables to object properties
      $this->loop_blocks = $loop_blocks;
      $this->pattern = $pattern;
      $this->patterns_per_page = $patterns_per_page;

      // register filter for loops in patters
      add_filter( 'pre_get_posts', array( &$this, 'pre_get_posts' ), 999 );

    }/* __construct */

    /**
     * Check if query variable 'orderby' is set as 'rand'.
     *
     * If it is, it removes the 'paged' and 'offset' parameters and
     * makes sure the same posts are not printed on the same page.
     *
     * @author Alessandro Biavati <@alebiavati>
     * @package LoopPattern.php
     * @since 1.0
     * @param $query
     * @return null
     */

    public function pre_get_posts( $query )
    {
      // check if it's one of our loops
      $loop_name = $query->get('bw_loop');

      if ( empty( $loop_name ) || !isset( $this->loop_blocks[ $loop_name ] ) )
        return;

      // check for 'orderby' == 'rand'
      if ( isset( $query->query_vars['orderby'] ) && $query->query_vars['orderby'] == 'rand' ) {

        $query->set( 'offset', null );
        $query->set( 'paged', null );

        // Check if there are any posts from this loop_name already on the page
        // For this to work the total available posts needs to be bigger
        // than the posts_per_page parameter, otherwise we'll get some empty slots
        // in the pattern.
        if ( !empty( $this->posts_in_page[ $loop_name ] ) )
          $query->set( 'post__not_in', $this->posts_in_page[ $loop_name ] );

      }

    }/* pre_get_posts() */


    /**
     * setup query variables
     *
     * @author Alessandro Biavati <@alebiavati>
     * @package LoopPattern.php
     * @since 1.0
     * @param
     * @return null
     */

    public function setupPagination()
    {

      /**
       * Initialize posts_in_page arrays
       * We use this array for avoiding duplication
       */
      foreach ($this->loop_blocks as $loop_name => $block_path) {
        $this->posts_in_page[ $loop_name ] = array();
      }


      // calculate full page pattern
      $page_pattern = array();
      for ($i=0; $i < $this->patterns_per_page; $i++) {
        $page_pattern = array_merge( $page_pattern, $this->pattern);
      }

      // calculate total posts per page for each loop_name
      $posts_per_page = array();
      foreach ($page_pattern as $loop_name) {

        // initialize loop post count
        if ( !isset($posts_per_page[ $loop_name ]) )
          $posts_per_page[ $loop_name ] = 0;

        // add page count for this loop
        $posts_per_page[ $loop_name ]++;
      }

      // detect page loops
      // find how many posts per loop
      $page_loops = array();
      $prev_loop_name = '';

      // Var that holds the offset of the loops relative to the current page.
      // In other words, how many posts with the same loop name are
      // shown before current loop.
      $page_loop_offsets = array();
      foreach ($page_pattern as $loop_name) {

        // initialize page loop offset for current loop name
        if ( !isset($page_loop_offsets[ $loop_name ]) )
          $page_loop_offsets[ $loop_name ] = 0;

        // initialize new page loop, if needed
        if ($loop_name != $prev_loop_name) {

          $page_loops[] = array(
            'name' => $loop_name,
            'block_path' => $this->loop_blocks[ $loop_name ],
            'template' => array(),
            'query_vars' => array(
              'posts_per_page' => 0,
            ),
            'page_offset' => $page_loop_offsets[ $loop_name ],
          );

        }

        // add 1 to current loop count
        $page_loop_offsets[ $loop_name ]++;

        // add 1 to current loop count
        $last_page_loop_index = count( $page_loops ) - 1;
        $page_loops[ $last_page_loop_index ]['query_vars']['posts_per_page']++;

      }


      /**
       * Compute the loops offsets for custom paging
       */
      $page_loops_temp = $page_loops;
      for ($i=0; $i < count($page_loops); $i++) {

        $page_loop = $page_loops[ $i ];

        // get posts per page
        $ppp = $posts_per_page[ $page_loop['name'] ];

        // calculate loop offset
        $paged = $this->getCurrentPage();
        $offset = ($paged - 1) * $ppp + $page_loop['page_offset'];

        // set loop offset
        $page_loops[ $i ]['query_vars']['offset'] = $offset;
      }


      // save page_loops in current object
      $this->page_loops = $page_loops;

    }/* setupPagination() */


    /**
     * Get current page number
     *
     * @author Alessandro Biavati <@alebiavati>
     * @package LoopPattern.php
     * @since 1.0
     * @param
     * @return $paged
     */

    public function getCurrentPage()
    {
      $paged = 1;
      if ( get_query_var('paged') ) $paged = get_query_var('paged');
      elseif ( get_query_var('page') ) $paged = get_query_var('page');

      return $paged;
    }



    /**
     * Run loop pattern
     *
     * @author Alessandro Biavati <@alebiavati>
     * @package LoopPattern.php
     * @since 1.0
     * @param
     * @return null
     */

    public function run()
    {
      // run pagination setup
      $this->setupPagination();

      /**
       * create the loops
       */

      foreach ($this->page_loops as $loop) {
        $loopName = $loop['name'];
        $template = $loop['block_path'];
        $queryVars = $loop['query_vars'];
        $queryVars['bw_loop'] = $loopName;

        $query_params = array_merge(
          array(
            'posts_per_page' => 10,
            'post_type' => 'post'
          ),
          $queryVars
        );

        $loop_query = new \WP_Query( $query_params );

        //Start fresh (used when we're fetching not getting a template)
        $output = ( $json ) ? array() : '';

        //Set up the loop calling the relevant get_template call
        if( $loop_query->have_posts() ) : while( $loop_query->have_posts() ) : $loop_query->the_post();

          // Initialize plugin
          Template::render( $template );

          if ( !in_array( get_the_id(), $this->posts_in_page[ $loop['name'] ] ) ) {
            $this->posts_in_page[ $loop['name'] ][] = get_the_id();
          }

        endwhile; wp_reset_postdata(); endif;

      }

    }



  }/* class Loop */
