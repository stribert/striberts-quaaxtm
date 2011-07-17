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
 * Represents a topic name.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MemoryName extends MemoryScoped {
  
  private $_type,
          $_value,
          $_variants;

  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct() {
    parent::__construct();
    $this->_type = 
    $this->_value = null;
    $this->_variants = array();
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct() {
    unset($this->_type); 
    unset($this->_value);
    unset($this->_variants);
  }
  
  /**
   * Sets the topic name type.
   * 
   * @param Topic The topic name type.
   * @return void
   */
  public function setType(Topic $type) {
    $this->_type = $type;
  }
  
  /**
   * Gets the topic name type.
   * 
   * @return Topic The topic name type.
   */
  public function getType() {
    return $this->_type;
  }
  
  /**
   * Sets the topic name value.
   * 
   * @param string The name value.
   * @return void
   */
  public function setValue($value) {
    $this->_value = $value;
  }
  
  /**
   * Gets the topic name value.
   * 
   * @return string The name value.
   */
  public function getValue() {
    return $this->_value;
  }
  
  /**
   * Adds a variant to this topic name.
   * 
   * @param MemoryVariant The topic name variant.
   * @return void
   */
  public function addVariant(MemoryVariant $variant) {
    $this->_variants[] = $variant;
  }
  
  /**
   * Gets the topic name variants
   * 
   * @return array An array containing {@link MemoryVariant}s.
   */
  public function getVariants() {
    return $this->_variants;
  }
}
?>