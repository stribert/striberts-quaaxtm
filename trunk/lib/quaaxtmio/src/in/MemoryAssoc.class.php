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
 * Represents an association item.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MemoryAssoc extends MemoryScoped {
  
  private $roles,
          $type;
          
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct() {
    parent::__construct();
    $this->roles = array();
    $this->type = null;
  }
  
  /**
   * Sets the association type.
   * 
   * @param Topic The association type.
   * @return void
   */
  public function setType(Topic $type) {
    $this->type = $type;
  }
  
  /**
   * Gets the association type.
   * 
   * @return Topic The association type.
   */
  public function getType() {
    return $this->type;
  }
  
  /**
   * Adds an association role.
   * 
   * @param MemoryRole The association role.
   * @return void
   */
  public function addRole(MemoryRole $role) {
    $this->roles[] = $role;
  }
  
  /**
   * Gets the association roles.
   * 
   * @return array An array containing {@link MemoryRole}s.
   */
  public function getRoles() {
    return $this->roles;
  }
}