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
 * Wraps access to MySQL query results via mysqli.
 * 
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MysqlResult
{	
  private $_result,
          $_connection;
	
  /**
   * Constructor.
   * 
   * @param resource The MySQL result resource.
   * @param resource The MySQL connection resource.
   * @return void
   */
  public function __construct($result, $connection)
  {
    $this->_result = $result;
    $this->_connection = $connection;
  }
	
  /**
   * Fetches a result as associative array from MySQL.
   * 
   * @return array|null
   */   
  public function fetch()
  {
    if ($array = mysqli_fetch_assoc($this->_result)) {
      return $array;
    } else {
      return null;
    }
  }
	
  /**
   * Fetches a result as numeric array from MySQL.
   * 
   * @return array|null
   */   
  public function fetchArray()
  {
    if ($array = mysqli_fetch_array($this->_result)) {
      return $array;
    } else {
      return null;
    }
  }

  /**
   * Gets the affected rows of a query.
   * 
   * @return int
   */      
  public function getNumRows()
  {
    return (int) mysqli_num_rows($this->_result);
  }
	
  /**
   * Returns the last inserted/updated record id.
   * 
   * @return int
   */
  public function getLastId()
  {
    return (int) mysqli_insert_id($this->_connection);
  }
}
?>