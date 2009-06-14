<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
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
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class NameImpl extends ScopedImpl implements Name {
  
  const VARIANT_CLASS_NAME = 'VariantImpl',
        SCOPE_NO_SUPERSET_ERR_MSG = ": Variant's scope is not a true superset of the name's scope!";
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicImpl The parent topic.
   * @param TopicMapImpl The containing topic map.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, Topic $parent, 
    TopicMap $topicMap) {
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
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
   * Returns the value of this name.
   *
   * @return string
   */
  public function getValue() {
    $query = 'SELECT value FROM ' . $this->config['table']['topicname'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $result['value'];
  }

  /**
   * Sets the value of this name.
   * The previous value is overridden.
   *
   * @param string The name string to be assigned to the name; must not be <var>null</var>.
   * @return void
   * @throws {@link ModelConstraintException} If the the <var>value</var> is <var>null</var>.
   */
  public function setValue($value) {
    if (!is_null($value)) {
      $value = CharacteristicUtils::canonicalize($value);
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['topicname'] . 
        ' SET value = "' . $value . '"' .
        ' WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->parent->getNameHash($value, $this->getType(), $this->getScope());
      $this->parent->updateNameHash($this->dbId, $hash);
      $this->mysql->finishTransaction();
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        ConstructImpl::VALUE_NULL_ERR_MSG);
    }
  }

  /**
   * Returns the {@link VariantImpl}s defined for this name.
   * The return array may be empty but must never be <var>null</var>.
   *
   * @return array An array containing {@link VariantImpl}s.
   */
  public function getVariants() {
    $variants = array();
    $query = 'SELECT id FROM ' . $this->config['table']['variant'] . 
      ' WHERE topicname_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $this->topicMap->setConstructParent($this);
      $variant = $this->topicMap->getConstructById(self::VARIANT_CLASS_NAME . '-' . $result['id']);
      $variants[] = $variant;
    }
    return $variants;
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
   * @param string A URI indicating the datatype of the <var>value</var>. I.e.
   *        http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @param array An array (length >= 1) containing {@link TopicImpl}s, each representing a theme.
   * @return VariantImpl
   * @throws {@link ModelConstraintException} If the <var>value</var> or <var>datatype</var>
   *        is <var>null</var>, or the scope of the variant would not be a 
   *        true superset of the name's scope.
   */
  public function createVariant($value, $datatype, array $scope) {
    if (!is_null($value) && !is_null($datatype)) {
      $value = CharacteristicUtils::canonicalize($value);
      $mergedScope = array_merge($scope, $this->getScope());
      $hash = $this->getVariantHash($value, $datatype, $scope);
      $variantId = $this->hasVariant($hash);
      if (!$variantId) {
        // check if merged scope is a true superset of the name's scope
        $nameScopeObj = $this->getScopeObject();
        if ($nameScopeObj->isTrueSubset($mergedScope)) {
          $this->mysql->startTransaction(true);
          $query = 'INSERT INTO ' . $this->config['table']['variant'] . 
            ' (id, topicname_id, value, datatype, hash) VALUES' .
            ' (NULL, ' . $this->dbId . ', "' . $value . '", "' . $datatype . '", "' . $hash . '")';
          $mysqlResult = $this->mysql->execute($query);
          $lastVariantId = $mysqlResult->getLastId();
          
          $query = 'INSERT INTO ' . $this->config['table']['topicmapconstruct'] . 
            ' (variant_id, topicmap_id, parent_id) VALUES' .
            ' (' . $lastVariantId . ', ' . $this->topicMap->dbId . ', ' . $this->dbId . ')';
          $this->mysql->execute($query);
          
          $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope);
          $query = 'INSERT INTO ' . $this->config['table']['variant_scope'] . 
            ' (scope_id, variant_id) VALUES' .
            ' (' . $scopeObj->dbId . ', ' . $lastVariantId . ')';
          $this->mysql->execute($query);
          
          $this->mysql->finishTransaction(true);
          $this->topicMap->setConstructParent($this);
          return $this->topicMap->getConstructById(self::VARIANT_CLASS_NAME . '-' . $lastVariantId);
        } else {
          throw new ModelConstraintException($this, __METHOD__ . 
            self::SCOPE_NO_SUPERSET_ERR_MSG);
        }
      } else {
        $this->topicMap->setConstructParent($this);
        return $this->topicMap->getConstructById(self::VARIANT_CLASS_NAME . '-' . $variantId);
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        ConstructImpl::VALUE_DATATYPE_NULL_ERR_MSG);
    }
  }
  
  /**
   * Returns the reifier of this name.
   * 
   * @return TopicImpl The topic that reifies this name or
   *        <var>null</var> if this name is not reified.
   */
  public function getReifier() {
    return $this->_getReifier();
  }

  /**
   * Sets the reifier of this name.
   * The specified <var>reifier</var> MUST NOT reify another information item.
   *
   * @param TopicImpl|null The topic that should reify this name or null
   *        if an existing reifier should be removed.
   * @return void
   * @throws {@link ModelConstraintException} If the specified <var>reifier</var> 
   *        reifies another construct.
   */
  public function setReifier($reifier) {
    $this->_setReifier($reifier);
  }
  
  /**
   * Returns the type of this construct.
   *
   * @return TopicImpl
   */
  public function getType() {
    $query = 'SELECT type_id FROM ' . $this->config['table']['topicname'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
      $result['type_id']);
  }

  /**
   * Sets the type of this name.
   * Any previous type is overridden.
   * 
   * @param TopicImpl The topic that should define the nature of this name.
   * @return void
   */
  public function setType(Topic $type) {
    $this->mysql->startTransaction();
    $query = 'UPDATE ' . $this->config['table']['topicname'] . 
      ' SET type_id = ' . $type->dbId . 
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
    
    $hash = $this->parent->getNameHash($this->getValue(), $type, $this->getScope());
    $this->parent->updateNameHash($this->dbId, $hash);
    $this->mysql->finishTransaction();
  }
  
  /**
   * Deletes this name.
   * 
   * @override
   * @return void
   */
  public function remove() {
    $query = 'DELETE FROM ' . $this->config['table']['topicname'] . 
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      $this->id = null;
      $this->dbId = null;
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
  public function getVariantHash($value, $datatype, array $scope) {
    $scopeIdsImploded = null;
    if (count($scope) > 0) {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) $ids[$theme->dbId] = $theme->dbId;
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
  public function updateVariantHash($variantId, $hash) {
    $query = 'UPDATE ' . $this->config['table']['variant'] . 
      ' SET hash = "' . $hash . '"' .
      ' WHERE id = ' . $variantId;
    $this->mysql->execute($query);
  }
  
  /**
   * Checks if topic name has a certain variant.
   * 
   * @param string The hash code.
   * @return false|int The variant id or <var>false</var> otherwise.
   */
  public function hasVariant($hash) {
    $return = false;
    $query = 'SELECT id FROM ' . $this->config['table']['variant'] . 
      ' WHERE topicname_id = ' . $this->dbId . 
      ' AND hash = "' . $hash . '"';
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      $return = (int) $result['id'];
    }
    return $return;
  }
  
  /**
   * Tells the topic map system that a variant modification is finished and 
   * duplicate removal can take place.
   * 
   * NOTE: This may be a resource consuming process.
   * 
   * @param VariantImpl The modified variant.
   * @return void
   */
  public function finished(Variant $variant) {
    // get the hash of the finished variant
    $query = 'SELECT hash FROM ' . $this->config['table']['variant'] . 
      ' WHERE id = ' . $variant->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    // detect duplicates
    $query = 'SELECT id FROM ' . $this->config['table']['variant'] . 
      ' WHERE hash = "' . $result['hash'] . '"' .
      ' AND id <> ' . $variant->dbId . 
      ' AND topicname_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {// there exist duplicates
      while ($result = $mysqlResult->fetch()) {
        $this->topicMap->setConstructParent($this);
        $duplicate = $this->topicMap->getConstructById(self::VARIANT_CLASS_NAME . '-' . 
          $result['id']);
        // gain duplicate's item identities
        $variant->gainItemIdentifiers($duplicate);
        // gain duplicate's reifier
        $variant->gainReifier($duplicate);
        $duplicate->remove();
      }
    }
  }
}
?>