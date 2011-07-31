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

/**
 * Represents a Topic Maps construct.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MemoryConstruct
{
  /**
   * The reifier.
   * 
   * @var Topic
   */
  private $_reifier;
  
  /**
   * The item identifiers.
   * 
   * @var array
   */
  private $_iids;
          
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct()
  {
    $this->_reifier = null;
    $this->_iids = array();
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct()
  {
    unset($this->_reifier);
    unset($this->_iids);
  }
  
  /**
   * Sets the construct's reifier.
   * 
   * @param Topic The reifier.
   * @return void
   */
  public function setReifier(Topic $reifier)
  {
    $this->_reifier = $reifier;
  }
  
  /**
   * Gets the construct's reifier.
   * 
   * @return Topic The reifier.
   */
  public function getReifier()
  {
    return $this->_reifier;
  }
  
  /**
   * Adds an item identifier to the construct.
   * 
   * @param string The item identifier.
   * @return void
   */
  public function addItemIdentifier($iid)
  {
    $this->_iids[$iid] = $iid;
  }
  
  /**
   * Gets the contruct's item identifiers.
   * 
   * @return array An array containing the item identifiers.
   */
  public function getItemIdentifiers()
  {
    return array_values($this->_iids);
  }
}
?>