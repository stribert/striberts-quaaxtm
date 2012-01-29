<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2012 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * Extends PHP SPL's ArrayObject.
 * 
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class ArrayObjectUtils extends ArrayObject
{
  /**
   * Constructor.
   * 
   * @param mixed The input of type <var>array</var> or <var>Object</var>.
   * @return void
   */
  public function __construct($input)
  {
    parent::__construct($input);
    $this->iterator = $this->getIterator();
  }
  
  /**
   * Provides iterative access to entries; e.g. usable in <var>while loops</var>.
   * 
   * @return array|null
   */
  public function fetch()
  {
    if ($this->iterator->valid()) {
      $currentEntry = $this->iterator->current();
      $this->iterator->next();
      return $currentEntry;
    }
    return null;
  }
}
?>