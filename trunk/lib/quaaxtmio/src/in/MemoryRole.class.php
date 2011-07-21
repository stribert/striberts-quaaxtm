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

require_once('MemoryConstruct.class.php');

/**
 * Represents an association role.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MemoryRole extends MemoryConstruct
{
  private $_type,
          $_player;
  
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
    $this->_type = 
    $this->_player = null;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct()
  {
    unset($this->_type);
    unset($this->_player);
  }
  
  /**
   * Sets the association role type.
   * 
   * @param Topic The association role type.
   * @return void
   */
  public function setType(Topic $type)
  {
    $this->_type = $type;
  }
  
  /**
   * Gets the association role type.
   * 
   * @return Topic The association role type.
   */
  public function getType()
  {
    return $this->_type;
  }
  
  /**
   * Sets the association role player.
   * 
   * @param Topic The association role player.
   * @return void
   */
  public function setPlayer(Topic $player)
  {
    $this->_player = $player;
  }
  
  /**
   * Gets the association role player.
   * 
   * @return Topic The association role player.
   */
  public function getPlayer()
  {
    return $this->_player;
  }
}
?>