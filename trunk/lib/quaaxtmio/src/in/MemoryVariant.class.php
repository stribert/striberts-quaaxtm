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
 * Represents a topic name variant.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MemoryVariant extends MemoryScoped
{
  /**
   * The name variant value.
   * 
   * @var string
   */
  private $_value;
  
  /**
   * The value's XSD datatype identifier (a URI).
   * E.g. "http://www.w3.org/2001/XMLSchema#string" indicates a string value.
   * 
   * @var string
   */
  private $_datatype;
          
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
    $this->_value = 
    $this->_datatype = null;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct()
  {
    unset($this->_value);
    unset($this->_datatype);
    parent::__destruct();
  }
  
  /**
   * Sets the variant value.
   * 
   * @param string The value.
   * @param string The URI identifying the datatype of the value.
   * @return void
   */
  public function setValue($value, $datatype)
  {
    $this->_value = $value;
    $this->_datatype = $datatype;
  }
  
  /**
   * Gets the variant value.
   * 
   * @return string The variant value.
   */
  public function getValue()
  {
    return $this->_value;
  }
  
  /**
   * Gets the URI identifying the datatype of the value.
   * 
   * @return string The URI identifying the datatype of the value.
   */
  public function getDatatype()
  {
    return $this->_datatype;
  }
}
?>