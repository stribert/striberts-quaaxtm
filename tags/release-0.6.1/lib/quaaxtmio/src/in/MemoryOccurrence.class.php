<?php
/*
 * QuaaxTMIO is the Topic Maps syntaxes serializer/deserializer library for QuaaxTM.
 * 
 * Copyright (C) 2010 Johannes Schmidt <joschmidt@users.sourceforge.net>
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

require_once('MemoryScoped.class.php');

/**
 * Represents an occurrence.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MemoryOccurrence extends MemoryScoped {
  
  private $type,
          $value,
          $datatype;

  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct() {
    parent::__construct();
    $this->type = 
    $this->value = 
    $this->datatype = null;
  }
  
  /**
   * Sets the occurrence type.
   * 
   * @param Topic The occurrence type.
   * @return void
   */
  public function setType(Topic $type) {
    $this->type = $type;
  }
  
  /**
   * Gets the occurrence type.
   * 
   * @return Topic The occurrence type.
   */
  public function getType() {
    return $this->type;
  }
  
  /**
   * Sets the occurrence value.
   * 
   * @param string The value.
   * @param string The URI identifying the datatype of the value.
   * @return void
   */
  public function setValue($value, $datatype) {
    $this->value = $value;
    $this->datatype = $datatype;
  }
  
  /**
   * Gets the occurrence value.
   * 
   * @return string The occurrence value.
   */
  public function getValue() {
    return $this->value;
  }
  
  /**
   * Gets the URI identifying the datatype of the value.
   * 
   * @return string The URI identifying the datatype of the value.
   */
  public function getDatatype() {
    return $this->datatype;
  }
}
?>