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
 * Indicates that a statement (Topic Maps construct) has a scope.
 * 
 * {@link AssociationImpl}s, {@link OccurrenceImpl}s, {@link NameImpl}s, and 
 * {@link VariantImpl}s are scoped.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
abstract class ScopedImpl extends ConstructImpl implements Scoped {

  private $bindingTable;
  
  /**
   * Constructor.
   * 
   * @param string The Topic Maps construct id.
   * @param ConstructImpl The parent Topic Maps construct.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicMapImpl The containing topic map.
   * @return void
   */
  public function __construct($id, Construct $parent, Mysql $mysql, array $config, 
    TopicMap $topicMap) {
    parent::__construct($id, $parent, $mysql, $config, $topicMap);
    $this->bindingTable = $this->getBindingTable();
  }

  /**
   * Returns the topics which define the scope.
   * An empty array represents the unconstrained scope.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing {@link TopicImpl}s which define the scope.
   */
  public function getScope() {
    $scope = array();
    $query = 'SELECT t1.topic_id FROM ' . $this->config['table']['theme'] . 
      ' t1 INNER JOIN ' . $this->bindingTable . ' t2' .
      ' ON t1.scope_id = t2.scope_id' . 
      ' WHERE t2.' . $this->fkColumn . ' = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $theme = $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
        $result['topic_id']);
      $scope[] = $theme;
    }
    return $scope;
  }

  /**
   * Adds a topic to the scope.
   *
   * @param TopicImpl The topic which should be added to the scope.
   * @return void
   */
  public function addTheme(Topic $theme) {
    $prevScopeObj = $this->getScopeObject();
    $scope = $this->getScope();
    $scope[] = $theme;
    $this->mysql->startTransaction(true);
    $this->unsetScope($prevScopeObj);
    $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope);
    $this->setScope($scopeObj);
    $this->updateScopedPropertyHash($scope);
    $this->mysql->finishTransaction(true);
  }

  /**
   * Removes a topic from the scope.
   *
   * @param TopicImpl The topic which should be removed from the scope.
   * @return void
   */
  public function removeTheme(Topic $theme) {
    $prevScopeObj = $this->getScopeObject();
    $scope = $this->getScope();
    $_scope = $this->idsToKeys($scope);
    unset($_scope[$theme->dbId]);
    $scope = array_values($_scope);
    if ($this instanceof Variant) {
      // check the variant scope superset constraint
      $mergedScope = array_merge($scope, $this->parent->getScope());
      $nameScopeObj = $this->parent->getScopeObject();
      if (!$nameScopeObj->isTrueSubset($mergedScope)) return;
    }
    $this->mysql->startTransaction(true);
    $this->unsetScope($prevScopeObj);
    $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope);
    $this->setScope($scopeObj);
    $this->updateScopedPropertyHash($scope);
    $this->mysql->finishTransaction(true);
  }
  
  /**
   * Sets the construct's scope.
   * 
   * @param IScope The scope object.
   * @return void
   */
  protected function setScope(IScope $scopeObj) {
    $query = 'INSERT INTO ' . $this->bindingTable . 
      ' (scope_id, ' . $this->fkColumn . ') VALUES' .
      ' (' . $scopeObj->dbId . ', ' . $this->dbId . ')';
    $this->mysql->execute($query);
  }
  
  /**
   * Unsets the construct's scope.
   * 
   * @param IScope The scope object.
   * @return void
   */
  protected function unsetScope(IScope $scopeObj) {
    $query = 'DELETE FROM ' . $this->bindingTable . 
      ' WHERE ' . $this->fkColumn . ' = ' . $this->dbId;
    $this->mysql->execute($query);
    // clean up: check if scope is still in use
    if (!$scopeObj->isUnconstrained()) {
      $exists = false;
      $tables = $this->getBindingTables();
      foreach ($tables as $table) {
        $query = 'SELECT COUNT(*) FROM ' . $table . 
          ' WHERE scope_id = ' . $scopeObj->dbId;
        $mysqlResult = $this->mysql->execute($query);
        $result = $mysqlResult->fetchArray();
        if ($result[0] > 0) {
          $exists = true;
          break;
        }
      }
      if (!$exists) {
        $query = 'DELETE FROM ' . $this->config['table']['scope'] . 
          ' WHERE id = ' . $scopeObj->dbId;
        $this->mysql->execute($query);
      }
    }
  }
  
  /**
   * Gets the scope object representing the scope of the scoped construct.
   * 
   * @return ScopeImpl
   */
  protected function getScopeObject() {
    return new ScopeImpl($this->mysql, $this->config, $this->getScope());
  }
  
  /**
   * Gets the scope binding tables.
   * 
   * @return array
   */
  private function getBindingTables() {
    return array(
                  $this->config['table']['topicname_scope'], 
                  $this->config['table']['occurrence_scope'], 
                  $this->config['table']['association_scope'], 
                  $this->config['table']['variant_scope']
                );
  }
  
  /**
   * Gets the scope binding table.
   * 
   * @return string The table name.
   */
  private function getBindingTable() {
    switch ($this->className) {
      case TopicImpl::NAME_CLASS_NAME:
        return $this->config['table']['topicname_scope'];
      case TopicImpl::OCC_CLASS_NAME:
        return $this->config['table']['occurrence_scope'];
      case TopicMapImpl::ASSOC_CLASS_NAME:
        return $this->config['table']['association_scope'];
      case NameImpl::VARIANT_CLASS_NAME:
        return $this->config['table']['variant_scope'];
      default:
        return null;
    }
  }
  
  /**
   * Builds a scope array where the db id is the key.
   * 
   * @param array The scope returned by {@link ScopedImpl::getScope()}.
   * @return array
   */
  protected function idsToKeys(array $scope) {
    $_scope = array();
    foreach ($scope as $theme) {
      $_scope[$theme->dbId] = $theme;
    }
    return $_scope;
  }
  
  /**
   * Updates the property hash.
   * 
   * @param array The new scope.
   * @return void
   */
  protected function updateScopedPropertyHash(array $scope) {
    switch ($this->className) {
      case TopicImpl::NAME_CLASS_NAME:
        $hash = $this->parent->getNameHash($this->getValue(), $this->getType(), $scope);
        $this->parent->updateNameHash($this->dbId, $hash);
        break;
      case TopicImpl::OCC_CLASS_NAME:
        $hash = $this->parent->getOccurrenceHash($this->getType(), $this->getValue(), 
          $this->getDatatype(), $scope);
        $this->parent->updateOccurrenceHash($this->dbId, $hash);
        break;
      case TopicMapImpl::ASSOC_CLASS_NAME:
        $hash = $this->parent->getAssocHash($this->getType(), $scope, $this->getRoles());
        $this->parent->updateAssocHash($this->dbId, $hash);
        break;
      case NameImpl::VARIANT_CLASS_NAME:
        $hash = $this->parent->getVariantHash($this->getValue(), 
          $this->getDatatype(), $scope);
        $this->parent->updateVariantHash($this->dbId, $hash);
    }
  }
}
?>