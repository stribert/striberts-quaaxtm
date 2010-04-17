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
 * Represents a variant item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-variant}.
 * 
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns the 
 * {@link NameImpl} to which this Variant belongs.
 * Inherited method <var>getScope()</var> from {@link ScopedImpl} returns the union of 
 * its own scope and the parent's scope.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class VariantImpl extends ScopedImpl implements IVariant {
  
  private $propertyHolder;
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param NameImpl The parent name.
   * @param TopicMapImpl The containing topic map.
   * @param PropertyUtils The property holder.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, Name $parent, 
    TopicMap $topicMap, PropertyUtils $propertyHolder=null) {
    
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
    
    $this->propertyHolder = !is_null($propertyHolder) ? $propertyHolder : new PropertyUtils();
  }
  
  /**
   * Destructor. If enabled duplicate removal in database takes place.
   * 
   * @return void
   */
  public function __destruct() {
    if ($this->topicMap->getTopicMapSystem()->getFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL) && 
      !is_null($this->dbId) && !is_null($this->parent->dbId)) $this->parent->finished($this);
  }
  
  /**
   * Returns the string representation of the value.
   * 
   * @return string The string representation of the value (never <var>null</var>).
   */
  public function getValue() {
    if (!is_null($this->propertyHolder->getValue())) {
      return $this->propertyHolder->getValue();
    } else {
      $query = 'SELECT value FROM ' . $this->config['table']['variant'] . 
        ' WHERE id = ' . $this->dbId;
      $mysqlResult = $this->mysql->execute($query);
      $result = $mysqlResult->fetch();
      $this->propertyHolder->setValue($result['value']);
      return $result['value'];
    }
  }

  /**
   * Returns the URI identifying the datatype of the value.
   * E.g. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   *
   * @return string The datatype of this construct (never <var>null</var>).
   */
  public function getDatatype() {
    if (!is_null($this->propertyHolder->getDatatype())) {
      return $this->propertyHolder->getDatatype();
    } else {
      $query = 'SELECT datatype FROM ' . $this->config['table']['variant'] . 
        ' WHERE id = ' . $this->dbId;
      $mysqlResult = $this->mysql->execute($query);
      $result = $mysqlResult->fetch();
      $this->propertyHolder->setDatatype($result['datatype']);
      return $result['datatype'];
    }
  }

  /**
   * Sets the value and the datatype.
   *
   * @param string The string representation of the value; must not be <var>null</var>.
   * @param string The URI identifying the datatype of the value; must not be <var>null</var>. 
   *        E.g. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>value</var> or <var>datatype</var> 
   *        is <var>null</var>.
   */
  public function setValue($value, $datatype) {
    if (!is_null($value) && !is_null($datatype)) {
      $value = CharacteristicUtils::canonicalize($value, $this->mysql->getConnection());
      $datatype = CharacteristicUtils::canonicalize($datatype, $this->mysql->getConnection());
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['variant'] . 
        ' SET value = "' . $value . '", ' . 
        'datatype = "' . $datatype . '" ' . 
        'WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->parent->getVariantHash($value, $datatype, $this->getScope());
      $this->parent->updateVariantHash($this->dbId, $hash);
      $this->mysql->finishTransaction();
      
      if (!$this->mysql->hasError()) {
        $this->propertyHolder->setValue($value);
        $this->propertyHolder->setDatatype($datatype);
        $this->postSave();
      }
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
   * @see ConstructImpl::_setReifier()
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
    $this->preDelete();
    $scopeObj = $this->getScopeObject();
    $query = 'DELETE FROM ' . $this->config['table']['variant'] . 
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      if (!$scopeObj->isUnconstrained()) {
        $this->unsetScope($scopeObj);// triggers clean up routine
      }
      $this->id = 
      $this->dbId = 
      $this->propertyHolder = null;
    }
  }
  
  /**
   * Returns the merged scope of the {@link VariantImpl} and the parent {@link NameImpl}.
   * 
   * @see ScopedImpl::getScope()
   * @override
   */
  public function getScope() {
    $scope = array_merge(parent::getScope(), $this->parent->getScope());
    return $this->arrayToSet($scope);
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