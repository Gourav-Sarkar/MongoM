<?php

namespace com\bazaar\core\mongom;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of QueryObject
 *
 * @author gourav sarkar
 */
class QueryObject implements QueryInterface {
    //put your code here

    const QO_COND_OPS_NONE = 0;
    const QO_COND_OPS_OR = 1;
    const QO_COND_OPS_AND = 2;
    const QO_COND_OPS_NOT = 3;

    private $stmt = [];
    private $allowedField = [];
    private $disallowedField = [];
    private $skip = 0;
    private $limit = 0;
    //private $start=0;
    // private $offset=0;
    //private $orderBy;


    private $lastOps = QueryObject::QO_COND_OPS_NONE;
    private $groupVia = false;
    private $modelMap;

    public function setMapper(MongOM $map) {
        $this->modelMap = $map;
    }

    public function setOffset($skip, $limit) {
        $this->skip = $skip;
        $this->limit = $limit;
    }

    public function setPage($page, $itemLimit) {
        if (!empty($page)) {
            $this->skip = $itemLimit * ($page - 1);
            $this->limit = $itemLimit;
        }

        //xdebug_var_dump("FOO", $page, $this->limit, $this->skip);
    }

    public function getLimit() {
        return $this->limit;
    }

    public function getSkip() {
        return $this->skip;
    }

    /*
     * Relational operator
     * cant have consequitive calls of relational operator 
     * - only execute if lastoperation is other than conditional operation
     */

    public function gt($field, $value) {
        $this->stmt = [$field => ['$gt' => $value]];
    }

    public function lt($field, $value) {
        $this->stmt = [$field => ['$lt' => $value]];
    }

    public function gte($field, $value) {
        $this->stmt = [$field => ['$gte' => $value]];
    }

    public function lte($field, $value) {
        $this->stmt = [$field => ['$lte' => $value]];
    }

    /**
     * 
     * @param type $field
     * @param type $value
     * 
     * $field could be array, scalar, embeded document
     * 
     * Currently handles onsly single field
     * 
     * to make it handle array queryobject needs to consult mongomapper object
     */
    public function eq($field, $value) {
        if (is_array($value)) {
            $this->stmt = [$field => ['$in' => $value]];
        } else if (is_object($value)) {
            if ($value instanceof ops) {
                $this->stmt = [$field => $value->getQuery()];
            } else if ($value instanceof \MongoID) {
            $this->stmt = [$field => $value];
                
            } else {
                $this->stmt = [$field => ['$elemmatch' => $value]];
            }
        } else {
            $this->stmt = [$field => $value];
        }
        /* else {
          throw new \RuntimeException("Invalid Data type specified");
          }
         * 
         */
        return $this;
    }

    public function neq($stmt) {
        
    }

    /*
     * If consequtive logical operator is used group the buffered query and add the new one
     */

    /*
     * Logical operator
     * Cant be the first call - when buffer is empty
     * consequitive call push the buffer to query
     */

    public function _and() {
        //$this->lastOps = static::QO_COND_OPS_AND;

        $smts = func_get_args();
        $this->stmt = ['$and' => $stmts];
    }

    public function _or($stmt) {
        //$this->lastOps = static::QO_COND_OPS_OR;
        $smts = func_get_args();
        $this->stmt = ['$or' => $stmts];
    }

    public function _not($stmt) {
        //$this->lastOps = static::QO_COND_OPS_NOT;
        $smts = func_get_args();
        $this->stmt = ['$not' => $stmts];
    }

    public function group() {
        
    }

    public function getQuery() {
        return $this->stmt;
    }

    /*
      public function __toString() {


      $query=$this->stmt;
      //unset($this->stmt);
      return json_encode($query);
      }
     */
}

/*
 * $query = new QueryObject();
 * $query->eq('foo','bar')->_and()->neq('baf','bar')->_or()->eq("baz","beta)->group()->_and();
 * $query->eq('foo','bar')->_and()->neq('baf','bar')->_or(true)->eq("baz","beta);
 * 
 * $query->and(); //error
 * $query->eq("baz","baf")->neq("baf","baz"); //error
 * 
 * $query->and($query->eq(), $query->neq());
 */
?>
