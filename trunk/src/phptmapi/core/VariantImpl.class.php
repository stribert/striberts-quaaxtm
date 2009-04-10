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
 * Represents a variant item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-variant}.
 * 
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns the 
 * {@link NameImpl}to which this Variant belongs.
 * Inherited method <var>getScope()</var> from {@link ScopedImpl} returns the union of 
 * its own scope and the parent's scope.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class VariantImpl extends ScopedImpl implements Variant {
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param NameImpl The parent name.
   * @param TopicMapImpl The containing topic map.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, Name $parent, 
    TopicMap $topicMap) {
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
  }
  
  /**
   * Returns the string representation of the value.
   * 
   * @return string The string representation of the value (never <var>null</var>).
   */
  public function getValue() {
    $query = 'SELECT value FROM ' . $this->config['table']['variant'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $result['value'];
  }

  /**
   * Returns the URI identifying the datatype of the value.
   * I.e. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   *
   * @return string The datatype of this construct (never <var>null</var>).
   */
  public function getDatatype() {
    $query = 'SELECT datatype FROM ' . $this->config['table']['variant'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $result['datatype'];
  }

  /**
   * Sets the value and the datatype.
   *
   * @param string The string representation of the value; must not be <var>null</var>.
   * @param string The URI identifying the datatype of the value; must not be <var>null</var>. 
   *        I.e. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>value</var> or <var>datatype</var> 
   *        is <var>null</var>.
   */
  public function setValue($value, $datatype) {
    if (!is_null($value) && !is_null($datatype)) {
      $value = CharacteristicUtils::canonicalize($value);
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['variant'] . 
        ' SET value = "' . $value . '", ' . 
        'datatype = "' . $datatype . '" ' . 
        'WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->parent->getVariantHash($value, $datatype, $this->getScope());
      $this->parent->updateVariantHash($this->dbId, $hash);
      $this->mysql->finishTransaction();
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        ConstructImpl::VALUE_DATATYPE_NULL_ERR_MSG);
    }
  }
  
  /**
   * Returns the reifier of this variant.
   * 
   * @return TopicImpl The topic that reifies this variant or
   *        <var>null</var> if this variant is not reified.
   */
  public function getReifier() {
    return $this->_getReifier();
  }

  /**
   * Sets the reifier of this variant.
   * The specified <var>reifier</var> MUST NOT reify another information item.
   *
   * @param TopicImpl|null The topic that should reify this variant or null
   *        if an existing reifier should be removed.
   * @return void
   * @throws {@link ModelConstraintException} If the specified <var>reifier</var> 
   *        reifies another construct.
   */
  public function setReifier($reifier) {
    $this->_setReifier($reifier);
  }
  
  /**
   * Deletes this variant.
   * 
   * @override
   * @return void
   */
  public function remove() {
    $query = 'DELETE FROM ' . $this->config['table']['variant'] . 
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      $this->id = null;
      $this->dbId = null;
    }
  }
  
  /**
   * Removes a topic from the scope.
   *
   * @param TopicImpl The topic which should be removed from the scope.
   * @return void
   */
  public function removeTheme(Topic $theme) {
    $scope = $this->getScope();
    $_scope = $this->idsToKeys($scope);
    unset($_scope[$theme->dbId]);
    $scope = array_values($_scope);
    // check if the new scope is still a true superset of the parent name's scope
    $nameScopeObj = $this->parent->getScopeObject();
    if ($nameScopeObj->isTrueSubset($scope)) {
      $this->mysql->startTransaction(true);
      $this->unsetScope();
      $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope);
      $this->setScope($scopeObj);
      $this->updateScopedPropertyHash($scope);
      $this->mysql->finishTransaction(true);
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        NameImpl::SCOPE_NO_SUPERSET_ERR_MSG);
    }
  }
  
  /**
   * Gets the hash.
   * 
   * @return string The hash.
   */
  protected function getHash() {
    $query = 'SELECT hash FROM ' . $this->config['table']['variant'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $result['hash'];
  }
}
?>