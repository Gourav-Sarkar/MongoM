<?php

namespace com\bazaar\core\mongom;

use com\bazaar\core as core;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UnitOfWork
 *
 * @author gourav sarkar
 */
class UnitOfWork {

    //Unitofwork instance
    private static $instance;
    //List of object created in memory, but is not synced to DB yet
    private $newObjects;
    //List of objects marked as edited, with edited field
    private $dirtyObjects;
    //List of objects marked to be removed
    private $removedObjects;
    private $cleanObjects;
    //private $datastore;

    private function __construct() {

        $this->dirtyObjects = new IdentityMap();
        $this->newObjects = new IdentityMap();
        $this->removedObjects = new IdentityMap();


        $this->cleanObjects = new IdentityMap();

        //$this->datastore = new MongOMDatastore(new \MongoClient());
    }

    public static function getInstance() {
        if (!UnitOfWork::$instance instanceof UnitOfWork) {
            UnitOfWork::$instance = new UnitOfWork();
        }

        return UnitOfWork::$instance;
    }

    /**
     * 
     * @param \com\bazaar\core\Model $model
     * @param type $fieldName
     * 
     * Do not register if object is CLEAN = newly loaded from DB
     * or is NEW= created newly in object memory
     */
    public function registerDirty(core\Model $model, $fieldName = '') {
        //Check if object has no id 
        $fields = [];


        assert('$model instanceof com\bazaar\core\Model');

        /*
         * Only clean objects are updateable
         * If object is newly created it does not represetn database data it will
         * be treated as new data
         */
        if ($this->cleanObjects->contains($model)) {
            if (!empty($fieldName)) {

                //get Fields from identitymap;
                if ($this->dirtyObjects->contains($model)) {

                    $fields = $this->dirtyObjects->offsetGet($model);
                }

                array_push($fields, $fieldName);
                //xdebug_var_dump("Field" , $fields);
                //xdebug_var_dump($this->dirtyObjects->offsetGet($model));

                $this->dirtyObjects->attach($model, $fields);
            }
        }
    }

    /**
     * 
     * @param \com\bazaar\core\Model $model
     * 
     * Do not register if object is CLEAN = newly loaded from DB
     * Not need an id
     * or is DIRTY= created newly in object memory
     * 
     * Do not log Embeded model - embeded models are not separate entity. It 
     * itself are atomic and dependeant upon parent model
     */
    public function registerNew(core\Model $model) {
        //Reegister as new if object is not in clean list or dirty list
        //if object is an embeded model
        //xdebug_var_dump(__METHOD__);
        // xdebug_var_dump($this->cleanObjects->contains($model));
        //xdebug_var_dump($this->dirtyObjects->contains($model));

        if (!$this->cleanObjects->contains($model)
                && !$this->dirtyObjects->contains($model)) {
            //xdebug_var_dump("EMBED TEST===" ,$model instanceof core\EmbededModel ,$model);
            $this->newObjects->attach($model);
        }
    }

    public function unRegisterNew(core\Model $model) {
        //Remove object from new list
        $this->newObjects->detach($model);
    }

    /**
     * 
     * @param \com\bazaar\core\Model $model
     *  Must have an id
     * Do not register if is NEW= created newly in object memory
     */
    public function registerRemoved(core\Model $model) {
        if (!$this->newObjects->contains($model)) {
            $this->dirtyObjects->attach($model);
        }
    }

    /**
     *  Mark clean
     * Clean object must have ID
     *  cant be in dirty,new or removed list
     * @param \com\bazaar\core\Model $mdoel
     */
    public function registerClean(core\Model $model) {

        if (!$this->removedObjects->contains($model)
                && !$this->dirtyObjects->contains($model)
                && !$this->newObjects->contains($model)
                && !$model instanceof MongOMElement\EmbededElement) {
            $this->cleanObjects->attach($model);
        }
    }

    
    public function getNewObjects()
    {
        return $this->newObjects;
    }
    /**
     * Handle database transaction for insert
     * Init batch insert
     * if insert is success full
     *      remove object from new list
     *      insert the object in clean list
     * 
     * @todo handle exception
     * @todo Handle partial insertion
     */
    
    /*
    private function doInsert() {
       // xdebug_var_dump("Insert new objects " . $this->newObjects->count());


        //Batch insert if transaction success maintain object cache
        $newInsertData = $this->datastore->save($this->newObjects);
    }
     * 
     */

    /**
     * Init batch delete
     * if delete is success full
     *      remove object from remove list
     *      remove object from clean list
     */
    private function doRemove() {

        //if transaction success

        foreach ($this->removedObjects as $object) {
            //xdebug_var_dump($obj instanceof ArrayObject);


            //xdebug_var_dump("Remove", $this->dirtyObjects[$object]);



            //if transaction success
            //remove from remove list
            $this->removedObjects->detach($object);

            //Delete the object reference
            //remove them to clean list
            $this->cleanObjects->detach($object);
        }
    }

    /**
     * Init batch update
     * calculate fields for update
     * if update is success full
     *      remove object from dirty list
     *      add those object to clean list
     */
    
    /*
    private function doUpdate() {


        $newInsertData = $this->datastore->update($this->dirtyObjects);
            //xdebug_var_dump("Total dirty object " . $this->dirtyObjects->count());

        foreach ($this->dirtyObjects as $object) {
            //xdebug_var_dump($obj instanceof ArrayObject);



            //if transaction success
            //remove from dirty list
            $this->dirtyObjects->detach($object);
            //add them to clean list
            $this->registerClean($object);
        }
    }
     * 
     */

    /**
     * 
     * @param type $isAtomic Flag for atomic transaction
     * @todo Atomic transaction
     * 
     * Moved to datastore
     */
    
    /*
    public function commit($options = []) {
        //xdebug_var_dump("Unit of work commiting..");
        $this->doInsert();
        $this->doRemove();
        $this->doUpdate();
    }
     * 
     */

    /**
     * Mark loaded object as clean by adding in cleanObjects 
     * CleanObjects identity map can be used as cached layer too
     */
    

    public function isNew(core\Model $model) {
        return $this->newObjects->contains($model);
    }

    public function isClean(core\Model $model) {
        return $this->cleanObjects->contains($model);
    }

    public function isRemoved(core\Model $model) {
        return $this->removedObjects->contains($model);
    }

}

?>
