<?php
namespace com\bazaar\core\mongom;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author gourav sarkar
 */
interface QueryInterface {
    public function getQuery();
    public function setMapper(MongOM $map);
}

?>
