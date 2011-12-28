<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2009 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * Provides utilities for topic names, variants, and occurrences.
 *
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class CharacteristicUtils
{
  /**
   * The UTF-8 character encoding representation.
   */
  const UTF8 = 'UTF-8';
  
  /**
   * The ISO/IEC 8859-1 character encoding representation.
   */
  const ISO88591 = 'ISO-8859-1';
      
  /**
   * Constructor.
   * 
   * @return void
   */
  private function __construct(){}
        
  /**
   * Canonicalizes a value for SQL statements.
   * 
   * @param string The value.
   * @param mysqli The current MySQL connection.
   * @param string The encoding. Default UTF-8.
   * @param boolean Create HTML entities or not. Default false.
   * @return string
   * @static
   */
  public static function canonicalize(
    $value, 
    mysqli $connection, 
    $encoding=self::UTF8, 
    $entities=false
    )
  {
    $value = mysqli_real_escape_string($connection, $value);
    return $entities ? htmlentities($value, ENT_NOQUOTES, $encoding) : $value;
  }
}
?>