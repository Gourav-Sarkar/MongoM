<?php

namespace com\bazaar\core\mongom\aggregationframework;

use com\bazaar\core\mongom as mongom;
use com\bazaar\core as core;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AggregateFramework
 * @todo Map check
 * @author gourav sarkar
 */
class Aggregate implements AggregateInterface {

    private $pipeline = [];
    private $mapper;

    const SORT_ASC = 1;
    const SORT_DESC = -1;
    const SORT_META = 2;
    
    
    const OPS_GROUP = 'group';
    const OPS_SORT = 'sort';
    const OPS_MATCH = 'match';
    
    const OPS_UNWIND = 'unwind';
    const OPS_REDACT = 'redact';
    const OPS_SKIP = 'skip';
    const OPS_LIMIT = 'limit';
    const OPS_OUT = 'out';

    private $lastOperation;

    public function setMapper(mongom\MongOM $map) {
        $this->mapper = $map;
    }

    /**
     * 
     * @param type $idList
     * @return \com\bazaar\core\mongom\aggregationframework\GroupAccumulator
     */
    public function group($idList) {
        /*
        if($this->lastOperation == Aggregate::OPS_GROUP)
        {
            throw new \LogicException("You cant use group operator consequitively");
        }
         * 
         */
        
        $this->lastOperation = Aggregate::OPS_GROUP;
        return new GroupAccumulator($this, $idList);
    }
    
    
    

    public function project($whiteList=[],$blackList=[]) {
       
        throw new \BadMethodCallException("Not supported yet");
        
        //Handle wild card
        if(empty($whiteList) || empty($blackList))
        {
            $this->mapper->getProperties();
        }
    }

    
    
    
    /**
     * 
     * @param \com\bazaar\core\mongom\QueryObject $query
     * @return \com\bazaar\core\mongom\aggregationframework\Aggregate
     * 
     * @todo handle text
     */
    public function match(mongom\QueryObject $query) {
        $this->pipeline[]['$match'] = $query->getQuery();
        $this->lastOperation = Aggregate::OPS_MATCH;
        return $this;
    }

    
    /**
     * 
     * @param type $limitNum
     * @return \com\bazaar\core\mongom\aggregationframework\Aggregate
     */
    public function limit($limitNum) {
        $this->pipeline[]['$limit'] = intval($limitNum);
        return $this;
    }

    /**
     * @todo Use assert for dev level check
     */
    public function skip($skipNum) {

        $this->pipeline[]['$skip'] = intval($skipNum);
        return $this;
    }

    
    
    public function geonear(GeoNear $geoNear) {

        throw new \BadMethodCallException("Not supported yet");
    }

    
    
    /**
     * 
     * @param String $fieldName Application level Model property
     * 
     * Get application leevl model property and use mapper to get its DB property 
     * correspondance and use it in operator
     * 
     * @todo Incomplete implementation.  Mapper should be refactored before implementing this
     */
    public function unwind($fieldName) {
        //Get all Application level property of current model
        $propertyList=  $this->mapper->getProperties;
        
        //return false if not match found
        $targetProperty= array_search($fieldName,array_keys($propertyList));
        
        /*
         *  Should throw exception if property does not exist 
         *  or it is not type of collection like hasMany option true or array [incomplete]
         */
        if($targetProperty === false)
        {
            throw new \RuntimeException("Either Specified field cant be or field is not type of collection (array) ");
        }
        
        $targetPropertyName=(isset($targetProperty['name']))?$targetProperty['name']:$targetProperty;
        
    }

    
    
    /**
     * 
     * @param mixed $collection string as collection name or core\Model
     *      * 
     * It must be last operation so this does not return reference
     * @todo use collection name from model
     */
     
    public function out($collection) {
        
        if($collection instanceof core\Model)
        {
            $mongomap= new mongom\MongOM($model);
            $collection =$mongomap->getEntity();
        }
            $this->pipeline[]['$out'] = $collection;
    }
    
    

    /**
     * 
     * @param mixed $sortType Either string or SORT_ASC or SORT_DESC 
     * @return \com\bazaar\core\mongom\aggregationframework\Aggregate
     */
    public function sort($field, $sortType) {

        if (!in_array($sortType, [Aggregate::SORT_ASC, Aggregate::SORT_DESC]) || !is_string($sortType)) {
            throw new \LogicException("Sort type mismatch");
        }

        if ($sortType == Aggregate::SORT_META) {
            $sortop = [$field => ['$meta' => $sortType]];
        } else {
            $this->pipeline[]['$sort'] = [$field => $sortType];
        }


        //If previously it was sort opearation merge it
        if ($this->lastOperation == Aggregate::OPS_SORT) {
            $lastop = end($this->pipeline);
            $sortop = array_merge($lastop['$sort'], $sortop);
        }
        
        $this->pipeline[]['$sort'] = $sortop;

        //Mark the operation
        $this->lastOperation = Aggregate::OPS_SORT;


        return $this;
    }

    
    
    public function redact() {
        throw new \BadMethodCallException("Not supported yet");
    }

    
    
    public function getQuery() {
        return $this->pipeline;
    }

    public function end($operation) {
        $this->pipeline[]['$' . $this->lastOperation] = $operation;
        unset($this->lastOperation);
    }

}

/*
 * $agf= new AggregateFramework();
 * 
 * $agf->group()->sum('field','price')->sum('count',1)->addToPipleLine()->sort();
 */
?>
