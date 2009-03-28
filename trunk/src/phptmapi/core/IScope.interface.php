<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 2.1 of the License, or (at your option) any later version.
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
 * Represents a set of {@link Topic}s (themes) which define the scope.
 * This interface is not meant to be used outside of QuaaxTM.
 * 
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
interface IScope {
  
  /**
   * Checks if all themes of this scope are contained in the given themes array.
   * 
   * @param array An array containing topics.
   * @return boolean
   */
  public function containsAll(array $themes);
  
  /**
   * Checks if this scope represents the unconstrained scope (ucs).
   * 
   * @return boolean
   */
  public function isUnconstrained();
  
  /**
   * Returns all themes (topics) of this scope.
   * 
   * @return array An array containing topics.
   */
  public function getThemes();
}
?>
