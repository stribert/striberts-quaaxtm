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
 * Base class for all indices.
 *
 * @package index
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
abstract class IndexImpl implements Index {
  
  protected $mysql,
            $config,
            $topicMap,
            $tmDbId;
  
  /**
   * Constructor.
   * 
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicMap The topic map the index operates on.
   * @param int The topic map's database id.
   * @return void
   */
  public function __construct(Mysql $mysql, array $config, TopicMap $topicMap, $tmDbId) {
    $this->mysql = $mysql;
    $this->config = $config;
    $this->topicMap = $topicMap;
    $this->tmDbId = $tmDbId;
  }
  
  /**
   * Opens the index.
   * This method must be invoked before using any other method (aside from
   * {@link isOpen()}) exported by this interface or derived interfaces.
   * 
   * @return void
   */
  public function open() {
    return;
  }

  /**
   * Closes the index.
   * 
   * @return void
   */
  public function close() {
    return;
  }

  /**
   * Indicates if the index is open.
   * 
   * @return boolean <var>true</var> if index is already opened, <var>false</var> otherwise.
   */
  public function isOpen() {
    return true;
  }

  /**
   * Indicates whether the index is updated automatically.
   * If the value is <var>true</var>, then the index is automatically kept
   * synchronized with the topic map as values are changed.
   * If the value is <var>false</var>, then the {@link Index::reindex()}
   * method must be called to resynchronize the index with the topic map
   * after values are changed.
   * 
   * @return boolean <var>true</var> if index is updated automatically, 
   *        <var>false</var> otherwise.
   */
  public function isAutoUpdated() {
    return true;
  }

  /**
   * Synchronizes the index with data in the topic map.
   *
   * @return void
   */
  public function reindex() {
    return;
  }
  
  /**
   * Returns the database id (primary key).
   * 
   * @param Construct The Topic Maps construct.
   * @return int The database id (primary key).
   */
  protected function getConstructDbId(Construct $construct) {
    $constituents = explode('-', $construct->getId());
    return (int) $constituents[1];  
  }
}
?>