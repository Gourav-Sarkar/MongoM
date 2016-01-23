<?php

namespace com\bazaar\core\mongom\aggregationframework;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GroupOperator
 *
 * @author gourav sarkar
 */
class GroupAccumulator {

    //put your code here
    private $aggregateFramework;
    private $groupOperation;

    /**
     * 
     * @param \com\bazaar\core\mongom\aggregationframework\Aggregate $agf
     * @param type $id
     * @todo second parameter $id should have an array
     */
    public function __construct(Aggregate $agf, $id) {
        $this->aggregateFramework = $agf;


        if (empty($id)) {
            $this->groupOperation['_id'] = null;
        } else {
            $this->groupOperation['_id'] = '$' . $id;
        }
        /*
          $this->groupOperation['_id'] = array_map(function($item) {
          return '$' . $item;
          }, $id);
         * 
         */
    }

    public function sum($field, $operation) {
        $stmt = [];
        //String is identifier for field
        if (is_string($operation)) {
            $stmt = [$field => '$' . $operation];
        }
        //Boolean
        else if (is_bool($operation)) {
            //Convert to integer
            $stmt = [$field => ['$sum' => (int) $operation]];
        } else {
            throw new \RuntimeException();
        }

        $this->groupOperation = array_merge($this->groupOperation, $stmt);

        return $this;
    }

    public function avg() {

        return $this;
    }

    public function first() {

        return $this;
    }

    public function last() {

        return $this;
    }

    public function min() {

        return $this;
    }

    public function max() {

        return $this;
    }

    public function push() {
        return $this;
    }

    public function addToSet() {

        return $this;
    }

    public function end() {
        $this->aggregateFramework->end($this->groupOperation);

        return $this->aggregateFramework;
    }

}

?>
