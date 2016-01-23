<?php

namespace com\bazaar\core\mongom\MongOMElement;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of IndexElement
 *
 * @author gourav sarkar
 */
class ReferenceElement extends EntityElement{
    
    const REF_TYPE_EMBEDED='embeded';
    const REF_TYPE_REFERENCE='reference';
    
    const IDF_REF_TYPE='type';
    const IDF_HAS_MANY='hasmany';
    const IDF_REFERENCE='ref';
    
    private $type;
    private $hasMany;
    private $reference;
    
    public function dbToPhp()
    {
        
    }
    
    public function phpToDb()
    {
        
    }
}

?>
