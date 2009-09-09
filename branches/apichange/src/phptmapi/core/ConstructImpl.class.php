<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
abstract class ConstructImpl implements Construct {
  
  const REIFIER_ERR_MSG = ': Reifier reifies another construct!',
        VALUE_NULL_ERR_MSG = ': Value must not be null!',
        VALUE_DATATYPE_NULL_ERR_MSG = ': Value and datatype must not be null!',
        ITEM_IDENTIFIER_EXISTS_ERR_MSG = ': Item identifier already exists!',
        SAME_TM_CONSTRAINT_ERR_MSG = ': Same topic map constraint violation!',
        
        ASSOC_FK_COL = 'association_id',
        ROLE_FK_COL = 'assocrole_id',
        TOPIC_FK_COL = 'topic_id',
        OCC_FK_COL = 'occurrence_id',
        NAME_FK_COL = 'topicname_id',
        VARIANT_FK_COL = 'variant_id',
        TOPICMAP_FK_COL = 'topicmap_id';
  
  protected $id,
            $parent,
            $mysql,
            $config,
            $topicMap,
            $dbId,
            $fkColumn,
            $className,
            $constructDbId;
  
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
  public function __construct($id, $parent, Mysql $mysql, array $config, $topicMap) {
    $this->id = $id;
    $this->parent = $parent;
    $this->mysql = $mysql;
    $this->config = $config;
    $this->topicMap = $topicMap;
    $this->dbId = $this->getDbId();
    $this->className = get_class($this);
    $this->fkColumn = $this->getFkColumn($this->className);
    $this->constructDbId = !$this instanceof TopicMap ? $this->getConstructDbId() : null;
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
  public function getParent() {
    return $this->parent;
  }

  /**
   * Returns the {@link TopicMapImpl} instance to which this Topic Maps construct 
   * belongs.
   * A {@link TopicMapImpl} instance returns itself.
   *
   * @return TopicMapImpl The topic map instance to which this construct belongs.
   */
  public function getTopicMap() {
    return $this->topicMap;
  }

  /**
   * Returns the identifier of this construct.
   * This property has no representation in the Topic Maps - Data Model (TMDM).
   *
   * @return string An identifier which identifies this construct uniquely within
   *        a topic map.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Returns the item identifiers of this Topic Maps construct.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the item identifiers.
   */
  public function getItemIdentifiers() {
    $iids = array();
    $query = 'SELECT locator FROM ' . $this->config['table']['itemidentifier'] . 
      ' WHERE topicmapconstruct_id = ' . $this->constructDbId;
    $mysqlResult = $this->mysql->execute($query);
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
  public function addItemIdentifier($iid) {
    if (!is_null($iid)) {
      // define insert stmnt only once
      $insert = 'INSERT INTO ' . $this->config['table']['itemidentifier'] . 
        ' (topicmapconstruct_id, locator) VALUES' .
        ' (' . $this->constructDbId . ', "' . $iid . '")';
      // check if given item identifier exists in topic map
      $query = 'SELECT t1.*' . 
        ' FROM ' . $this->config['table']['topicmapconstruct'] . ' t1' . 
        ' INNER JOIN ' . $this->config['table']['itemidentifier'] . ' t2' .
        ' ON t1.id = t2.topicmapconstruct_id' .
        ' WHERE t2.locator = "' . $iid . '"' . 
        ' AND t1.topicmap_id = ' . $this->topicMap->dbId;
      $mysqlResult = $this->mysql->execute($query);
      $rows = $mysqlResult->getNumRows();
      if ($rows == 0) {
        // if construct is a topic check sids too
        if ($this instanceof Topic) {
          $query = 'SELECT t2.id' .
            ' FROM ' . $this->config['table']['subjectidentifier'] . ' t1' .
            ' INNER JOIN ' . $this->config['table']['topic'] . ' t2' .
            ' ON t2.id = t1.topic_id' .
            ' WHERE t1.locator = "' . $iid . '"' .
            ' AND t2.topicmap_id = ' . $this->topicMap->dbId . 
            ' AND t1.topic_id <> ' . $this->dbId;
          $mysqlResult = $this->mysql->execute($query);
          $rows = $mysqlResult->getNumRows();
          if ($rows == 0) {
            $this->mysql->execute($insert);
            if (!$this->mysql->hasError()) {
              $this->postSave();
            }
          } else {// merge
            $result = $mysqlResult->fetch();
            $existingTopic = $this->topicMap
              ->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . $result['id']);
            $this->mergeIn($existingTopic);
          }
        } else {
          $this->mysql->execute($insert);
          if (!$this->mysql->hasError()) {
            $this->postSave();
          }
        }
      } else {// the given item identifier already exists
        $existing = $this->factory($mysqlResult);
        if ($existing instanceof Topic && $this instanceof Topic) {
          if (!$existing->equals($this)) {
            $this->mergeIn($existing);
          } else {
            return;
          }
        } else {
          throw new IdentityConstraintException($this, $existing, $iid, __METHOD__ . 
            self::ITEM_IDENTIFIER_EXISTS_ERR_MSG);
        }
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        TopicImpl::IDENTITY_NULL_ERR_MSG);
    }
  }

  /**
   * Removes an item identifier.
   *
   * @param string The item identifier to be removed from this construct, 
   *        if present (<var>null</var> is ignored).
   * @return void
   */
  public function removeItemIdentifier($iid) {
    if (!is_null($iid)) {
      $query = 'DELETE FROM ' . $this->config['table']['itemidentifier'] . 
        ' WHERE topicmapconstruct_id = ' . $this->constructDbId . 
        ' AND locator = "' . $iid . '"';
      $this->mysql->execute($query);
    } else {
      return;
    }
  }

  /**
   * Deletes a construct from its parent container. Each construct 
   * implements its own <var>remove()</var> method.
   * 
   * @return void
   */
  public function remove() {
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
  public function equals(Construct $other) {
    return $this->getId() === $other->getId() ? true : false;
  }

  /**
   * Returns a hash code value.
   * The returned hash code is equal to the hash code of the {@link getId()}
   * property.
   *
   * @return string
   */
  public function hashCode() {
    return md5($this->getId());
  }
  
  /**
   * Gets the construct's database <var>id</var>
   * 
   * @return int The database id.
   */
  public function getDbId() {
    $constituents = explode('-', $this->getId());
    return (int) $constituents[1];
  }
  
  /**
   * Gets the construct's fk column name.
   * 
   * @param string The class name.
   * @return string|null The fk column name or <var>null</var> if class name is unknown.
   */
  protected function getFkColumn($className) {
    switch ($className) {
      case TopicMapImpl::ASSOC_CLASS_NAME:
        return self::ASSOC_FK_COL;
      case AssociationImpl::ROLE_CLASS_NAME:
        return self::ROLE_FK_COL;
      case TopicImpl::OCC_CLASS_NAME:
        return self::OCC_FK_COL;
      case TopicMapImpl::TOPIC_CLASS_NAME:
        return self::TOPIC_FK_COL;
      case TopicMapImpl::TOPICMAP_CLASS_NAME:
        return self::TOPICMAP_FK_COL;
      case TopicImpl::NAME_CLASS_NAME:
        return self::NAME_FK_COL;
      case NameImpl::VARIANT_CLASS_NAME:
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
  protected function gainItemIdentifiers(Construct $other) {
    if ($this->className === $other->className) {
      // get other's item identifiers
      $query = $query = 'SELECT locator FROM ' . $this->config['table']['itemidentifier'] . 
        ' WHERE topicmapconstruct_id = ' . $other->constructDbId;
      $mysqlResult = $this->mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        // assign other's item identifiers
        $query = 'INSERT INTO ' . $this->config['table']['itemidentifier'] . 
          ' (topicmapconstruct_id, locator) VALUES' .
          ' (' . $this->constructDbId . ', "' . $result['locator'] . '")';
        $this->mysql->execute($query);
      }
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
  protected function gainReifier(Construct $other) {
    if ($this->className === $other->className) {
      if (!is_null($other->getReifier())) {
        if (is_null($this->getReifier())) {
          $query = 'UPDATE ' . $this->config['table']['topicmapconstruct'] . 
            ' SET reifier_id = ' . $other->getReifier()->dbId . 
            ' WHERE id = ' . $this->constructDbId;
          $this->mysql->execute($query);
        } else {// both constructs have reifiers
          $otherReifier = $other->getReifier();
          // prevent MCE, the other construct will be removed afterwards
          $other->setReifier(null);
          $this->getReifier()->mergeIn($otherReifier);
        }
      } else {
        return;
      }
    } else {
      return;
    }
  }
  
  /**
   * Gains the {@link VariantImpl}s of the other name.
   * 
   * @param NameImpl The other topic name.
   * @return void
   */
  protected function gainVariants(Name $other) {
    if ($this instanceof Name) {
      $otherVariants = $other->getVariants();
      foreach ($otherVariants as $otherVariant) {
        $variantId = $this->hasVariant($otherVariant->getHash());
        if (!$variantId) {// gain the variant
          $this->mysql->startTransaction();
          $query = 'UPDATE ' . $this->config['table']['variant'] . 
            ' SET topicname_id = ' . $this->dbId  .
            ' WHERE id = ' . $otherVariant->dbId;
          $this->mysql->execute($query);
          
          $query = 'UPDATE ' . $this->config['table']['topicmapconstruct'] . 
            ' SET parent_id = ' . $this->dbId  .
            ' WHERE variant_id = ' . $otherVariant->dbId;
          $this->mysql->execute($query);
          $this->mysql->finishTransaction();
          if (!$this->mysql->hasError()) {
            $this->postSave();
          }
        } else {// only gain variant's iids and reifier
          $this->topicMap->setConstructParent($this);
          $variant = $this->topicMap->getConstructById(NameImpl::VARIANT_CLASS_NAME . '-' . $variantId);
          $variant->gainItemIdentifiers($otherVariant);
          $variant->gainReifier($otherVariant);
          $variant->postSave();
        }
      }
    } else {
      return;
    }
  }
  
  /**
   * Builds a construct from one row of the topicmapconstruct table.
   * 
   * @param MysqlResult The query result object.
   * @return ConstructImpl|null A construct or <var>null</var> if no construct
   *        could be created.
   */
  protected function factory(MysqlResult $mysqlResult) {
    $result = $mysqlResult->fetch();
    if (!is_null($result['topic_id'])) {
      return $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
        $result['topic_id']);
    } elseif (!is_null($result['occurrence_id'])) {
      $parentTopic = $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . 
        '-' . $result['parent_id']);
      $this->topicMap->setConstructParent($parentTopic);
      return $this->topicMap->getConstructById(TopicImpl::OCC_CLASS_NAME . '-' . 
        $result['occurrence_id']);
    } elseif (!is_null($result['topicname_id'])) {
      $parentTopic = $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . 
        '-' . $result['parent_id']);
      $this->topicMap->setConstructParent($parentTopic);
      return $this->topicMap->getConstructById(TopicImpl::NAME_CLASS_NAME . '-' . 
        $result['topicname_id']);
    } elseif (!is_null($result['association_id'])) {
      return $this->topicMap->getConstructById(TopicMapImpl::ASSOC_CLASS_NAME . '-' . 
        $result['association_id']);
    } elseif (!is_null($result['assocrole_id'])) {
      $parentAssoc = $this->topicMap->getConstructById(TopicMapImpl::ASSOC_CLASS_NAME . 
        '-' . $result['parent_id']);
      $this->topicMap->setConstructParent($parentAssoc);
      return $this->topicMap->getConstructById(AssociationImpl::ROLE_CLASS_NAME . '-' . 
        $result['assocrole_id']);
    } elseif (!is_null($result['variant_id'])) {
      $parentTopicId = $this->getNameparentId($result['parent_id']);
      $parentTopic = $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . 
        '-' . $parentTopicId);
      $this->topicMap->setConstructParent($parentTopic);
      $parentName = $this->topicMap->getConstructById(TopicImpl::NAME_CLASS_NAME . 
        '-' . $result['parent_id']);
      $this->topicMap->setConstructParent($parentName);
      return $this->topicMap->getConstructById(NameImpl::VARIANT_CLASS_NAME . '-' . 
        $result['variant_id']);
    } elseif (!is_null($result['topicmap_id'])) {
      return $this->topicMap->getConstructById(TopicMapImpl::TOPICMAP_CLASS_NAME . '-' . 
        $result['topicmap_id']);
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
  protected function _getReifier() {
    $query = 'SELECT reifier_id FROM ' . $this->config['table']['topicmapconstruct'] . 
      ' WHERE id = ' . $this->constructDbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    if (!is_null($result['reifier_id'])) {
      return $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
        $result['reifier_id']);
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
  protected function _setReifier($reifier) {
    if ($reifier instanceof Topic) {
      if (!$this->topicMap->equals($reifier->topicMap)) {
        throw new ModelConstraintException($this, __METHOD__ . 
          self::SAME_TM_CONSTRAINT_ERR_MSG);
      }
      // check if reifier reifies another construct in this map
      $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['topicmapconstruct'] . 
        ' WHERE topicmap_id = ' . $this->topicMap->dbId . 
        ' AND reifier_id = ' . $reifier->dbId . 
        ' AND id <> ' . $this->constructDbId;
      $mysqlResult = $this->mysql->execute($query);
      $result = $mysqlResult->fetchArray();
      if ($result[0] == 0) {// no
        $query = 'UPDATE ' . $this->config['table']['topicmapconstruct'] . 
          ' SET reifier_id = ' . $reifier->dbId . 
          ' WHERE id = ' . $this->constructDbId;
        $this->mysql->execute($query);
        if (!$this->mysql->hasError()) {
          $this->postSave();
        }
      } else {
        throw new ModelConstraintException($this, __METHOD__ . self::REIFIER_ERR_MSG);
      }
    } elseif (is_null($reifier)) {// unset reifier
      $query = 'UPDATE ' . $this->config['table']['topicmapconstruct'] . 
        ' SET reifier_id = NULL' .
        ' WHERE id = ' . $this->constructDbId;
      $this->mysql->execute($query);
      if (!$this->mysql->hasError()) {
        $this->postSave();
      }
    } else {
      return;
    }
  }
  
  /**
   * Generates a true set from given array containing {@link Construct}s.
   * 
   * Note: This could also be done with SQL, however we want to avoid expensive
   * temp. tables when using DISTINCT or GROUP BY.
   * 
   * @param array An array containing {@link Construct}s.
   * @return array
   */
  protected function arrayToSet(array $array) {
    $set = array();
    foreach ($array as $element) {
      if ($element instanceof Construct) {
        $set[$element->getId()] = $element;
      }
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
  protected function postInsert(array $params=array()) {}
  
  /**
   * Post save hook for e.g. updating cache or search index.
   * It is guaranteed that this hook is only called of no MySQL error occurred.
   * 
   * @param array Optional parameters.
   * @return void
   */
  protected function postSave(array $params=array()) {}
  
  /**
   * Pre delete hook for e.g. cache or search index drop.
   * 
   * @param array Optional parameters.
   * @return void
   */
  protected function preDelete(array $params=array()) {}
  
  /**
   * Gets the construct's topicmapconstruct table <var>id</var>.
   * {@link TopicMapImpl} implements its own <var>getConstructDbId()</var>.
   * 
   * @return int The id.
   */
  private function getConstructDbId() {
    $query = 'SELECT id FROM ' . $this->config['table']['topicmapconstruct'] . 
      ' WHERE ' . $this->fkColumn . ' = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return (int) $result['id'];
  }
  
  /**
   * Gets the parent (topic) id of the given name (id).
   * 
   * @param string The name id.
   * @return string The parent (topic) id.
   */
  private function getNameparentId($nameId) {
    $query = 'SELECT parent_id FROM ' . $this->config['table']['topicmapconstruct'] . 
      ' WHERE topicname_id = ' . $nameId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $result['parent_id'];
  }
}
?>