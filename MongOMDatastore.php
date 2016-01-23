<?php

namespace com\bazaar\core\mongom;

use com\bazaar\core as core;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MongOMDatastore
 *
 * @author gourav sarkar
 */
class MongOMDatastore {

    //put your code here
    private $connection;

    public function __construct(\MongoClient $mongoCon) {
        $this->connection = $mongoCon->{PROJECT_NAME};
    }

    public function getConnection() {
        return $this->connection;
    }

    /**
     * 
     * @param \com\bazaar\core\Model $model
     * @param type $options
     * @throws \RuntimeException
     * 
     * @todo Handle property with different files
     * 
     * This method takes one or more model
     * Access its mapper
     * get the models value mapped by the appropiate mapper
     * handle scalar and embeded document
     * prepare the data
     * insert to database
     * check for valid transaction
     * update the id of corresponding models
     * 
     */
    private function prepareSave(core\Model $model, $options = []) {
        $data = []; //converted data for DB transaction
        $propertyData = ''; //property data accesed using getter
        $getter = '';   //getter name
        //Get mapper for each model
        $mapper = new MongOM($model);

        //xdebug_var_dump("Property map of " . $mapper->getEntity()['name'], $mapper->getProperties());
        //Recover property value
        //loop through each property and populate the array with data
        foreach ($mapper->getProperties() as $property => $propertyMeta) {


            $propertyName = (isset($propertyMeta['name'])) ? $propertyMeta['name'] : $property;
            //xdebug_var_dump("PROPPP",$property);
            //getter method must have been set otherwise use default getter
            $getter = (isset($propertyMeta['getter'])) ? $propertyMeta['getter'] : $getter = "get{$property}";

            //xdebug_var_dump($getter);
            //If getter is not there throw exception
            //@todo change to specific exception
            if (!method_exists($model, $getter)) {
                throw new \RuntimeException("MongoM: Getter method is not defined for model {$model} and property {$property}");
            }


            //Access property using getter
            $propertyData = $model->$getter();




            /*
             * Handle embeded document
             */
            if (isset($propertyMeta['isEmbeded']) && $propertyMeta['isEmbeded'] == true) {

                /*
                 * Handle many documents
                 */
                if ($propertyMeta['hasMany'] == false) {
                    $data[$propertyName] = $this->prepareSave($propertyData, $options);
                } else {
                    //Initialize for blank properties
                    $data[$propertyName] = [];


                    assert('$propertyData instanceof Traversable /* Probable data mis match of property and propertylist */');

                    //assert(empty($propertyData));

                    /*
                     * Handle non empty list of Embeded document
                     * Handle Countable interface and array
                     */
                    if (!empty($propertyData) || ($propertyData instanceof \Traversable && $propertyData instanceof \Countable && $propertyData->count() !== 0)) {
                        //trigger_error(\count($propertyData));

                        foreach ($propertyData as $modelItem) {
                            //Embeded documents should be flatened and push to array

                            $flatenModel = $this->prepareSave($modelItem, $options);
                            //Do not add blank models
                            if (!empty($flatenModel)) {
                                $data[$propertyName][] = $flatenModel;
                            }
                        }
                    }
                }
            }
            /*
             * Handle referenced document
             */ elseif (isset($propertyMeta['isReference']) && $propertyMeta['isReference'] == true) {

                /*
                 * Handle single reference document
                 */
                if ($propertyMeta['hasMany'] == false) {

                    /*
                     * Handle FK and id fetching depending on key FK and index
                     * index must be unique
                     * @todo assert if method exist or not
                     * @todo assert for null value in id (FK)
                     */
                    if (isset($propertyMeta['fk']) && isset($propertyMeta['index']) && $propertyMeta['index'] == 'unique') {
                        $data[$propertyName] = $propertyData->{"get{$propertyMeta['fk']}"}();
                        //xdebug_var_dump($data[$propertyName]);
                    } else {
                        $data[$propertyName] = $propertyData->getID();
                    }
                }
                /*
                 * Handle many reference document
                 */ else {
                    $data[$propertyName] = [];  //initalize property


                    /*
                     * Handle non empty list of reference
                     * Handle Countable interface and array
                     */
                    if (!empty($propertyData) || ($propertyData instanceof \Traversable && $propertyData instanceof \Countable && $propertyData->count() != 0)) {
                        //xdebug_var_dump($propertyData);
                        //assert(false);
                        foreach ($propertyData as $modelItem) {

                            //xdebug_var_dump($modelItem);
                            assert('$modelItem instanceof \com\bazaar\core\Model');

                            $refID = $modelItem->getID();
                            //$refID= (empty($refID))? new \MongoId() : $refID; 
                            //xdebug_var_dump("REFID",$modelItem);
                            $data[$propertyName][] = $refID;

                            //
                        }
                    }
                }
            }
            /*
             * Handle scalar fields and PHP array fields null fields
             */ else if (is_scalar($propertyData) || is_array($propertyData)) {
                //xdebug_var_dump("scalar " . $property);

                /*
                 * Handle id
                 * id is always scalar dont execute later part if id is found
                 */


                $data[$propertyName] = $propertyData;
            } elseif (is_null($propertyData)) {
                //Do not add null value for id
                if (!isset($propertyMeta['id']) || $propertyMeta['id'] == false) {
                    $data[$propertyName] = $propertyData;
                }
            }
            /*
             * Handle other data types
             */ else {
                //xdebug_var_dump($propertyData, $property);
                //throw new \RuntimeException("Attempt to Unknown data type conversion of " . $propertyData);
            }
        }

        return $data;
    }

    private function save($models, $options = []) {
        //xdebug_var_dump("Save");

        $batchData = [];
        $data = [];
        $collection = [];



        //Loop through all object
        //get map
        //get data
        //append
        $models->rewind();
        while ($models->valid()) {

            $model = $models->current();
            //xdebug_var_dump("Log object========");
            //xdebug_var_dump("Model", $model);
            //xdebug_var_dump("Log object========");


            /*
             * This part are used to get only collection name
             * 
             */
            $mapper = new MongOM($model);

            $entity = $mapper->getEntity();
            $collection = $entity[MongOM::IDF_COL_NAME];
            //==================================

            $data = $this->prepareSave($model);

            //@todo unset if it is empty other wise retain its value
            unset($data['_id']);

            // xdebug_var_dump("###################################################", "COLLECTION_NAME $collection", $data);

            $this->connection->{$collection}->insert($data, ["w" => true]);
            $model->setID($data["_id"]);



            $models->next();

            UnitOfWork::getInstance()->unregisterNew($model);
            UnitOfWork::getInstance()->registerClean($model);

            $batchData[] = $data;

            // xdebug_var_dump($data);
            //xdebug_var_dump("#################################################");
        }


        //xdebug_var_dump("Final data", $data);
        //@throws Throw exception if $entity['name'] is no there
        //do not try to insert if there is no data
        if (!empty($batchData)) {
            //xdebug_var_dump("Data for insert processing BATCHDATA", count($batchData), $batchData);
            //$this->connection->{$collection}->batchInsert($batchData,$options);
        }

        return $batchData;
    }

    /**
     * 
     * @param core\Model $updateModels
     * @param QueryObject $queryObject
     * 
     * Only valid for cleaned object laoded from DB
     * 
     * @todo Update now onl;y handles list collection using $pushAll. Update will be made
     *  to mae support set collection using $addToSet
     * 
     */
    public function update(core\Model $updateModel, QueryObject $queryObject = NULL, $options = []) {
        // xdebug_var_dump("Update new objects " . $updateModels->count());
        $updateModelSerialized=[];

        $modelMap = new MongOM($updateModel);
        $data = $this->prepareSave($updateModel, $options);

        //xdebug_var_dump('Data' ,$data);
        //xdebug_var_dump($modelMap->getProperties());
        
        
        /*
         * @todo Replace flag
         * If REPLACE flag is off
         * Remove mongo id
         */
        unset($data['_id']);

        
        
        $collectionFields = array_filter($data, function ($value) {
                    //xdebug_var_dump($value,is_array($value));
                    return is_array($value);
                }
        );
        
        $singularFields = array_diff_key($data, $collectionFields);
        //xdebug_var_dump($collectionFields,$singularFields);
        
        $updateModelSerialized=['$set' => $singularFields, '$pushAll' => $collectionFields];
        //xdebug_var_dump($updateModelSerialized,$queryObject->getQuery());
        
        
        /*
         * @todo will be removed when untiofworker will be chnaged.
         * Commits will be made from unitof work
         */
        $collection=$modelMap->getEntity();
        //xdebug_var_dump($collection);
        $updatestatus=$this->connection->$collection['name']->update($queryObject->getQuery(),$updateModelSerialized,$options);
       // xdebug_var_dump($queryObject->getQuery(),$updatestatus);
    }

    /**
     * 
     * @param \com\bazaar\core\mongom\MongOM $map
     * @param array $data
     * @param bool $fromNonDB generate object from Model directly or Database using map
     * @return \com\bazaar\core\mongom\className
     * @throws \RuntimeException
     */
    public static function loadObject(MongOM $map, $data, $fromNonDB = true) {
        $classMeta = $map->getEntity();
        $className = $classMeta[MongOM::IDF_DOCUMENT_REF];
        $setterName = $getterName = '';

        /*
         * @todo will be removed when unitofwork workflow will be changed
         * 
         * _id is mandatory but some documents like embeded document may not have 
         * _id
         */
        $id = ($fromNonDB) ? 'id' : '_id';


        $modelID = (!empty($data[$id])) ? $data[$id] : null;
        $model = new $className($modelID);

        //xdebug_var_dump($data);


        /*
         * Loop through all fields that does not belongs to database or persitence storage
         * these fields are usually generated from calculated field or mostly
         * Aggregated data. 
         * 
         * Currently it handles only scalar values
         */
        foreach ($map->getPropertiesNonDBMapped($data) as $nondbfield) {
            $setterName = "set{$nondbfield}";

            if (!method_exists($model, $setterName)) {
                throw new \RuntimeException("Setter method is not available for {$nondbfield}");
            }

            /*
             * @todo id mapping
             * Skip ID field
             */
            //xdebug_var_dump($nondbfield);

            $model->$setterName($data[$nondbfield]);
        }




        /*
         * Loop through all property
         */
        //xdebug_var_dump($map->getPropertiesDBMapped());
        foreach ($map->getPropertiesDBMapped() as $propertyNameModel => $propertyNameDB) {

            if ($fromNonDB) {
                $propertyNameDB = $propertyNameModel;
            }

            //xdebug_var_dump($data);

            $propertyMap = $map->getProperties()[$propertyNameModel];

            /*
             * @important
             * use this setter getter name later in code for setter and getter
             * $propertyName matches with model property name so it matches with DEFAYLT setter name
             */
            $setterName = (isset($propertyMap['setter'])) ? $propertyMap['setter'] : "set{$propertyNameModel}";
            $getterName = (isset($propertyMap['getter'])) ? $propertyMap['getter'] : "get{$propertyNameModel}";

            //xdebug_var_dump($setterName);

            if (!method_exists($model, $setterName)) {
                throw new \RuntimeException("Setter method is not available for {$propertyNameModel} in {$className}");
            }

            //$propertyName = (isset($propertyMap['name'])) ? $propertyMap['name'] : $propertyName;
            // xdebug_var_dump($propertyNameDB);

            /*
             * 'reference' means it can be embeded or it can be referenced
             */
            if (isset($propertyMap['reference'])) {

                //Get map for object
                $className = $propertyMap['reference'];
                $refPropMap = new MongOM(new $className());




                /*
                 * Handle many documents
                 */
                if (isset($propertyMap['hasMany']) && $propertyMap['hasMany']) {
                    /**
                     * @internal Get the list and update it instead of creating new
                     * that way it dont need to look for list type and use 
                     * ArrayAccess interface to manipulate its data
                     * Also it will by pass need of setter method for list
                     * 
                     * create new map for heterogenous collecion otherwise use same
                     * map
                     */
                    $list = $model->{$getterName}();
                    assert('$list instanceof ArrayAccess');

                    foreach ($data[$propertyNameDB] as $listItemModelData) {
                        //xdebug_var_dump("listdata",$listItemModelData);

                        if (isset($propertyMap['isEmbeded']) && $propertyMap['isEmbeded']) {
                            $list[] = MongOMDatastore::loadObject($refPropMap, $listItemModelData, false);
                        } else {
                            /*
                             * only id reference is stored in list. so just get the id
                             * Create object with only id
                             */
                            $className = $propertyMap['reference'];
                            $list[] = new $className($listItemModelData);
                        }
                    }
                } else {
                    //xdebug_var_dump($fkFlag);

                    /*
                     * Handle embeded data
                     */
                    if (isset($propertyMap['isEmbeded']) && $propertyMap['isEmbeded']) {
                        $model->{$setterName}(MongOMDatastore::loadObject($refPropMap, $data[$propertyNameDB], false));
                    }

                    /*
                     * Handel refrence data which could have default FK or explicit FK
                     * 
                     * @todo index and fk comaptibility could be tested here also
                     */ else if (isset($propertyMap['isReference']) && $propertyMap['isReference']) {
                        //xdebug_var_dump("Reference");
                        /*
                         * explicit FK
                         * Dont iterate. initialize the  model and set its properties
                         */
                        if (isset($propertyMap['fk'])) {
                            //xdebug_var_dump($model);
                            $fkModel = new $propertyMap['reference']();

                            /*
                             * @todo setter existance valdiation
                             * @todo mark incomplete or ghost object marking for lazy loading
                             */
                            $fkModel->{"set{$propertyMap['fk']}"}($data[$propertyNameDB]);

                            $model->$setterName($fkModel);
                        }
                        /*
                         * Default _id FK
                         */ else {
                            $model->{$setterName}(MongOMDatastore::loadObject($refPropMap, $data[$propertyNameDB], false));
                        }
                    }
                }
            }
            /*
             * Handle scalar data
             */ else {

                /*
                  $propertyData = (isset($data[$propertyName])) ? $data[$propertyName] : '';
                  $model->{$setterName}($propertyData);
                 * 
                 */


                //xdebug_var_dump($propertyMap);
                /*
                 * If data is not available use default data stored in property init
                 * In models
                 */
                if (isset($data[$propertyNameDB])) {
                    //xdebug_var_dump("$setterName setting data ");

                    /*
                     * @todo will be removed when unitofwork workflow will be changed
                     */
                    if (isset($propertyMap['id']) && $propertyMap['id']) {
                        break;
                    }

                    $model->{$setterName}($data[$propertyNameDB]);
                }
            }
        }



        /**
         * @internal If object is loaded from DB it is clean and complete. So it should be marked as clean
         * @todo Will be removed in future
         */
        if (!$fromNonDB) {
            UnitOfWork::getInstance()->registerclean($model);
        }


        return $model;
    }

    /*
     * Public interface
     */

    public function insert(core\Model $model) {
        //xdebug_var_dump('insert');
        UnitOfWork::getInstance()->registerNew($model);
    }

    /*
      public function remove(core\Model $model) {
      UnitOfWork::getInstance()->registerRemoved($model);
      }
     * 
     */

    public function commit($options = []) {
        //xdebug_var_dump("Unit of work commiting..");
        $this->save(UnitOfWork::getInstance()->getNewObjects());
        // UnitOfWork::getInstance()->doRemove();
        // UnitOfWork::getInstance()->doUpdate();
    }

    public function query($model, QueryInterface $queryObject) {
        $modelList = [];
        $query = $queryObject->getQuery();

        //xdebug_var_dump($model);
        $mapper = new MongOM($model);
        $queryObject->setMapper($mapper);

        $collection = $mapper->getEntity();

        //xdebug_var_dump($collection[MongOM::IDF_COL_NAME], $query);

        if ($queryObject instanceof QueryObject) {
            /* @var $cursor MongoCursor */
            $cursor = $this->connection->{$collection[MongOM::IDF_COL_NAME]}->find($query);


            /*
             * Handle pagination
             */
            //xdebug_var_dump($queryObject->getLimit(),$queryObject->getSkip());

            /*
             * Only for query object . not for aggregate
             */
            $cursor->limit($queryObject->getLimit());
            $cursor->skip($queryObject->getSkip());
        } else if ($queryObject instanceof aggregationframework\AggregateInterface) {
            $cursor = $this->connection->{$collection[MongOM::IDF_COL_NAME]}->aggregateCursor($query);
        }

        //xdebug_var_dump($cursor->count(true));


        /*
         * Could be change to return only one cursor
         * Detecting NO RESULT is more generic and suitable
         * Further limit can be made later
         */
        if ($cursor->count(true) == 0) {
            throw new core\rest\ResourceNotFoundException("Specified Resource could not be found");
        }

        foreach ($cursor as $document) {
            //Same mapper will be used
            //xdebug_var_dump($document);
            $model = MongOMDatastore::loadObject($mapper, $document, false);
            $modelList[] = $model;
        }

        //xdebug_var_dump(count($modelList));

        return $modelList;
    }

}

?>
