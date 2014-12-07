<?php

  namespace BW\Utils;

  class Data
  {

    /**
     * Private construct so that this class never gets instantiated (only static)
     */
    private function __construct() {}


    /**
     * Converts an XML string to a formatted associative array
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (string) $xml - XML data to convert into array
     * @return (array)
     */

    static public function xmlToArray( $xml )
    {
      $obj = new \SimpleXMLElement($xml);

      return static::xmlObjToArray($obj);

    }/* xmlToArray */


    /**
     * Converts a properly formatted array into an XML string
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (array) $array - associative array representing XML data, to be converted into XML data
     * @return (string)
     */

    static public function arrayToXml( $array )
    {
      $dom = static::arrayToXmlDom($array);

      return $dom->saveXML();

    }/* arrayToXml */


    /**
     * Converts an XML dom object into a formatted associative array
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (SimpleXMLElement) $obj - XML object to convert into array
     * @return (array)
     */

    static public function xmlObjToArray($obj)
    {
      // add namespaces to top element
      $doc_namespaces = $obj->getDocNamespaces(true);
      $namespaces = array();
      foreach( $doc_namespaces as $ns => $ns_url ) {
        $ns = trim((string)$ns);
        $ns_url = trim((string)$ns_url);
        if (empty($ns)) {
          $ns = 'xmlns';
        }else{
          $ns = 'xmlns:' . $ns;
        }
        $namespaces[$ns] = $ns_url;
      }
      $array = static::xmlObjToArrayRecursion($obj);
      $array['namespaces'] = $namespaces;

      return $array;

    }/* xmlObjToArray() */



    /**
     * Converts a properly formatted array into an XML dom object (SimpleXMLElement)
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (array) $array - formatted array represanting an XML object
     * @return (SimpleXMLElement)
     */

    static public function arrayToXmlDom( $array )
    {
      $dom = new \DOMDocument('1.0.0', 'UTF-8');

      // create element
      if(empty($array['text'])) {
        $node = $dom->createElement($array['name']);
      }else{
        $node = $dom->createElement($array['name'], $array['text']);
      }

      // children
      if(!empty($array['children'])){
        foreach($array['children'] as $child_name => $child_array) {
          // recrusive call.
          $child_array[0]['name'] = $child_name;
          $child_dom = static::arr_to_xml_dom($child_array[0]);
          $child_node = $dom->importNode($child_dom->documentElement, true);
          $node->appendChild($child_node);
        }
      }

      $root = $dom->appendChild($node);

      // namespaces
      if(!empty($array['namespaces'])){
        foreach($array['namespaces'] as $ns => $ns_url) {
          $root->setAttribute($ns, $ns_url);
        }
      }

      // attributes
      if(!empty($array['attributes'])){
        foreach($array['attributes'] as $attribute_name => $attribute_value) {
          $root->setAttribute($attribute_name, $attribute_value);
        }
      }

      return $dom;

    }/* arrayToXmlDom() */


    /**
     * Recursive function that converts an XML object into an array
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (SimpleXMLElement) $obj - XML object to convert
     * @return (array)
     */

    static private function xmlObjToArrayRecursion($obj)
    {
      $doc_namespaces = $obj->getDocNamespaces(true);
      $doc_namespaces[NULL] = NULL;

      $children = array();
      $attributes = array();
      $name = strtolower((string)$obj->getName());

      $text = trim((string)$obj);
      if( strlen($text) <= 0 ) {
        $text = NULL;
      }

      // get info for all namespaces
      if(is_object($obj)) {
        foreach( $doc_namespaces as $ns=>$ns_url ) {
          // atributes
          $obj_attributes = $obj->attributes($ns, true);
          foreach( $obj_attributes as $attribute_name => $attribute_value ) {
            $attrib_name = trim((string)$attribute_name);
            $attrib_val = trim((string)$attribute_value);
            if (!empty($ns)) {
              $attrib_name = $ns . ':' . $attrib_name;
            }
            $attributes[$attrib_name] = $attrib_val;
          }

          // children
          $obj_children = $obj->children($ns, true);
          foreach( $obj_children as $child_name=>$child ) {
            $child_name = (string)$child_name;
            if( !empty($ns) ) {
              $child_name = $ns.':'.$child_name;
            }
            $children[$child_name][] = static::xmlObjToArrayRecursion($child);
          }
        }
      }

      return array(
        'name'=>$name,
        'text'=>$text,
        'attributes'=>$attributes,
        'children'=>$children
      );

    }/* xmlObjToArrayRecursion() */


    /**
     * Array utility
     *
     * @author Alessandro Biavati <ale@briteweb.com>
     * @package briteweb/utils
     * @since 1.0.0
     * @param (array) $original
     * @param (array) $insert - array to insert at $offset
     * @param (int) $offset
     * @return null
     */
    static public function arrayInsertAtIndex($original, $insert, $offset)
    {
      return array_merge( array_slice($original, 0, $offset, true), $insert, array_slice($original, $offset, null, true) );

    }/* arrayInsertAtIndex() */



    /**
     * Creates a div with data attributes or a script with a JSON array in it
     * based on the data array provided as argument
     */
    static public function dataTag($id, $data, $tag = 'script')
    {
      $html = '';

      if ( $tag == 'script' ) {

        $html .= '<script id="' . $id . '" type="application/json">';
        $html .= json_encode($data);
        $html .= '</script>';

      }else{

        $html .= '<div id="' . $id . '"';

        foreach ($data as $key => $value) {
          if ( is_string($value) || is_numeric($value) ) {
            $html .= ' data-' . $key . '="' . $value . '"';
          }
        }

        $html .= '></div>';

      }

      return $html;
    }



  }/* Class Data */

