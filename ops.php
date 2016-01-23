<?php

namespace com\bazaar\core\mongom;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of util
 *
 * @author gourav sarkar
 */
class ops {
    //put your code here
    private $data;
    
    public function __construct($ops,$data)
    {
        $this->data=['$'.$ops => $data];
        
    }
    
    public function getQuery()
    {
        return $this->data;
    }
}

?>
