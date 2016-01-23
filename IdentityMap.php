<?php
namespace com\bazaar\core\mongom;
use com\bazaar\core as core;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of IdentityMap
 *
 * @author gourav sarkar
 */
class IdentityMap extends \SplObjectStorage{
    //put your code here
    public function getHash($model)
    {
        assert('$model instanceof com\bazaar\core\Model');
        $id=$model->getID();
        
       // assert('!empty($id)');
        
        //New object usually dont have id
        if(empty($id))
        {
            $id=  spl_object_hash($model);
        }
        
        //xdebug_var_dump((String)__CLASS__ . " $model\\{$id}");
        return (String)"$model\\{$id}";
    }
    
    
}

?>
