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
 * Represents a set of {@link TopicImpl}s (themes) which define the scope.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class ScopeImpl
{
  /**
   * The scope id in MySQL table "qtm_scope" (the primary key).
   * 
   * @var int
   */
  public $dbId;

  /**
   * The MySQL wrapper.
   * 
   * @var Mysql
   */
  private $_mysql;
  
  /**
   * The configuration data.
   * 
   * @var array
   */
  private $_config;
  
  /**
   * The themes (topics representing a scope).
   * 
   * @var array
   */
  private $_themes;
  
  /**
   * The themes ids in MySQL database.
   * 
   * @var int
   */
  private $_themesIds;
  
  /**
   * The current topic map.
   * 
   * @var TopicMapImpl
   */
  private $_currentTopicMap;
  
  /**
   * The current scoped construct.
   * 
   * @var AssociationImpl|NameImpl|OccurrenceImpl|VariantImpl
   */
  private $_currentConstruct;

  /**
   * Constructor.
   * 
   * @param Mysql The MySQL wrapper.
   * @param array The configuration data.
   * @param array An array containing {@link TopicImpl}s (the themes).
   * @param TopicMapImpl The current topic map.
   * @return void
   */
  public function __construct(
    Mysql $mysql, 
    array $config, 
    array $themes, 
    TopicMap $currentTopicMap, 
    Construct $currentConstruct
    )
  {  
    $this->_mysql = $mysql;
    $this->_config = $config;
    $this->_currentTopicMap = $currentTopicMap;
    $this->_currentConstruct = $currentConstruct;
    if (count($themes) > 0) {
      $this->_createSet($themes);
      $scopeId = $this->_exists();
      if ($scopeId) {
        $this->_dbId = $scopeId;
      } else {
        $this->_create();
      }
    } else {// unconstrained scope
      $this->_themesIds = 
      $this->_themes = array();
      $this->_dbId = $this->_getUcsId();
    }
  }
  
  /**
   * Checks if scope is a true subset of given scope (themes).
   * 
   * @param array An array containing topics.
   * @return boolean
   */
  public function isTrueSubset(array $themes)
  {
    $set = array();
    foreach ($themes as $theme) {
      if ($theme instanceof Topic) {
        $set[$theme->getDbId()] = $theme;
      }
    }
    $otherThemesIds = array_keys($set);
    $intersect = array_intersect($this->_themesIds, $otherThemesIds);
    return count($intersect) == count($this->_themesIds) && 
      count($otherThemesIds) > count($this->_themesIds) 
      ? true 
      : false;
  }
  
  /**
   * Checks if this scope represents the unconstrained scope (UCS).
   * 
   * @return boolean
   */
  public function isUnconstrained()
  {
    return count($this->_themesIds) > 0 ? false : true;
  }
  
  /**
   * Checks if a scope exists.
   * 
   * @return int|false <var>False</var> if scope does not exist, the scope id otherwise.
   */
  private function _exists()
  {
    $idsImploded = implode(',', $this->_themesIds);
    $query = 'SELECT scope_id FROM ' . $this->_config['table']['theme'] . 
      ' WHERE topic_id IN (' . $idsImploded . ')' .
      ' GROUP BY scope_id';
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    if ($numRows > 0) {
      while ($result = $mysqlResult->fetch()) {
        $_themesIds = array();
        $query = 'SELECT topic_id FROM ' . $this->_config['table']['theme'] . 
          ' WHERE scope_id = ' . $result['scope_id'];
        $_mysqlResult = $this->_mysql->execute($query);
        while ($_result = $_mysqlResult->fetch()) {
          $_themesIds[] = (int) $_result['topic_id'];
        }
        $diff = array_diff($this->_themesIds, $_themesIds);
        $diffReverse = array_diff($_themesIds, $this->_themesIds);
        if (empty($diff) && empty($diffReverse)) {
          return (int) $result['scope_id'];
        }
      }
    }
    return false;
  }
  
  /**
   * Generates a true set from the provided scope (themes). 
   * Sets the private attributes $themesIds and $themes.
   * 
   * @param array An array containing topics (the themes).
   */
  private function _createSet(array $scope)
  {
    $set = array();
    foreach ($scope as $theme) {
      if ($theme instanceof Topic) {
        $set[$theme->getDbId()] = $theme;
      }
    }
    $this->_themesIds = array_keys($set);
    $this->_themes = array_values($set);
  }
  
  /**
   * Creates a new scope.
   * 
   * @return void
   */
  private function _create()
  {
    $this->_mysql->startTransaction();
    $query = 'INSERT INTO ' . $this->_config['table']['scope'] . ' (id) VALUES (NULL)';
    $mysqlResult = $this->_mysql->execute($query);
    $lastScopeId = $mysqlResult->getLastId();
    foreach ($this->_themesIds as $topicId) {
      $query = 'INSERT INTO ' . $this->_config['table']['theme'] . 
        ' (scope_id, topic_id) VALUES' .
        ' (' . $lastScopeId . ', ' . $topicId . ')';
      $this->_mysql->execute($query);
    }
    $this->_dbId = $lastScopeId;
    $this->_mysql->finishTransaction();
  }
  
  /**
   * Returns the id of the scope that represents the unconstrained scope (ucs).
   * 
   * @return int The scope id.
   */
  private function _getUcsId()
  {
    $query = 'SELECT t1.id AS scope_id FROM ' . $this->_config['table']['scope'] . ' t1 ' .
      'LEFT JOIN ' . $this->_config['table']['theme'] . ' t2 ' .
      'ON t2.scope_id = t1.id ' .
      'WHERE t2.scope_id IS NULL';
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    if ($numRows > 0) {
      $result = $mysqlResult->fetch();
      return (int) $result['scope_id'];
    }
    $query = 'INSERT INTO ' . $this->_config['table']['scope'] . ' (id) VALUES (NULL)';
    $mysqlResult = $this->_mysql->execute($query);
    return (int) $mysqlResult->getLastId();
  }
}
?>