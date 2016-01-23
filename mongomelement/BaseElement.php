<?php
namespace com\bazaar\core\mongom\MongOMElement;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MongOMElement
 *
 * @author gourav sarkar
 */
abstract class BaseElement {
    //put your code here
    const MOM_IDF_CORE="mom";
    
    public function dbToPhp();
    public function phpToDb();
}

?>
