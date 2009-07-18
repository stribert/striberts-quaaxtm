<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2009 Johannes Schmidt <joschmidt@users.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public License along with this 
 * library; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, 
 * Boston, MA 02111-1307 USA
 */

/**
 * Holds scalar properties of a Topic Maps construct.
 *
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class PropertyUtils {

    private $typeId,
            $value,
            $datatype;
            
    /**
     * Constructor.
     * 
     * @return void
     */
    public function __construct() {
      $this->typeId = $this->value = $this->datatype = null;
    }
    
    /**
     * Sets the type id.
     * 
     * @return PropertyUtils
     */
    public function setTypeId($typeId) {
      $this->typeId = $typeId;
      return $this;
    }
    
    /**
     * Returns the type id.
     * 
     * @return int
     */
    public function getTypeId() {
      return (int) $this->typeId;
    }
    
    /**
     * Sets the value.
     * 
     * @return PropertyUtils
     */
    public function setValue($value) {
      $this->value = $value;
      return $this;
    }
    
    /**
     * Gets the value.
     * 
     * @return string
     */
    public function getValue() {
      return (string) $this->value;
    }
    
    /**
     * Sets the data type.
     * 
     * @return PropertyUtils
     */
    public function setDatatype($datatype) {
      $this->datatype = $datatype;
      return $this;
    }
    
    /**
     * Gets the data type.
     * 
     * @return string
     */
    public function getDatatype() {
      return (string) $this->datatype;
    }
}
?>