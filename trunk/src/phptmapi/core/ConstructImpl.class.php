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
 * Base interface for all Topic Maps constructs.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
abstract class ConstructImpl implements Construct
{  
  const ASSOC_FK_COL = 'association_id',
        ROLE_FK_COL = 'assocrole_id',
        TOPIC_FK_COL = 'topic_id',
        OCC_FK_COL = 'occurrence_id',
        NAME_FK_COL = 'topicname_id',
        VARIANT_FK_COL = 'variant_id',
        TOPICMAP_FK_COL = 'topicmap_id';
  
  protected $_id,
            $_parent,
            $_mysql,
            $_config,
            $_topicMap,
            $_dbId,
            $_fkColumn,
            $_className,
            $_constructDbId;
            
  protected static $_valueNullErrMsg = ': Value must not be null!',
                  $_valueDatatypeNullErrMsg = ': Value and datatype must not be null!',
                  $_iidExistsErrMsg = ': Item identifier already exists!',
                  $_sameTmConstraintErrMsg = ': Same topic map constraint violation!',
                  $_identityNullErrMsg = ': Identity locator must not be null!';
                  
  
  /**
   * Constructor.
   * 
   * @param string The Topic Maps construct id.
   * @param ConstructImpl|null The parent Topic Maps construct or <var>null</var>
   *        iff the construct is an instance of {@link TopicMapImpl}.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicMapImpl The containing topic map.
   * @return void
   */
  public function __construct($id, $parent, Mysql $mysql, array $config, $topicMap)
  {
    $this->_id = $id;
    $this->_parent = $parent;
    $this->_mysql = $mysql;
    $this->_config = $config;
    $this->_topicMap = $topicMap;
    $this->_dbId = $this->getDbId();
    $this->_className = get_class($this);
    $this->_fkColumn = $this->_getFkColumn($this->_className);
    $this->_constructDbId = !$this instanceof TopicMap ? $this->_getConstructDbId() : null;
  }
    
  /**
   * Returns the parent of this construct. See the derived constructs for the particular
   * return value.
   * This method returns <var>null</var> iff this construct is a {@link TopicMapImpl}
   * instance.
   *
   * @return ConstructImpl|null The parent of this construct or <var>null</var> 
   *        iff the construct is an instance of {@link TopicMapImpl}.
   */
  public function getParent()
  {
    return $this->_parent;
  }

  /**
   * Returns the {@link TopicMapImpl} instance to which this Topic Maps construct 
   * belongs.
   * A {@link TopicMapImpl} instance returns itself.
   *
   * @return TopicMapImpl The topic map instance to which this construct belongs.
   */
  public function getTopicMap()
  {
    return !$this instanceof TopicMap ? $this->_topicMap : $this;
  }

  /**
   * Returns the identifier of this construct.
   * This property has no representation in the Topic Maps - Data Model (TMDM).
   *
   * @return string An identifier which identifies this construct uniquely within
   *        a topic map.
   */
  public function getId()
  {
    return $this->_id;
  }

  /**
   * Returns the item identifiers of this Topic Maps construct.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the item identifiers.
   */
  public function getItemIdentifiers()
  {
    $iids = array();
    $query = 'SELECT locator FROM ' . $this->_config['table']['itemidentifier'] . 
      ' WHERE topicmapconstruct_id = ' . $this->_constructDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $iids[] = $result['locator'];
    }
    return $iids;
  }

  /**
   * Adds an item identifier.
   * It is not allowed to have two {@link ConstructImpl}s in the same 
   * {@link TopicMapImpl} with the same item identifier. 
   * If the two objects are {@link TopicImpl}s, then they must be merged. 
   * If at least one of the two objects is not a {@link TopicImpl}, an
   * {@link IdentityConstraintException} must be reported. 
   *
   * @param string The item identifier to be added; must not be <var>null</var>.
   * @return void
   * @throws {@link IdentityConstraintException} If another construct has an item
   *        identifier which is equal to <var>itemIdentifier</var>.
   * @throws {@link ModelConstraintException} If the item identifier is <var>null</var>.
   */
  public function addItemIdentifier($iid)
  {
    if (is_null($iid)) {
      throw new ModelConstraintException(
        $this, __METHOD__ . self::$_identityNullErrMsg
      );
    }
    // define insert stmnt only once
    $insert = 'INSERT INTO ' . $this->_config['table']['itemidentifier'] . 
      ' (topicmapconstruct_id, locator) VALUES' .
      ' (' . $this->_constructDbId . ', "' . $iid . '")';
    // check if given item identifier exists in topic map
    $query = 'SELECT t1.*' . 
      ' FROM ' . $this->_config['table']['topicmapconstruct'] . ' t1' . 
      ' INNER JOIN ' . $this->_config['table']['itemidentifier'] . ' t2' .
      ' ON t1.id = t2.topicmapconstruct_id' .
      ' WHERE t2.locator = "' . $iid . '"' . 
      ' AND t1.topicmap_id = ' . $this->getTopicMap()->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows == 0) {
      // if construct is a topic check sids too
      if ($this instanceof Topic) {
        $query = 'SELECT t2.id' .
          ' FROM ' . $this->_config['table']['subjectidentifier'] . ' t1' .
          ' INNER JOIN ' . $this->_config['table']['topic'] . ' t2' .
          ' ON t2.id = t1.topic_id' .
          ' WHERE t1.locator = "' . $iid . '"' .
          ' AND t2.topicmap_id = ' . $this->getTopicMap()->_dbId . 
          ' AND t1.topic_id <> ' . $this->_dbId;
        $mysqlResult = $this->_mysql->execute($query);
        $rows = $mysqlResult->getNumRows();
        if ($rows == 0) {
          $this->_mysql->execute($insert);
          if (!$this->_mysql->hasError()) {
            $this->_postSave();
          }
        } else {// merge
          $result = $mysqlResult->fetch();
          $existingTopic = $this->getTopicMap()->_getConstructByVerifiedId(
          	'TopicImpl-' . $result['id']
          );
          $this->mergeIn($existingTopic);
        }
      } else {
        $this->_mysql->execute($insert);
        if (!$this->_mysql->hasError()) {
          $this->_postSave();
        }
      }
    } else {// the given item identifier already exists
      $existingConstruct = $this->_factory($mysqlResult);
      if (!$existingConstruct instanceof Topic || !$this instanceof Topic) {
        throw new IdentityConstraintException(
          $this, 
          $existingConstruct, 
          $iid, 
          __METHOD__ . self::$_iidExistsErrMsg
        );
      }
      if (!$existingConstruct->equals($this)) {
        $this->mergeIn($existingConstruct);
      }
    }
  }

  /**
   * Removes an item identifier.
   *
   * @param string The item identifier to be removed from this construct, 
   *        if present (<var>null</var> is ignored).
   * @return void
   */
  public function removeItemIdentifier($iid)
  {
    if (is_null($iid)) {
      return;
    }
    $query = 'DELETE FROM ' . $this->_config['table']['itemidentifier'] . 
      ' WHERE topicmapconstruct_id = ' . $this->_constructDbId . 
      ' AND locator = "' . $iid . '"';
    $this->_mysql->execute($query);
  }

  /**
   * Deletes a construct from its parent container. Each construct 
   * implements its own <var>remove()</var> method.
   * 
   * @return void
   */
  public function remove()
  {
    return;
  }

  /**
   * Returns <var>true</var> if the <var>other</var> construct is equal to this one. 
   * Equality must be the result of comparing the ids of the two constructs. 
   * 
   * Note: This equality test does not reflect any equality rule according
   * to the Topic Maps - Data Model (TMDM) by intention.
   *
   * @param ConstructImpl The construct to compare this construct against.
   * @return boolean
   */
  public function equals(Construct $other)
  {
    return $this->_id === $other->getId();
  }

  /**
   * Returns a hash code value.
   * The returned hash code is equal to the hash code of the {@link getId()}
   * property.
   *
   * @return string
   */
  public function hashCode()
  {
    return md5($this->_id);
  }
  
  /**
   * Gets the construct's database <var>id</var>
   * 
   * @return int The database id.
   */
  public function getDbId()
  {
    $constituents = explode('-', $this->_id);
    return (int) $constituents[1];
  }
  
  /**
   * Gets the construct's fk column name.
   * 
   * @param string The class name.
   * @return string|null The fk column name or <var>null</var> if class name is unknown.
   */
  protected function _getFkColumn($className)
  {
    switch ($className) {
      case 'AssociationImpl':
        return self::ASSOC_FK_COL;
      case 'RoleImpl':
        return self::ROLE_FK_COL;
      case 'OccurrenceImpl':
        return self::OCC_FK_COL;
      case 'TopicImpl':
        return self::TOPIC_FK_COL;
      case 'TopicMapImpl':
        return self::TOPICMAP_FK_COL;
      case 'NameImpl':
        return self::NAME_FK_COL;
      case 'VariantImpl':
        return self::VARIANT_FK_COL;
      default:
        return null;
    }
  }
  
  /**
   * Gains the item identifiers of the other construct.
   * 
   * NOTE: We can't use getItemIdentifiers() / addItemIdentifier() here
   * as this could cause an {@link IdentityConstraintException} for the 
   * duplicate construct which will be removed afterwards.
   * 
   * @param ConstructImpl The other construct.
   * @return void
   */
  protected function _gainItemIdentifiers(Construct $other)
  {
    if ($this->_className != $other->_className) {
      return;
    }
    // get other's item identifiers
    $query = $query = 'SELECT locator FROM ' . $this->_config['table']['itemidentifier'] . 
      ' WHERE topicmapconstruct_id = ' . $other->_constructDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // assign other's item identifiers
      $query = 'INSERT INTO ' . $this->_config['table']['itemidentifier'] . 
        ' (topicmapconstruct_id, locator) VALUES' .
        ' (' . $this->_constructDbId . ', "' . $result['locator'] . '")';
      $this->_mysql->execute($query);
    }
  }
  
  /**
   * Gains the reifier of the other construct.
   * 
   * Note: We can't use setReifier() here as this could cause a 
   * {@link ModelConstraintException} for the duplicate construct 
   * which will be removed afterwards.
   * 
   * @param ConstructImpl The other construct.
   * @return void
   * @throws {@link ModelConstraintException} If the two constructs have different reifiers.
   */
  protected function _gainReifier(Construct $other)
  {
    if ($this->_className != $other->_className) {
      return;
    }
    if (is_null($other->getReifier())) {
      return;
    }
    if (is_null($this->getReifier())) {
      $query = 'UPDATE ' . $this->_config['table']['topicmapconstruct'] . 
        ' SET reifier_id = ' . $other->getReifier()->_dbId . 
        ' WHERE id = ' . $this->_constructDbId;
      $this->_mysql->execute($query);
    } else {// both constructs have reifiers
      $otherReifier = $other->getReifier();
      // prevent MCE, the other construct will be removed afterwards
      $other->setReifier(null);
      $this->getReifier()->mergeIn($otherReifier);
    }
  }
  
  /**
   * Gains the {@link VariantImpl}s of the other name.
   * 
   * @param NameImpl The other topic name.
   * @return void
   */
  protected function _gainVariants(Name $other)
  {
    if (!$this instanceof Name) {
      return;
    }
    $otherVariants = $other->getVariants();
    foreach ($otherVariants as $otherVariant) {
      $variantId = $this->_hasVariant($otherVariant->_getHash());
      if (!$variantId) {// gain the variant
        $this->_mysql->startTransaction();
        $query = 'UPDATE ' . $this->_config['table']['variant'] . 
          ' SET topicname_id = ' . $this->_dbId  .
          ' WHERE id = ' . $otherVariant->_dbId;
        $this->_mysql->execute($query);
        
        $query = 'UPDATE ' . $this->_config['table']['topicmapconstruct'] . 
          ' SET parent_id = ' . $this->_dbId  .
          ' WHERE variant_id = ' . $otherVariant->_dbId;
        $this->_mysql->execute($query);
        $this->_mysql->finishTransaction();
        if (!$this->_mysql->hasError()) {
          $this->_postSave();
        }
      } else {// only gain variant's iids and reifier
        $this->getTopicMap()->_setConstructParent($this);
        $variant = $this->getTopicMap()->_getConstructByVerifiedId('VariantImpl-' . $variantId);
        $variant->_gainItemIdentifiers($otherVariant);
        $variant->_gainReifier($otherVariant);
        $variant->_postSave();
      }
    }
  }
  
  /**
   * Builds a construct from one row of the topicmapconstruct table.
   * 
   * @param MysqlResult The query result object.
   * @return ConstructImpl|null A construct or <var>null</var> if no construct
   *        could be created.
   */
  protected function _factory(MysqlResult $mysqlResult)
  {
    $topicMap = $this->getTopicMap();
    $result = $mysqlResult->fetch();
    if (!is_null($result['topic_id'])) {
      return $topicMap->_getConstructByVerifiedId('TopicImpl-' . $result['topic_id']);
    } elseif (!is_null($result['occurrence_id'])) {
      $parentTopic = $topicMap->_getConstructByVerifiedId('TopicImpl-' . $result['parent_id']);
      $topicMap->_setConstructParent($parentTopic);
      return $topicMap->_getConstructByVerifiedId('OccurrenceImpl-' . $result['occurrence_id']);
    } elseif (!is_null($result['topicname_id'])) {
      $parentTopic = $topicMap->_getConstructByVerifiedId('TopicImpl-' . $result['parent_id']);
      $topicMap->_setConstructParent($parentTopic);
      return $topicMap->_getConstructByVerifiedId('NameImpl-' . $result['topicname_id']);
    } elseif (!is_null($result['association_id'])) {
      return $topicMap->_getConstructByVerifiedId('AssociationImpl-' . $result['association_id']);
    } elseif (!is_null($result['assocrole_id'])) {
      $parentAssoc = $topicMap->_getConstructByVerifiedId('AssociationImpl-' . $result['parent_id']);
      $topicMap->_setConstructParent($parentAssoc);
      return $topicMap->_getConstructByVerifiedId('RoleImpl-' . $result['assocrole_id']);
    } elseif (!is_null($result['variant_id'])) {
      $parentTopicId = $this->_getNameParentId($result['parent_id']);
      $parentTopic = $topicMap->_getConstructByVerifiedId('TopicImpl-' . $parentTopicId);
      $topicMap->_setConstructParent($parentTopic);
      $parentName = $topicMap->_getConstructByVerifiedId('NameImpl-' . $result['parent_id']);
      $topicMap->_setConstructParent($parentName);
      return $topicMap->_getConstructByVerifiedId('VariantImpl-' . $result['variant_id']);
    } elseif (!is_null($result['topicmap_id'])) {
      return $topicMap->_getConstructByVerifiedId('TopicMapImpl-' . $result['topicmap_id']);
    } else {
      return null;
    }
  }
  
  /**
   * Returns the reifier of this construct.
   * 
   * @return TopicImpl|null The topic that reifies this construct or
   *        <var>null</var> if this construct is not reified.
   */
  protected function _getReifier()
  {
    $query = 'SELECT reifier_id FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE id = ' . $this->_constructDbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    if (!is_null($result['reifier_id'])) {
      return $this->getTopicMap()->_getConstructByVerifiedId('TopicImpl-' . $result['reifier_id']);
    } else {
      return null;
    }
  }
  
  /**
   * Sets the reifier of this construct.
   * The specified <var>reifier</var> MUST NOT reify another information item.
   *
   * @param TopicImpl|null The topic that should reify this construct or <var>null</var>
   *        if an existing reifier should be removed.
   * @return void
   * @throws {@link ModelConstraintException} If the specified <var>reifier</var> 
   *        reifies another construct or the <var>reifier</var> does not belong to
   *        the parent topic map.
   */
  protected function _setReifier($reifier)
  {
    if ($reifier instanceof Topic) {
      if (!$this->getTopicMap()->equals($reifier->_topicMap)) {
        throw new ModelConstraintException(
          $this, 
          __METHOD__ . self::$_sameTmConstraintErrMsg
        );
      }
      // check if reifier reifies another construct in this topic map
      $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topicmapconstruct'] . 
        ' WHERE topicmap_id = ' . $this->getTopicMap()->_dbId . 
        ' AND reifier_id = ' . $reifier->_dbId . 
        ' AND id <> ' . $this->_constructDbId;
      $mysqlResult = $this->_mysql->execute($query);
      $result = $mysqlResult->fetchArray();
      if ($result[0] == 0) {// there is no reified
        $query = 'UPDATE ' . $this->_config['table']['topicmapconstruct'] . 
          ' SET reifier_id = ' . $reifier->_dbId . 
          ' WHERE id = ' . $this->_constructDbId;
        $this->_mysql->execute($query);
        if (!$this->_mysql->hasError()) {
          $this->_postSave();
        }
      } else {
        throw new ModelConstraintException(
          $this, 
          __METHOD__ . ': Reifier reifies another construct!'
        );
      }
    } else {// setReifier() signature constrains to Topic or NULL; unset reifier
      $query = 'UPDATE ' . $this->_config['table']['topicmapconstruct'] . 
        ' SET reifier_id = NULL' .
        ' WHERE id = ' . $this->_constructDbId;
      $this->_mysql->execute($query);
      if (!$this->_mysql->hasError()) {
        $this->_postSave();
      }
    }
  }
  
  /**
   * Generates a true set from given array containing {@link Construct}s.
   * 
   * Note: This could also be done by SQL, however we want to avoid expensive
   * temp. tables when using DISTINCT or GROUP BY.
   * 
   * @param array An array containing {@link Construct}s.
   * @return array
   */
  protected function _arrayToSet(array $array)
  {
    $set = array();
    foreach ($array as $element) {
      $set[$element->getId()] = $element;
    }
    return array_values($set);
  }
  
  /**
   * Post insert hook for e.g. inserting into cache or search index.
   * It is guaranteed that this hook is only called of no MySQL error occurred.
   * 
   * @param array Optional parameters.
   * @return void
   */
  protected function _postInsert(array $params=array()){}
  
  /**
   * Post save hook for e.g. updating cache or search index.
   * It is guaranteed that this hook is only called of no MySQL error occurred.
   * 
   * @param array Optional parameters.
   * @return void
   */
  protected function _postSave(array $params=array()){}
  
  /**
   * Pre delete hook for e.g. cache or search index drop.
   * 
   * @param array Optional parameters.
   * @return void
   */
  protected function _preDelete(array $params=array())
  {
    $topicMap = $this->getTopicMap();
    if (array_key_exists($this->_id, $topicMap->_seenConstructsCache)) {
      unset($topicMap->_seenConstructsCache[$this->_id]);
    }
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_getConstructByVerifiedId()
   */
  protected function _getConstructByVerifiedId($id, $hash=null)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_setConstructParent()
   */
  protected function _setConstructParent(Construct $parent)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_setConstructPropertyHolder()
   */
  protected function _setConstructPropertyHolder(array $propertyHolder)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_getAssocHash()
   */
  protected function _getAssocHash(Topic $type, array $scope, array $roles)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_updateAssocHash()
   */
  protected function _updateAssocHash($assocId, $hash)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_hasAssoc()
   */
  protected function _hasAssoc($hash)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_removeAssociationFromCache()
   */
  protected function _removeAssociationFromCache($assocId)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_removeTopicFromCache()
   */
  protected function _removeTopicFromCache($topicId)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/NameImpl#_getVariantHash()
   */
  protected function _getVariantHash($value, $datatype, array $scope)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/NameImpl#_updateVariantHash()
   */
  protected function _updateVariantHash($variantId, $hash)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/NameImpl#_hasVariant()
   */
  protected function _hasVariant($hash)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicImpl#_getNameHash()
   */
  protected function _getNameHash($value, Topic $type, array $scope)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicImpl#_getOccurrenceHash()
   */
  protected function _getOccurrenceHash(Topic $type, $value, $datatype, array $scope)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicImpl#_updateNameHash()
   */
  protected function _updateNameHash($nameId, $hash)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicImpl#_updateOccurrenceHash()
   */
  protected function _updateOccurrenceHash($occId, $hash)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_setState()
   */
  protected function _setState($state)
  {
    return;
  }
  
  /**
   * (non-PHPDoc)
   * @see phptmapi/core/TopicMapImpl#_getState()
   */
  protected function _getState()
  {
    return;
  }
  
  /**
   * Gets the permission (or not) for using the memcached based MySQL result cache.
   * 
   * @return boolean <var>False</var> if in merging state, <var>true</var> otherwise.
   */
  protected function _getResultCachePermission()
  {
    return $this->_topicMap->_getState() != VocabularyUtils::QTM_STATE_MERGING;
  }
  
  /**
   * Gets the construct's topicmapconstruct table <var>id</var>.
   * {@link TopicMapImpl} implements its own <var>getConstructDbId()</var>.
   * 
   * @return int The id.
   */
  private function _getConstructDbId()
  {
    $query = 'SELECT id FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE ' . $this->_fkColumn . ' = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return (int) $result['id'];
  }
  
  /**
   * Gets the parent (topic) id of the given name (id).
   * 
   * @param string The name id.
   * @return int The parent (topic) id.
   */
  private function _getNameParentId($nameId)
  {
    $query = 'SELECT parent_id FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE topicname_id = ' . $nameId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return (int) $result['parent_id'];
  }
}
?>