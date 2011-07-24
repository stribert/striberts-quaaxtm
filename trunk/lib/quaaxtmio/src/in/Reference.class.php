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

require_once('Reference.interface.php');

/**
 * Represents a topic reference.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class Reference implements ReferenceInterface
{
  private $_ref,
          $_type;
  
  /**
   * Constructor.
   * 
   * @param string The topic's reference.
   * @param string The reference type. Default <code>ITEM_IDENTIFIER</code>.
   * @return void
   */
  public function __construct($ref, $type = self::ITEM_IDENTIFIER)
  {    
    $this->_ref = $ref;
    $this->_type = $type;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct()
  {
    unset($this->_ref);
    unset($this->_type);
  }
 
  /**
   * (non-PHPDoc)
   * @see src/in/ReferenceInterface#getReference()
   */
  public function getReference()
  {
    return $this->_ref;
  }

  /**
   * (non-PHPDoc)
   * @see src/in/ReferenceInterface#getType()
   */
  public function getType()
  {
    return $this->_type;
  }
}
?>