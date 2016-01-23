<?php

namespace com\bazaar\core\mongom;

use com\bazaar\core as core;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MongOM
 * @todo Handle Entity without name
 * @todo handle multiple annotation for same poperty
 *
 * @author gourav sarkar
 */
class MongOM {
    //put your code here

    const IDF_DOCUMENT = 'document';
    const IDF_DOCUMENT_REF = 'ref';
    const IDF_PROPERTY = 'property';
    const IDF_INDEX = 'index';
    const IDF_COL_NAME = 'name';

    private $map;
    private $model;

    public function __construct(core\model $model) {
        $this->model = new \ReflectionObject($model);

        $this->map[static::IDF_DOCUMENT] = $this->parseDocBlock($this->fetchDocBlock($this->model->getDocComment()));
        $this->map[static::IDF_DOCUMENT] [static::IDF_DOCUMENT_REF] = (string) $model;

        $this->fetchProperty();

        //xdebug_var_dump("MAP", $this->map);
    }

    /**
     * 
     * @param String $comment
     * @return array - empty if no match found
     */
    private function fetchDocBlock($comment) {
        //xdebug_var_dump($comment);
        $matches = [];
        preg_match("/mom-([\w]+)( {[\w\W\d\D]+})*/", $comment, $matches);
        //xdebug_var_dump($matches);

        return $matches;
    }

    private function parseDocBlock($matches) {
        $propertyMeta = [];
        //xdebug_var_dump($matches);
        //index 2 holds the json data
        if (!empty($matches[2])) {

            //xdebug_var_dump($matchData[2]);
            $propertyMeta = json_decode(trim($matches[2]), true);
            //xdebug_var_dump($propertyMeta);


            if (json_last_error()) {
                throw new \RuntimeException("Error while parsing annotation || Error " . json_last_error());
            }
        }
        return $propertyMeta;
    }

    public function fetchProperty() {


        $properties = $this->model->getProperties();

        foreach ($properties as $property) {

            $matches = $this->fetchDocBlock($property->getDocComment());

            if (!empty($matches)) {
                try {
                    $this->map[static::IDF_PROPERTY][$property->name] = $this->parseDocBlock($matches);
                } catch (\RuntimeException $e) {
                    throw new \RuntimeException($e->getMessage() + " at property: " + $property->getName());
                }
            }
        }
    }

    public function fetchMethod() {
        
    }

    private function handleClass($matchData) {
        
    }

    private function handleProperty($matchData) {
        
    }

    private function convert($data) {
        
    }

    private function handleIndex() {
        
    }

    private function handleEmbededDocumentOneToOne() {
        
    }

    private function handleEmbededDocumentOneToMany() {
        
    }

    private function handleReferenceOneToOne() {
        
    }

    private function handleReferenceDocumentOneToMany() {
        
    }

    public function getProperties() {
        return $this->map[static::IDF_PROPERTY];
    }

    /**
     * 
     * @return type
     * List name of properties mapped from DB
     */
    public function getPropertiesDBMapped() {
        $properties = $this->getProperties();


        array_walk($properties, function(&$value, $key) {
                    //xdebug_var_dump($key,$value );
                    $propertyName = (isset($value['name'])) ? $value['name'] : $key;
                    $value = $propertyName;
                });

        return $properties;
    }

    /**
     * 
     * @param type $data
     * @return type
     * 
     * List name of properties which is part of model propety with no persistance 
     * mechanism and does have in query data But does not mapped to model property
     */
    public function getPropertiesNonDBMapped($data) {
       // xdebug_var_dump($this->getProperties());
        //xdebug_var_dump('data',$data);

        $dbmap = $this->getPropertiesDBMapped();


        //xdebug_var_dump("PropertyData", array_keys($data));
        //xdebug_var_dump('propertyList', array_values($dbmap));


        /*
         * $propertyDiff stores list of fields as which is not in DB properties but
         * have in model property
         */
        $propertyDiff = array_diff(array_keys($data), array_values($dbmap));
        //xdebug_var_dump($propertyDiff);

        /*
         * $propsWithAlias stores map data
         */
        $propsWithAlias = array_filter($this->getProperties()
                , function( $value) {
                    return isset($value['name']);
                });

        //xdebug_var_dump($propsWithAlias,$propertyDiff);
        return array_diff($propertyDiff, array_keys($propsWithAlias));
    }

    public function getIndexes() {
        return $this->map[static::IDF_INDEX];
    }

    public function getEntity() {
        return $this->map[static::IDF_DOCUMENT];
    }

}

?>
