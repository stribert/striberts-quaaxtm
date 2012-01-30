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
final class VariantImpl extends ScopedImpl implements IVariant
{  
  /**
   * The property holder for construct properties after initial retrieval 
   * from storage.
   * 
   * @var array
   */
  private $_propertyHolder;
  
  /**
   * The variant MD5 hash as stored in MySQL table "qtm_variant".
   * 
   * @var string
   */
  private $_hash;
  
  /**
   * Constructor.
   * 
   * @param int The construct id in its table representation in the MySQL database.
   * @param Mysql The MySQL wrapper.
   * @param array The configuration data.
   * @param NameImpl The parent name.
   * @param TopicMapImpl The containing topic map.
   * @param array The property holder.
   * @param string|null The variant hash.
   * @return void
   */
  public function __construct(
    $dbId, 
    Mysql $mysql, 
    array $config, 
    Name $parent, 
    TopicMap $topicMap, 
    array $propertyHolder=array(), 
    $hash=null
    )
  {  
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
    $this->_propertyHolder = $propertyHolder;
    $this->_hash = $hash;
  }
  
  /**
   * Destructor. If enabled duplicate removal in database takes place.
   * 
   * @return void
   */
  public function __destruct()
  {
    $featureIsSet = $this->_topicMap->getTopicMapSystem()->getFeature(
      VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL
    );
    if (
      $featureIsSet && 
      !is_null($this->_dbId) && 
      !is_null($this->_parent->_dbId) && 
      $this->_mysql->isConnected()
      ) 
    {
      $this->_parent->finished($this);
    }
  }
  
  /**
   * Returns the string representation of the value.
   * 
   * @return string The string representation of the value (never <var>null</var>).
   */
  public function getValue()
  {
    if (isset($this->_propertyHolder['value']) && !empty($this->_propertyHolder['value'])) {
      return $this->_propertyHolder['value'];
    }
    $query = 'SELECT value FROM ' . $this->_config['table']['variant'] . 
      ' WHERE id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->_propertyHolder['value'] = $result['value'];
  }

  /**
   * Returns the URI identifying the datatype of the value.
   * E.g. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   *
   * @return string The datatype of this construct (never <var>null</var>).
   */
  public function getDatatype()
  {
    if (
      isset($this->_propertyHolder['datatype']) && 
      !empty($this->_propertyHolder['datatype'])
    ) {
      return $this->_propertyHolder['datatype'];
    }
    $query = 'SELECT datatype FROM ' . $this->_config['table']['variant'] . 
      ' WHERE id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->_propertyHolder['datatype'] = $result['datatype'];
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
  public function setValue($value, $datatype)
  {
    if (is_null($value) || is_null($datatype)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_valueDatatypeNullErrMsg
      );
    }
    // create legal SQL strings
    $escapedValue = $this->_mysql->escapeString($value);
    $escapedDatatype = $this->_mysql->escapeString($datatype);
    
    $this->_mysql->startTransaction();
    $query = 'UPDATE ' . $this->_config['table']['variant'] . 
      ' SET value = "' . $escapedValue . '", ' . 
      'datatype = "' . $escapedDatatype . '" ' . 
      'WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    $hash = $this->_parent->_getVariantHash($value, $datatype, $this->getScope());
    $this->_parent->_updateVariantHash($this->_dbId, $hash);
    
    $this->_mysql->finishTransaction();
    
    if (!$this->_mysql->hasError()) {
      $this->_propertyHolder['value'] = $value;
      $this->_propertyHolder['datatype'] = $datatype;
      $this->_postSave();
    }
  }
  
  /**
   * Returns the reifier of this variant.
   * 
   * @return TopicImpl The topic that reifies this variant or
   *        <var>null</var> if this variant is not reified.
   */
  public function getReifier()
  {
    return $this->_getReifier();
  }

  /**
   * @see ConstructImpl::_setReifier()
   */
  public function setReifier(Topic $reifier=null)
  {
    $this->_setReifier($reifier);
  }
  
  /**
   * Deletes this variant.
   * 
   * @override
   * @return void
   */
  public function remove()
  {
    $this->_preDelete();
    $scopeObj = $this->_getScopeObject();
    $query = 'DELETE FROM ' . $this->_config['table']['variant'] . ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    if (!$this->_mysql->hasError()) {
      if (!$scopeObj->isUnconstrained()) {
        $this->_unsetScope($scopeObj);// triggers clean up routine
      }
      $this->_id = 
      $this->_dbId = null;
      $this->_propertyHolder = array();
    }
  }
  
  /**
   * Returns the merged scope of the {@link VariantImpl} and the parent {@link NameImpl}.
   * 
   * @see ScopedImpl::getScope()
   * @override
   */
  public function getScope()
  {
    $scope = array_merge(parent::getScope(), $this->_parent->getScope());
    return $this->_arrayToSet($scope);
  }
  
  /**
   * Gets the hash.
   * 
   * @return string The hash.
   */
  protected function _getHash()
  {
    if (!is_null($this->_hash)) {
      return $this->_hash;
    }
    $query = 'SELECT hash FROM ' . $this->_config['table']['variant'] . 
      ' WHERE id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->_hash = $result['hash'];
  }
}
?>