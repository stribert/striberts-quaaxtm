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
 * Indicates that a statement (Topic Maps construct) has a scope.
 * 
 * {@link AssociationImpl}s, {@link OccurrenceImpl}s, {@link NameImpl}s, and 
 * {@link VariantImpl}s are scoped.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
abstract class ScopedImpl extends ConstructImpl implements Scoped {

  private $_bindingTable;
  
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
  public function __construct(
    $id, 
    Construct $parent, 
    Mysql $mysql, 
    array $config, 
    TopicMap $topicMap
  ) {  
    parent::__construct($id, $parent, $mysql, $config, $topicMap);
    $this->_bindingTable = $this->_getBindingTable($this->_className);
  }

  /**
   * Returns the topics which define the scope.
   * An empty array represents the unconstrained scope.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link TopicImpl}s which define the scope.
   */
  public function getScope() {
    $scope = array();
    $query = 'SELECT t1.topic_id FROM ' . $this->_config['table']['theme'] . 
      ' t1 INNER JOIN ' . $this->_bindingTable . ' t2' .
      ' ON t1.scope_id = t2.scope_id' . 
      ' WHERE t2.' . $this->_fkColumn . ' = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $theme = $this->_topicMap->_getConstructByVerifiedId('TopicImpl-' . $result['topic_id']);
      $scope[$theme->getId()] = $theme;
    }
    return array_values($scope);
  }

  /**
   * Adds a topic to the scope.
   *
   * @param TopicImpl The topic which should be added to the scope.
   * @return void
   * @throws {@link ModelConstraintException} If <var>theme</var> does not belong to 
   * 				the parent topic map.
   */
  public function addTheme(Topic $theme) {
    if (!$this->_topicMap->equals($theme->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
    $prevScopeObj = $this->_getScopeObject();
    $scope = $this->getScope();
    $scope[] = $theme;
    $this->_mysql->startTransaction(true);
    $this->_unsetScope($prevScopeObj);
    $scopeObj = new ScopeImpl($this->_mysql, $this->_config, $scope, $this->_topicMap, $this);
    $this->_setScope($scopeObj);
    $this->_updateScopedPropertyHash($scope);
    $this->_mysql->finishTransaction(true);
    if (!$this->_mysql->hasError()) {
      $this->_postSave();
    }
  }

  /**
   * Removes a topic from the scope.
   *
   * @param TopicImpl The topic which should be removed from the scope.
   * @return void
   */
  public function removeTheme(Topic $theme) {
    $prevScopeObj = $this->_getScopeObject();
    $scope = $this->getScope();
    $_scope = $this->_idsToKeys($scope);
    unset($_scope[$theme->_dbId]);
    $scope = array_values($_scope);
    if ($this instanceof IVariant) {
      // check the variant scope superset constraint
      $mergedScope = array_merge($scope, $this->_parent->getScope());
      $nameScopeObj = $this->_parent->_getScopeObject();
      if (!$nameScopeObj->isTrueSubset($mergedScope)) {
        return;
      }
    }
    $this->_mysql->startTransaction(true);
    $this->_unsetScope($prevScopeObj);
    $scopeObj = new ScopeImpl($this->_mysql, $this->_config, $scope, $this->_topicMap, $this);
    $this->_setScope($scopeObj);
    $this->_updateScopedPropertyHash($scope);
    $this->_mysql->finishTransaction(true);
    if (!$this->_mysql->hasError()) {
      $this->_postSave();
    }
  }
  
  /**
   * Sets the construct's scope.
   * 
   * @param ScopeImpl The scope object.
   * @return void
   */
  private function _setScope(ScopeImpl $scopeObj) {
    $query = 'INSERT INTO ' . $this->_bindingTable . 
      ' (scope_id, ' . $this->_fkColumn . ') VALUES' .
      ' (' . $scopeObj->_dbId . ', ' . $this->_dbId . ')';
    $this->_mysql->execute($query);
  }
  
  /**
   * Unsets the construct's scope.
   * 
   * @param ScopeImpl The scope object.
   * @return void
   */
  protected function _unsetScope(ScopeImpl $scopeObj) {
    $query = 'DELETE FROM ' . $this->_bindingTable . 
      ' WHERE ' . $this->_fkColumn . ' = ' . $this->_dbId;
    $this->_mysql->execute($query);
    // clean up: check if scope is still in use
    if (!$scopeObj->isUnconstrained()) {
      $exists = false;
      $tables = $this->_getBindingTables();
      foreach ($tables as $table) {
        $query = 'SELECT COUNT(*) FROM ' . $table . 
          ' WHERE scope_id = ' . $scopeObj->_dbId;
        $mysqlResult = $this->_mysql->execute($query);
        $result = $mysqlResult->fetchArray();
        if ($result[0] > 0) {
          $exists = true;
          break;
        }
      }
      if (!$exists) {
        $query = 'DELETE FROM ' . $this->_config['table']['scope'] . 
          ' WHERE id = ' . $scopeObj->_dbId;
        $this->_mysql->execute($query);
      }
    }
  }
  
  /**
   * Gets the scope object representing the scope of the scoped construct.
   * 
   * @return ScopeImpl
   */
  protected function _getScopeObject() {
    return new ScopeImpl(
      $this->_mysql, 
      $this->_config, 
      $this->getScope(), 
      $this->_topicMap, 
      $this
    );
  }
  
  /**
   * Builds a scope array where the db id is the key.
   * 
   * @param array The scope returned by {@link ScopedImpl::getScope()}.
   * @return array
   */
  private function _idsToKeys(array $scope) {
    $_scope = array();
    foreach ($scope as $theme) {
      $_scope[$theme->_dbId] = $theme;
    }
    return $_scope;
  }
  
  /**
   * Updates the property hash.
   * 
   * @param array The new scope.
   * @return void
   */
  private function _updateScopedPropertyHash(array $scope) {
    switch ($this->_className) {
      case 'NameImpl':
        $hash = $this->_parent->_getNameHash($this->getValue(), $this->getType(), $scope);
        $this->_parent->_updateNameHash($this->_dbId, $hash);
        break;
      case 'OccurrenceImpl':
        $hash = $this->_parent->_getOccurrenceHash($this->getType(), $this->getValue(), 
          $this->getDatatype(), $scope);
        $this->_parent->_updateOccurrenceHash($this->_dbId, $hash);
        break;
      case 'AssociationImpl':
        $hash = $this->_parent->_getAssocHash($this->getType(), $scope, $this->getRoles());
        $this->_parent->_updateAssocHash($this->_dbId, $hash);
        break;
      case 'VariantImpl':
        $hash = $this->_parent->_getVariantHash(
          $this->getValue(), 
          $this->getDatatype(), 
          $scope
        );
        $this->_parent->_updateVariantHash($this->_dbId, $hash);
    }
  }
  
  /**
   * Gets the scope binding tables.
   * 
   * @return array
   */
  private function _getBindingTables() {
    return array(
      $this->_config['table']['topicname_scope'], 
      $this->_config['table']['occurrence_scope'], 
      $this->_config['table']['association_scope'], 
      $this->_config['table']['variant_scope']
    );
  }
  
  /**
   * Gets the scope binding table.
   * 
   * @param string The scoped construct's class name.
   * @return string The table name.
   */
  private function _getBindingTable($className) {
    switch ($className) {
      case 'NameImpl':
        return $this->_config['table']['topicname_scope'];
      case 'OccurrenceImpl':
        return $this->_config['table']['occurrence_scope'];
      case 'AssociationImpl':
        return $this->_config['table']['association_scope'];
      case 'VariantImpl':
        return $this->_config['table']['variant_scope'];
    }
  }
}
?>