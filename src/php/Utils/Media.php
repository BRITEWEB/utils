<?php

  namespace BW\Utils;

  class Media
  {

    /**
     * Private construct so that this class never gets instantiated (only static)
     */
    private function __construct() {}


    static public function getAcfImageSrc( $post_id = false, $field, $size = 'thumbnail') {

      if ( ! function_exists( 'get_field' ) ) return false;

      if ( ! $post_id ) {
        global $post;
        $post_id = $post->ID;
      }

      $img_id = get_field( $field, $post_id );
      $image = wp_get_attachment_image_src( $img_id, $size );

      return $image[0];

    }

    static public function bwGetPostThumbnailSrc( $post_id, $size = 'post_thumb') {
      $src = '';
      $attachment_id = get_post_thumbnail_id ( $post->ID );
      $image_full = wp_get_attachment_image_src($attachment_id, 'full');
      if ($image_full[1] != 0 && $image_full[2] != 0) {
        if ($image_full[2] > $image_full[1]) { // is vertical image
          $size .= '_nocrop';
        }
      }
      $image = wp_get_attachment_image_src($attachment_id, $size);
      if($image){
        if ($image[1] != 0 && $image[2] != 0) {
          $src = $image[0];
        }
      }

      return empty($src) ? false : $src ;
    }

    /*
     * Helper function for Wordpress oEmbed to convert a video URL to embed code
     * @param  string $url URL of video to embed (from Youtube, Vimeo, etc - supports most video services)
     * @param  int $width / $height Width and height of embedded video. Will fit to aspect ratio of video source within $width and $height (to force to width specify height same or more than width)
     * @param  int $pid If outside of the loop you MUST specify the post ID or else this will just return the URL.
    */
    static public function embedVideoUrl( $url, $width = 0, $height = 0, $post_id = 0 ) {
      $wpembed = new \WP_Embed();
      if ( !empty( $post_id ) ) $wpembed->post_ID = $post_id;

      return $wpembed->shortcode( array( 'width' => $width, 'height' => $height ), $url );
    }

    static public function getVideoThumbnailSrc( $video_url, $custom_size = '' )
    {

      // check if it's youtube or vimeo and find video id
      // Is this a youtube link?
      $isYouTube = ( preg_match( '/youtu\.be/i', $video_url ) || preg_match( '/youtube\.com\/watch/i', $video_url ) ) ? true : false;

      // Is this a vimeo link?
      $isVimeo = ( preg_match( '/vimeo\.com/i', $video_url ) ) ? true : false;

      if( $isYouTube )
        $video_id = static::getVideoIDFromYoutubeURL( $video_url );

      if( $isVimeo )
        $video_id = static::getVideoIDFromVimeoURL( $video_url );

      $protocol = is_ssl() ? 'https' : 'http';
      // \BW\Debug::log( array( $video_url, $isYouTube, $isVimeo, $protocol ) );
      if( $isYouTube )
      {

        $img_src = $protocol . '://img.youtube.com/vi/' . $video_id . '/0.jpg';

        if ( $custom_size != '' )
          $img_src = $protocol . '://img.youtube.com/vi/' . $video_id . '/' . $custom_size . '.jpg';

      }
      elseif( $isVimeo )
      {

        $request_url = $protocol . '://vimeo.com/api/v2/video/' . $video_id . '.php';

        $contents = @file_get_contents("$request_url");

        $array = @unserialize(trim("$contents"));

        if( is_array( $array ) )
          $img_src = $array[0]['thumbnail_large'];
        else
          $img_src = $array['thumbnail_large'];

      }

      return $img_src;

    }/* getVideoThumbnailSrc() */



    /*
     * Retrieve attached image(s) from post
     * @uses   get_posts()
     * @param  int $id Post ID
     * @param  bool $single Set to true to return only one image, otherwise returns an array of all images
     * @param  bool $ignore_featured Set to true to remove featured image from return array
    */
    static public function getPostImages( $id, $single = false, $ignore_featured = true ) {
      $args = array(
        'orderby'         => 'menu_order',
        'order'          => 'ASC',
        'post_type'      => 'attachment',
        'post_parent'    => $id,
        'post_mime_type' => 'image',
        'post_status'    => null,
        'numberposts'    => -1
      );

      if ( $ignore_featured ) $args['exclude'] = get_post_thumbnail_id( $id );

      $posts = get_posts( $args );
      if ( $single ) return $posts[0];
      else return $posts;
    }



    static public function getDocTypeClass( $mime ) {

      $mime = trim( $mime );

      switch ( $mime ) {
        case 'application/pdf' :
          return 'pdf';
          break;
        case 'application/msword' :
          return 'doc';
          break;
        case 'application/vnd.ms-powerpoint' :
          return 'ppt';
          break;
      }

      if ( strpos( $mime, 'zip' !== false ) ) return 'zip';
      if ( strpos( $mime, 'image' !== false ) ) return 'img';

    }



    /**
     * Helper method to convert a URL into a video ID for youtube.com, youtu'be and vimeo.com
     *
     * @author Richard Tape <@richardtape>
     * @package Media.php
     * @since 1.0
     * @param (string) $url the video url
     * @return (string) $id the ID of the video
     */
    static public function getVideoIDFromURL( $url )
    {

      // Is this a youtube link?
      $isYouTube = ( preg_match( '/youtu\.be/i', $url ) || preg_match( '/youtube\.com\/watch/i', $url ) ) ? true : false;

      // Is this a vimeo link?
      $isVimeo = ( preg_match( '/vimeo\.com/i', $url ) ) ? true : false;

      if( $isYouTube )
        return static::getVideoIDFromYoutubeURL( $url );

      if( $isVimeo )
        return static::getVideoIDFromVimeoURL( $url );

    }/* getVideoIDFromURL() */


    /**
     * Helper method to get a youTube ID from a url
     *
     * @author Richard Tape <@richardtape>
     * @package Media.php
     * @since 1.0
     * @param (string) $url
     * @return (string) $id - the youTube ID
     */

    static public function getVideoIDFromYoutubeURL( $url )
    {

      $pattern = '/^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/';

      preg_match( $pattern, $url, $matches );

      if( count( $matches ) && strlen( $matches[7] ) == 11 )
        return $matches[7];

    }/* getVideoIDFromYoutubeURL() */


    /**
     * Helper method to get the video ID for a Vimeo url
     *
     * @author Richard Tape <@richardtape>
     * @package Media.php
     * @since 1.0
     * @param (string) $url
     * @return (string) $id - the vimeo ID
     * @return null
     */

    static public function getVideoIDFromVimeoURL( $url )
    {

      $pattern = '/\/\/(www\.)?vimeo.com\/(\d+)($|\/)/';

      preg_match( $pattern, $url, $matches );

      if( count( $matches ) )
        return $matches[2];

    }/* getVideoIDFromVimeoURL() */


    /**
     * Detemine if a video url is from youtube or vimeo
     *
     * @author Andrew Vivash <@andrewvivash>
     * @package Media.php
     * @since 1.0
     * @param $url
     * @return video service
     */

    static public function getVideoService( $url )
    {

      // Is this a youtube link?
      $isYouTube = ( preg_match( '/youtu\.be/i', $url ) || preg_match( '/youtube\.com\/watch/i', $url ) ) ? true : false;

      // Is this a vimeo link?
      $isVimeo = ( preg_match( '/vimeo\.com/i', $url ) ) ? true : false;

      if( $isYouTube )
        return 'youtube';

      if( $isVimeo )
        return 'vimeo';

    }/* getVideoService() */


  }/* class Media */
