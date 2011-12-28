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
 * Represents a topic name item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-topic-name}.
 *
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns the 
 * {@link TopicImpl} to which this name belongs.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class NameImpl extends ScopedImpl implements Name
{        
  /**
   * The property holder for construct properties after initial retrieval 
   * from storage.
   * 
   * @var array
   */
  private $_propertyHolder;
  
  /**
   * Constructor.
   * 
   * @param int The construct id in its table representation in the MySQL database.
   * @param Mysql The MySQL wrapper.
   * @param array The configuration data.
   * @param TopicImpl The parent topic.
   * @param TopicMapImpl The containing topic map.
   * @param array The property holder.
   * @return void
   */
  public function __construct(
    $dbId, 
    Mysql $mysql, 
    array $config, 
    Topic $parent, 
    TopicMap $topicMap, 
    array $propertyHolder=array()
    )
  {  
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
    $this->_propertyHolder = $propertyHolder;
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
    ) {
      $this->_parent->finished($this);
    }
  }

  /**
   * Returns the value of this name.
   *
   * @return string
   */
  public function getValue()
  {
    if (isset($this->_propertyHolder['value']) && !empty($this->_propertyHolder['value'])) {
      return $this->_propertyHolder['value'];
    }
    $query = 'SELECT value FROM ' . $this->_config['table']['topicname'] . 
      ' WHERE id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->_propertyHolder['value'] = $result['value'];
  }

  /**
   * Sets the value of this name.
   * The previous value is overridden.
   *
   * @param string The name string to be assigned to the name; must not be <var>null</var>.
   * @return void
   * @throws {@link ModelConstraintException} If the the <var>value</var> is <var>null</var>.
   */
  public function setValue($value)
  {
    if (is_null($value)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_valueNullErrMsg
      );
    }
    // create a legal SQL string
    $escapedValue = $this->_mysql->escapeString($value);
    
    $this->_mysql->startTransaction();
    $query = 'UPDATE ' . $this->_config['table']['topicname'] . 
      ' SET value = "' . $escapedValue . '"' .
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    $hash = $this->_parent->_getNameHash($value, $this->getType(), $this->getScope());
    $this->_parent->_updateNameHash($this->_dbId, $hash);
    $this->_mysql->finishTransaction();
    
    if (!$this->_mysql->hasError()) {
      $this->_propertyHolder['value'] = $value;
      $this->_postSave();
    }
  }

  /**
   * Returns the {@link VariantImpl}s defined for this name.
   * The return array may be empty but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link VariantImpl}s.
   */
  public function getVariants()
  {
    $variants = array();
    $query = 'SELECT id, value, datatype, hash FROM ' . $this->_config['table']['variant'] . 
      ' WHERE topicname_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['value'] = $result['value'];
      $propertyHolder['datatype'] = $result['datatype'];
      
      $this->_topicMap->_setConstructPropertyHolder($propertyHolder);
      $this->_topicMap->_setConstructParent($this);
      $variant = $this->_topicMap->_getConstructByVerifiedId(
        'VariantImpl-' . $result['id'],
        $result['hash']
      );
      
      $variants[$result['hash']] = $variant;
    }
    return array_values($variants);
  }

  /**
   * Creates a {@link VariantImpl} of this topic name with the specified
   * <var>value</var>, <var>datatype</var>, and <var>scope</var>. 
   * The newly created {@link VariantImpl} will have the datatype specified by
   * <var>datatype</var>. 
   * The newly created {@link VariantImpl} will contain all themes from the parent name 
   * and the themes specified in <var>scope</var>.
   * 
   * @param string A string representation of the value.
   * @param string A URI indicating the datatype of the <var>value</var>. E.g.
   *        http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @param array An array (length >= 1) containing {@link TopicImpl}s, each representing a 
   * 				theme.
   * @return VariantImpl
   * @throws {@link ModelConstraintException} If the <var>value</var> or the <var>datatype</var>
   *        is <var>null</var>, if the scope of the variant would not be a true superset of 
   *        the name's scope, or if a <var>theme</var> in the scope does not belong to the 
   *        parent topic map.
   */
  public function createVariant($value, $datatype, array $scope)
  {
    if (is_null($value) || is_null($datatype)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_valueDatatypeNullErrMsg
      ); 
    }
    foreach ($scope as $theme) {
      if ($theme instanceof Topic && !$this->_topicMap->equals($theme->_topicMap)) {
        throw new ModelConstraintException(
          $this, 
          __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
        );
      }
    }
    
    $propertyHolder['value'] = $value;
    $propertyHolder['datatype'] = $datatype;
    $this->_topicMap->_setConstructPropertyHolder($propertyHolder);
    
    $this->_topicMap->_setConstructParent($this);
    
    $mergedScope = array_merge($scope, $this->getScope());
    $hash = $this->_getVariantHash($value, $datatype, $mergedScope);
    $variantId = $this->_hasVariant($hash);
    
    if ($variantId) {
      return $this->_topicMap->_getConstructByVerifiedId('VariantImpl-' . $variantId);
    }
    
    if (!$this->_getScopeObject()->isTrueSubset($mergedScope)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ': Variant\'s scope is not a true superset of the name\'s scope!'
      );
    }
    
    // create legal SQL strings
    $escapedValue = $this->_mysql->escapeString($value);
    $escapedDatatype = $this->_mysql->escapeString($datatype);
    
    $this->_mysql->startTransaction(true);
    $query = 'INSERT INTO ' . $this->_config['table']['variant'] . 
      ' (id, topicname_id, value, datatype, hash) VALUES' .
      ' (NULL, ' . $this->_dbId . ', "' . $escapedValue . '", "' . $escapedDatatype . '", "' . $hash . '")';
    $mysqlResult = $this->_mysql->execute($query);
    $lastVariantId = $mysqlResult->getLastId();
    
    $query = 'INSERT INTO ' . $this->_config['table']['topicmapconstruct'] . 
      ' (variant_id, topicmap_id, parent_id) VALUES' .
      ' (' . $lastVariantId . ', ' . $this->_topicMap->_dbId . ', ' . $this->_dbId . ')';
    $this->_mysql->execute($query);
    
    $scopeObj = new ScopeImpl($this->_mysql, $this->_config, $scope, $this->_topicMap, $this);
    $query = 'INSERT INTO ' . $this->_config['table']['variant_scope'] . 
      ' (scope_id, variant_id) VALUES' .
      ' (' . $scopeObj->_dbId . ', ' . $lastVariantId . ')';
    $this->_mysql->execute($query);
    
    $this->_mysql->finishTransaction(true);
    
    $variant = $this->_topicMap->_getConstructByVerifiedId('VariantImpl-' . $lastVariantId);
    
    if (!$this->_mysql->hasError()) {
      $variant->_postInsert();
      $this->_postSave();
    }
    return $variant;
  }
  
  /**
   * Returns the reifier of this name.
   * 
   * @return TopicImpl The topic that reifies this name or
   *        <var>null</var> if this name is not reified.
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
   * Returns the type of this construct.
   *
   * @return TopicImpl
   */
  public function getType()
  {
    if (
      isset($this->_propertyHolder['type_id']) && 
      !empty($this->_propertyHolder['type_id'])
    ) {
      return $this->_topicMap->_getConstructByVerifiedId(
      	'TopicImpl-' . $this->_propertyHolder['type_id']
      );
    }
    $query = 'SELECT type_id FROM ' . $this->_config['table']['topicname'] . 
      ' WHERE id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    $this->_propertyHolder['type_id'] = $result['type_id'];
    return $this->_topicMap->_getConstructByVerifiedId('TopicImpl-' . $result['type_id']);
  }

  /**
   * Sets the type of this name.
   * Any previous type is overridden.
   * 
   * @param TopicImpl The topic that should define the nature of this name.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>type</var> does not belong 
   *        to the parent topic map.
   */
  public function setType(Topic $type)
  {
    if (!$this->_topicMap->equals($type->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
    $this->_mysql->startTransaction();
    $query = 'UPDATE ' . $this->_config['table']['topicname'] . 
      ' SET type_id = ' . $type->_dbId . 
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    $hash = $this->_parent->_getNameHash($this->getValue(), $type, $this->getScope());
    $this->_parent->_updateNameHash($this->_dbId, $hash);
    $this->_mysql->finishTransaction();
    
    if (!$this->_mysql->hasError()) {
      // reference needed for merging context
      $this->_propertyHolder['type_id'] =& $type->_dbId;
      $this->_postSave();
    }
  }
  
  /**
   * Deletes this name.
   * 
   * @override
   * @return void
   */
  public function remove()
  {
    $this->_preDelete();
    $scopeObj = $this->_getScopeObject();
    $query = 'DELETE FROM ' . $this->_config['table']['topicname'] . 
      ' WHERE id = ' . $this->_dbId;
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
   * Tells the Topic Maps system that a variant modification is finished and 
   * duplicate removal can take place.
   * 
   * NOTE: This may be a resource consuming process.
   * 
   * @param VariantImpl The modified variant.
   * @return void
   */
  public function finished(IVariant $variant)
  {
    // get the hash of the finished variant
    $query = 'SELECT hash FROM ' . $this->_config['table']['variant'] . 
      ' WHERE id = ' . $variant->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    // detect duplicates
    $query = 'SELECT id FROM ' . $this->_config['table']['variant'] . 
      ' WHERE hash = "' . $result['hash'] . '"' .
      ' AND id <> ' . $variant->_dbId . 
      ' AND topicname_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $this->_topicMap->_setConstructParent($this);
      $duplicate = $this->_topicMap->_getConstructByVerifiedId('VariantImpl-' . $result['id']);
      // gain duplicate's item identities
      $variant->_gainItemIdentifiers($duplicate);
      // gain duplicate's reifier
      $variant->_gainReifier($duplicate);
      
      $variant->_postSave();
      $duplicate->remove();
    }
  }
  
  /**
   * Gets the variant hash.
   * 
   * @param string The value.
   * @param string The datatype.
   * @param array The scope.
   * @return string
   */
  protected function _getVariantHash($value, $datatype, array $scope)
  {
    $scopeIdsImploded = null;
    if (count($scope) > 0) {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) {
          $ids[$theme->_dbId] = $theme->_dbId;
        }
      }
      ksort($ids);
      $scopeIdsImploded = implode('', $ids);
    }
    return md5($value . $datatype . $scopeIdsImploded);
  }
  
  /**
   * Updates variant hash.
   * 
   * @param int The variant id.
   * @param string The variant hash.
   */
  protected function _updateVariantHash($variantId, $hash)
  {
    $query = 'UPDATE ' . $this->_config['table']['variant'] . 
      ' SET hash = "' . $hash . '"' .
      ' WHERE id = ' . $variantId;
    $this->_mysql->execute($query);
  }
  
  /**
   * Checks if topic name has a certain variant.
   * 
   * @param string The hash code.
   * @return false|int The variant id or <var>false</var> otherwise.
   */
  protected function _hasVariant($hash)
  {
    $query = 'SELECT id FROM ' . $this->_config['table']['variant'] . 
      ' WHERE topicname_id = ' . $this->_dbId . 
      ' AND hash = "' . $hash . '"';
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    if ($numRows > 0) {
      $result = $mysqlResult->fetch();
      return (int) $result['id'];
    }
    return false;
  }
}
?>