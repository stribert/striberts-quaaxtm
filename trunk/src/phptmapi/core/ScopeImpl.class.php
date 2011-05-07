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
 * An implementation of {@link IScope}.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class ScopeImpl implements IScope {

  /**
   * The id (primary key) in table qtm_scope.
   * 
   * @var int
   */
  public $dbId;

  private $mysql,
          $config,
          $themes,
          $themesIds,
          $currentTopicMap,
          $currentConstruct;

  /**
   * Constructor.
   * 
   * @param Mysql The Mysql object.
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
  ) {  
    $this->mysql = $mysql;
    $this->config = $config;
    $this->currentTopicMap = $currentTopicMap;
    $this->currentConstruct = $currentConstruct;
    if (count($themes) > 0) {
      $this->createSet($themes);
      $scopeId = $this->exists();
      if ($scopeId) {
        $this->dbId = $scopeId;
      } else {
        $this->create();
      }
    } else {// unconstrained scope
      $this->themesIds = 
      $this->themes = array();
      $this->dbId = $this->getUcsId();
    }
  }
  
  /**
   * @see IScope::isTrueSubset()
   */
  public function isTrueSubset(array $themes) {
    $set = array();
    foreach ($themes as $theme) {
      if ($theme instanceof Topic) {
        $set[$theme->getDbId()] = $theme;
      }
    }
    $otherThemesIds = array_keys($set);
    $intersect = array_intersect($this->themesIds, $otherThemesIds);
    return count($intersect) == count($this->themesIds) && 
      count($otherThemesIds) > count($this->themesIds) 
      ? true 
      : false;
  }
  
  /**
   * @see IScope::isUnconstrained()
   */
  public function isUnconstrained() {
    return count($this->themesIds) > 0 ? false : true;
  }
  
  /**
   * @see IScope::getThemes()
   */
  public function getThemes() {
    return $this->themes;
  }
  
  /**
   * @see IScope::hasTheme()
   */
  public function hasTheme(Topic $theme) {
    return in_array($theme->getDbId(), $this->themesIds);
  }
  
  /**
   * Checks if a scope exists.
   * 
   * @return int|false <var>False</var> if scope does not exist, the scope id otherwise.
   */
  private function exists() {
    $idsImploded = implode(',', $this->themesIds);
    $query = 'SELECT scope_id FROM ' . $this->config['table']['theme'] . 
      ' WHERE topic_id IN (' . $idsImploded . ')' .
      ' GROUP BY scope_id';
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      while ($result = $mysqlResult->fetch()) {
        $_themesIds = array();
        $query = 'SELECT topic_id FROM ' . $this->config['table']['theme'] . 
          ' WHERE scope_id = ' . $result['scope_id'];
        $_mysqlResult = $this->mysql->execute($query);
        while ($_result = $_mysqlResult->fetch()) {
          $_themesIds[] = (int) $_result['topic_id'];
        }
        $diff = array_diff($this->themesIds, $_themesIds);
        $diffReverse = array_diff($_themesIds, $this->themesIds);
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
  private function createSet(array $scope) {
    $set = array();
    foreach ($scope as $theme) {
      if ($theme instanceof Topic) {
        $set[$theme->getDbId()] = $theme;
      }
    }
    $this->themesIds = array_keys($set);
    $this->themes = array_values($set);
  }
  
  /**
   * Creates a new scope.
   * 
   * @return void
   */
  private function create() {
    $this->mysql->startTransaction();
    $query = 'INSERT INTO ' . $this->config['table']['scope'] . ' (id) VALUES (NULL)';
    $mysqlResult = $this->mysql->execute($query);
    $lastScopeId = $mysqlResult->getLastId();
    foreach ($this->themesIds as $topicId) {
      $query = 'INSERT INTO ' . $this->config['table']['theme'] . 
        ' (scope_id, topic_id) VALUES' .
        ' (' . $lastScopeId . ', ' . $topicId . ')';
      $this->mysql->execute($query);
    }
    $this->dbId = $lastScopeId;
    $this->mysql->finishTransaction();
  }
  
  /**
   * Returns the id of the scope that represents the unconstrained scope (ucs).
   * 
   * @return int The scope id.
   */
  private function getUcsId() {
    $query = 'SELECT t1.id AS scope_id FROM ' . $this->config['table']['scope'] . ' t1 ' .
      'LEFT JOIN ' . $this->config['table']['theme'] . ' t2 ' .
      'ON t2.scope_id = t1.id ' .
      'WHERE t2.scope_id IS NULL';
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return (int) $result['scope_id'];
    } else {
      $query = 'INSERT INTO ' . $this->config['table']['scope'] . ' (id) VALUES (NULL)';
      $mysqlResult = $this->mysql->execute($query);
      return (int) $mysqlResult->getLastId();
    }
  }
}
?>