<?php

  namespace BW\Utils;

  class Taxonomy
  {

    /**
     * Private construct so that this class never gets instantiated (only static)
     */
    private function __construct() {}

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

  }/* class Taxonomy */

