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
 * Represents a topic reference.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
interface ReferenceInterface
{
  /**
   * The reference type "item identifier".
   */
  const ITEM_IDENTIFIER = 1;
  
  /**
   * The reference type "subject identifier".
   */
  const SUBJECT_IDENTIFIER = 2;
  
  /**
   * The reference type "subject locator".
   */
  const SUBJECT_LOCATOR = 3;

  /**
   * Returns the reference. The reference may be a valid URI.
   *
   * @return string The reference.
   */
  public function getReference();

  /**
   * Returns the type of this reference.
   *
   * @return int The type of this reference: <code>Item Identifier</code>, 
   * 		<code>Subject Identifier</code>, or <code>Subject Locator</code>.
   */
  public function getType();
}
?>
