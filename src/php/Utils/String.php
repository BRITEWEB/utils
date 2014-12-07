<?php

  namespace BW\Utils;

  class String
  {

    /**
     * Private construct so that this class never gets instantiated (only static)
     */
    private function __construct() {}


    /**
     * Find the largest common prefix in an array of strings
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/package
     * @since 1.0.0
     * @param (array) $words - Strings to search in
     * @return (string) Output string found
     */

    static public function findCommonSubString($words)
    {
      // $words = array_map('strtolower', array_map('trim', $words));
      $sort_by_strlen = create_function('$a, $b', 'if (strlen($a) == strlen($b)) { return strcmp($a, $b); } return (strlen($a) < strlen($b)) ? -1 : 1;');
      usort($words, $sort_by_strlen);
      // We have to assume that each string has something in common with the first
      // string (post sort), we just need to figure out what the longest common
      // string is. If any string DOES NOT have something in common with the first
      // string, return false.
      $longest_common_substring = array();
      $shortest_string = str_split(array_shift($words));
      while (sizeof($shortest_string)) {
        array_unshift($longest_common_substring, '');
        foreach ($shortest_string as $ci => $char) {
          foreach ($words as $wi => $word) {
            if (!strstr($word, $longest_common_substring[0] . $char)) {
              // No match
              break 2;
            } // if
          } // foreach
          // we found the current char in each word, so add it to the first longest_common_substring element,
          // then start checking again using the next char as well
          $longest_common_substring[0].= $char;
        } // foreach
        // We've finished looping through the entire shortest_string.
        // Remove the first char and start all over. Do this until there are no more
        // chars to search on.
        array_shift($shortest_string);
      }
      // If we made it here then we've run through everything
      usort($longest_common_substring, $sort_by_strlen);
      return array_pop($longest_common_substring);

    } /* findCommonSubString() */


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


    /**
     * Shorten a given path, only if it's longer than the given $maxChars number.
     * It also prepends and appends dots if the string was shortened.
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/package
     * @since 1.0.0
     * @param (string) $path - Path to shorten
     * @param (int) $maxChars - Max number of characters that the string path be
     * @param (string) $pad - string to use as prefix and/or postfix if the path
     *                           is shortened
     * @return (string) Shortened path
     */

    static public function shortenPath($path, $maxChars = 20, $pad = '..')
    {
      if ( strlen( $path ) <= $maxChars )
        return $path;

      $urlArgs = '';
      if ( strpos( $path, '?' ) !== false )
      {
        $shortenedPathParts = explode( '?', $path );
        $path = $shortenedPathParts[0];
        $urlArgs = '?' . $shortenedPathParts[1];
      }

      // check if the path has a trailing slash
      $trailingSlash = '';
      if( substr( $path, -1 ) == '/' )
      {
        $path = substr( $path, 0, -1 );
        $trailingSlash = '/';
      }

      // get the path basename
      $basename = basename( $path );

      // build new shortened path
      $path = '/' . $basename . $trailingSlash . $urlArgs;

      // check again if string is still too long
      if ( strlen( $path ) > $maxChars )
      {
        // keep only first $maxChars charachters and insert dots
        $path = substr( $path, 0, $maxChars );
        $path .= $pad;
      }

      // add dots at the beginning
      $path = $pad . $path;

      return $path;

    } /* shortenPath() */


  } /* class String */
