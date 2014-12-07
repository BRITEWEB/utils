<?php

  namespace BW\Utils;

  class Social
  {

    /**
     * Private construct so that this class never gets instantiated (only static)
     */
    private function __construct() {}


    static public function buildShareUrl( $url = null, $campaign = null, $source = null, $location = null )
    {
      if ( ! $url ) $url = home_url();
      if(!$campaign || !$source) return $url;

      if ( ! $location ) $location = 'site';

      $args = array(
        'utm_campaign' => $campaign,
        'utm_source' => $source,
        'utm_medium' => $location,
      );


      $url = Data::arrayToUrl($url, $args);

      if ( $source && ( $source == 'facebook' || $source == 'email' ) ) {
        $shorturl = $url;
      } else
      {
        // check if bw_short_urls table exists
        $shorturl = \BW\App\Bitly\Utils::shorten_url($url);
      }

      // $shorturl = $url;

      return $shorturl;

    }

    static public function getSocialLinks($social_links, $args)
    {
      if(is_404()) return;

      $default_args = array(
        'classes' => '',
        'wrapper_classes' => '',
        'url' => '',
        'production_url' => '',
        'img' => '',
        'title' => '',
        'description' => '',
        'twitter_share_custom_message' => '',
        'wrapper' => 'ul',
        'link_wrapper' => 'li',

        // GA campaign
        'campaign' => 'website',
        'location' => null,

        'attributes' => array(),
        'before_link' => '',

        'retweet' => '',
        'reply' => '',

        'email_subject' => '',
        'email_message' => '',

        'twitter_hashtags' => 'Share',

        'facebook_text' => 'Share',
        'twitter_text' => 'Tweet',
        'email_text' => 'Email',
      );

      $args = array_merge($default_args, $args);
      extract($args);

      $html = '';
      foreach($social_links as $social_link_name) {

        $share_url = false;
        $social_link_html = '';
        $social_link_classes = '';
        // $description = addslashes( $description );
        $description = str_replace( '"', "'", $description );
        $twitter_share_custom_message = str_replace( '"', "'", $twitter_share_custom_message );
        $twitter_share_custom_message = strip_tags( $twitter_share_custom_message );
        switch ($social_link_name) {

          case 'tweet':
            $social_link_classes = 'twitter';

            if(!empty($twitter_share_custom_message)) {
              $text = $twitter_share_custom_message;
            }else {
              $text = $title;
            }

            if(!$url) $url = static::getCurrentUrl();
            $original_url = $url;

            if( !$production_url )
              $production_url = $original_url;

            if( !$share_url )
              $share_url = static::buildShareUrl( $production_url, $campaign, 'twitter', $location );

            $social_link_html .= '<a href="javascript:void(0)" data-url="' . $share_url . '" data-original_url="' . $original_url . '" data-text="' . $text . '" data-related="' . $twitter_account . '" data-hashtags="' . $twitter_hashtags . '" class="icon tweet ' . $classes . '" ' . static::attrArrayToString($attributes) . ' target="_blank">'  . $link_pre . '<span class="text-content">' . $twitter_text . '</span></a>';
            break;

          case 'tweet-notext':

            $social_link_classes = 'twitter';

            if( !empty( $twitter_share_custom_message ) )
              $text = $twitter_share_custom_message;
            else
              $text = $title;

            if( !$url )
              $url = static::getCurrentUrl();

            $original_url = $url;

            $urlDataAttr = 'data-url=""';

            if( ( empty( $reply ) || !$reply ) && ( empty( $retweet ) || !$retweet ) )
            {


              $twitter_url = $url;
              if ( $twitter_hashtags )
                $twitter_url .= '&hashtags=' . $twitter_hashtags . '&';

              if( !$share_url )
                $share_url = static::buildShareUrl( $twitter_url, $campaign, 'twitter', $location );

              $urlDataAttr = 'data-url="' . $share_url . '" ';
            }


            $social_link_html .= '<a href="javascript:void(0)" ' . $urlDataAttr . ' data-original_url="' . $original_url . '" data-text="' . $text . '" data-related="' . $twitter_account . '" class="icon tweet ' . $classes . '" ' . static::attrArrayToString($attributes) . ' target="_blank">'  . $link_pre . '</a>';

            break;

          case 'tweet-custom':

            if(!empty($twitter_share_custom_message)) {
              $text = $twitter_share_custom_message;
            }else {
              $text = $title;
            }

            if(!$url) $url = static::getCurrentUrl();
            $original_url = $url;

            if( !$share_url )
              $share_url = static::buildShareUrl( $url, $campaign, 'twitter', $location );

            $social_link_html .= '<a href="javascript:void(0)" data-url="' . $share_url . '" data-original_url="' . $original_url . '" data-text="' . $text . '" data-related="' . $twitter_account . '" class="tweet ' . $classes . '" ' . static::attrArrayToString($attributes) . ' target="_blank">'  . $link_pre . '<span class="text-content">Tweet</span></a>';
            break;

          case 'twitter':

            $social_link_html .= '<a class="icon ' . $classes . '" ' . static::attrArrayToString($attributes) . ' href="http://twitter.com/' . $twitter_account . '" target="_blank">'  . $link_pre . '<span class="text-content">Twitter</span></a>';
            break;

          case 'retweet':
            $twitter_link = '';

            $text = 'RT ' . $title . ': ' . $description;
            $social_link_html .= '<a href="javascript:void(0)" data-url="" data-text="' . $text . '" data-related="' . $twitter_account . '" class="icon twitter-share-button ' . $classes . '" ' . static::attrArrayToString($attributes) . '>'  . $link_pre . '<span class="text-content">Retweet</span></a>';
            break;

          case 'facebook-like':
            $social_link_classes = 'facebook';
            $social_link_html .= '<div class="fb-like ' . $classes . '" ' . static::attrArrayToString($attributes) . ' data-colorscheme="dark" data-send="false" data-layout="button_count" data-width="100" data-show-faces="false" data-font="arial"></div>';
            break;

          case 'facebook-like-custom':

            if(!$url) $url = static::getCurrentUrl();
            $original_url = $url;

            if( !$share_url )
              $share_url = static::buildShareUrl( $url, $campaign, 'facebook', $location );

            $social_link_html .= '<a href="javascript:void(0)" data-url="' . $share_url . '" data-original_url="' . $original_url . '" data-img="' . $img . '" data-title="' . $title . '" data-description="' . $description . '" class="facebook-share-button ' . $classes . '" ' . static::attrArrayToString($attributes) . ' target="_blank">'  . $link_pre . '<span class="text-content">Like</span></a>';
            break;

          case 'facebook-share':
            $social_link_classes = 'facebook';

            if(!$url) $url = static::getCurrentUrl();
            $original_url = $url;

            if( !$share_url )
              $share_url = static::buildShareUrl( $url, $campaign, 'facebook', $location );

            $social_link_html .= '<a href="javascript:void(0)" data-url="' . $share_url . '" data-original_url="' . $original_url . '" data-img="' . $img . '" data-title="' . $title . '" data-description="' . $description . '" class="icon fb-share ' . $classes . '" ' . static::attrArrayToString($attributes) . ' target="_blank">'  . $link_pre . '<span class="text-content">' . $facebook_text . '</span></a>';
            break;

          case 'facebook-share-notext':

            $social_link_classes = 'facebook';

            if( !$url )
              $url = static::getCurrentUrl();

            $original_url = $url;
            if( !$share_url )
              $share_url = static::buildShareUrl( $url, $campaign, 'facebook', $location );

            $social_link_html .= '<a href="javascript:void(0)" data-url="' . $share_url . '" data-original_url="' . $original_url . '" data-img="' . $img . '" data-title="' . $title . '" data-description="' . $description . '" class="icon fb-share ' . $classes . '" ' . static::attrArrayToString($attributes) . ' target="_blank">'  . $link_pre . '</a>';

            break;

          case 'email':

            $social_link_classes = 'email';

            if( !$url )
              $url = static::getCurrentUrl();

            if(!empty($email_subject)) {
              $emailSubject = $email_subject;
            }else {
              $emailSubject = $title;
            }

            if(!empty($email_message)) {
              $emailMsg = $email_message;
            }else {
              $emailMsg = $description;
            }

            $original_url = $url;

            if( !$share_url )
              $share_url = static::buildShareUrl( $url, $campaign, 'email', $location );

            $social_link_html .= '<a href="mailto:?subject=' . rawurlencode($emailSubject) . '&body=' . rawurlencode($emailMsg . ' ' . $original_url) . '" class="no-ajax icon email-share"><span class="text-content">' . $email_text . '</span></a>';
            break;


          case 'email-notext':

            $social_link_classes = 'email';

            if( !$url )
              $url = static::getCurrentUrl();

            $original_url = $url;

            if( !$share_url )
              $share_url = static::buildShareUrl( $url, $campaign, 'email', $location );

            $social_link_html .= '<a href="mailto:?subject=' . rawurlencode($title) . '&body=' . rawurlencode($description . ' ' . $original_url) . '" class="no-ajax icon email-share"></a>';
            break;

          case 'pinterest':

            if( !$url )
              $url = static::getCurrentUrl();

            $original_url = $url;

            $share_url = 'http://pinterest.com/pin/create/button/?url=' . $original_url . '&media=' . urlencode( $img ) . '&description=' . urlencode( $title ) . '';

            $social_link_html .= '<a href="' . $share_url . '" data-url="' . $share_url . '" data-original_url="' . $original_url . '" data-img="' . $img . '" data-title="' . $title . '" data-description="' . $description . '" class="icon pin-it-button from_pinterest' . $classes . '" target="_blank" ' . static::attrArrayToString( $attributes ) . ' count-layout="horizontal">'  . $link_pre . '<span class="text-content">Pin It</span></a>';


            break;

          case 'pinterest-notext':

            if( !$url )
              $url = static::getCurrentUrl();

            $original_url = $url;

            $share_url = 'http://pinterest.com/pin/create/button/?url=' . $original_url . '&media=' . urlencode( $img ) . '&description=' . urlencode( $title ) . '';

            $social_link_html .= '<a href="' . $share_url . '" data-url="' . $share_url . '" data-original_url="' . $original_url . '" data-img="' . $img . '" data-title="' . $title . '" data-description="' . $description . '" class="icon pin-it-button from_pinterest' . $classes . '" target="_blank" ' . static::attrArrayToString( $attributes ) . ' count-layout="horizontal">'  . $link_pre . '</a>';


            break;

          case 'facebook':
            $facebook_link = '';
            $social_link_html .= '<a class="icon ' . $classes . '" ' . static::attrArrayToString($attributes) . ' href="' . $facebook_link . '" target="_blank">'  . $link_pre . '<span class="text-content">Facebook</span></a>';
            break;

          case 'instagram':
            $social_link_html .= '<span class="icon ' . $classes . '" ' . static::attrArrayToString($attributes) . '><span class="text-content">Instagram</span></span>';
            break;

          default: break;
        }

        if( !empty($social_link_html) && !empty($link_wrapper) ) {
          $html .= '<' . $link_wrapper . ' class="social-link ' . $social_link_name . ' ' . $social_link_classes . '">' . $social_link_html . '</' . $link_wrapper . '>' . "\n";
        }

      }

      if( !empty($html) && !empty($wrapper) ) {
        $html = '<' . $wrapper . ' class="social-links icons ' . $wrapper_classes . '">' . $html . '</' . $wrapper . '>';
      }

      return $html;
    }


    static public function getSocialIcons($social_links, $classes = '' )
    {
      $html = '';

      foreach($social_links as $social_link_name) {

        $social_link_html = '';
        $social_link_classes = '';
        switch ($social_link_name) {

          case 'tweet':
            $social_link_classes = 'twitter';
            $social_link_html .= '<span class="icon twitter-share-button"><span class="text-content">Tweet</span></span>';
            break;

          case 'tweet-custom':
            $social_link_html .= '<span class="twitter-share-button"><span class="icon"></span><span class="text-content">Tweet</span></span>';
            break;

          case 'twitter':
            $social_link_html .= '<span class="icon"><span class="text-content">Twitter</span></span>';
            break;

          case 'retweet':
            $social_link_html .= '<span class="icon twitter-share-button"><span class="text-content">Retweet</span></span>';
            break;

          case 'facebook-like':
            $social_link_classes = 'facebook';
            $social_link_html .= '<span class="facebook-share-button"><span class="icon"></span><span class="text-content">Like</span></span>';
            break;

          case 'facebook-like-custom':
            $social_link_html .= '<span class="facebook-share-button"><span class="icon"></span><span class="text-content">Like</span></span>';
            break;

          case 'facebook-share':
            $social_link_classes = 'facebook';
            $social_link_html .= '<span class="icon facebook-share-button"><span class="text-content">Share</span></span>';
            break;

          case 'facebook':
            $facebook_link = '';
            $social_link_html .= '<span class="icon"><span class="text-content">Facebook</span></span>';
            break;

          case 'instagram':
            $social_link_html .= '<span class="icon"><span class="text-content">Instagram</span></span>';
            break;

          default: break;
        }

        if( !empty($social_link_html) ) {
          $html .= '<span class="social-link ' . $social_link_name . ' ' . $social_link_classes . '">' . $social_link_html . '</span>';
        }

      }

      if( !empty($html) ) {
        $html = '<span class="social-links icons ' . $classes . '">' . $html . '</span>';
      }

      return $html;
    }


    static public function getCurrentUrl()
    {
      $url = Url::getCurrentUrl();
      $url = str_replace( array('?bw_history=true', '&bw_history=true', '?json=true', '&json=true'), '', $url );
      return $url;
    }


  }/* class Utils */
