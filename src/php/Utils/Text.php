<?php

  namespace BW\Utils;

  class Text
  {

    static public function bwGetExcerpt( $post = false, $length = 120, $more = '...' )
    {
      if ($post->post_excerpt) return $post->post_excerpt . $more;

      return static::truncate( $post->post_content, $length, ' ', $more );

    }

    static public function truncate( $string, $limit, $break=" ", $pad="..." ) {

      // return with no change if string is shorter than $limit
      if( strlen( $string ) <= $limit ) return $string;

      // is $break present between $limit and the end of the string?
      if(false !== ( $breakpoint = strpos( $string, $break, $limit ) ) ) {
        if( $breakpoint < strlen( $string ) - 1 ) $string = substr( $string, 0, $breakpoint ) . $pad;
      }

      return $string;

    }

    static public function bwGetExcerpt_($text, $excerpt, $more_link = false, $excerpt_length = 55){
      if ($excerpt) return $excerpt;

      $text = strip_shortcodes( $text );

      $text = apply_filters('the_content', $text);
      $text = str_replace(']]>', ']]&gt;', $text);
      $text = strip_tags($text);
      if(empty($excerpt_length)) $excerpt_length = 55;
      $excerpt_length = apply_filters('excerpt_length', $excerpt_length);
      if ($more_link) {
        $excerpt_more = apply_filters('excerpt_more', ' <a class="moretag" href="'. get_permalink($post->ID) . '">'.__('Read More','plus-acumen').'</a>','plus-acumen');
      }else{
        $excerpt_more = apply_filters('excerpt_more', ' ' . '');
      }
      $words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
      if ( count($words) > $excerpt_length ) {
          array_pop($words);
          $text = implode(' ', $words);
          $text = $text . $excerpt_more;
      } else {
          $text = implode(' ', $words);
      }

      if(!isset($raw_excerpt)) $raw_excerpt = '';

      return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
    }


    static public function trimAndInt( &$item ) {
      $item = (int) trim( $item );
    }


    static public function formatPhone( $number ) {
      $number = preg_replace( "/[^0-9]/", "", $number );
      $number = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $number);
      return $number;
    }

  }/* class Copy */
