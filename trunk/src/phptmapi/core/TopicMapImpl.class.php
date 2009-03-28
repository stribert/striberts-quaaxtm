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
 * Represents a topic map item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#d0e657}.
 *
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns <var>null</var>.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class TopicMapImpl extends ConstructImpl implements TopicMap {

  const TOPICMAP_CLASS_NAME = __CLASS__,
        TOPIC_CLASS_NAME = 'TopicImpl',
        ASSOC_CLASS_NAME = 'AssociationImpl';

  private $setIid,
          $constructParent,
          $topicsCache,
          $assocsCache;
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config) {
    parent::__construct(__CLASS__ . '-' . $dbId, null, $mysql, $config, $this);
    $this->setIid = true;
    $this->constructParent = null;
    $this->constructDbId = $this->getConstructDbId();
    $this->topicsCache = $this->assocsCache = null;
  }

  /**
   * Returns all {@link TopicImpl}s contained in this topic map.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getTopics() {
    $topics = array();
    if (is_null($this->topicsCache)) {
      $query = 'SELECT id FROM ' . $this->config['table']['topic'] . 
        ' WHERE topicmap_id = ' . $this->dbId;
      $mysqlResult = $this->mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {    
        $topic = $this->getConstructById(self::TOPIC_CLASS_NAME . '-' . $result['id']);
        $topics[] = $topic;
      }
      $this->topicsCache = $topics;
    } else {
      $topics = $this->topicsCache;
    }
    return $topics;
  }

  /**
   * Returns all {@link AssociationImpl}s contained in this topic map.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing {@link AssociationImpl}s.
   */
  public function getAssociations() {
    $assocs = array();
    if (is_null($this->assocsCache)) {
      $query = 'SELECT id FROM ' . $this->config['table']['association'] . 
        ' WHERE topicmap_id = ' . $this->dbId;
      $mysqlResult = $this->mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {    
        $assoc = $this->getConstructById(self::ASSOC_CLASS_NAME . '-' . $result['id']);
        $assocs[] = $assoc;
      }
      $this->assocsCache = $assocs;
    } else {
      var_dump('assocs from cache');
      $assocs = $this->assocsCache;
    }
    return $assocs;
  }
  
  /**
   * Returns the associations in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param TopicImpl The type of the {@link AssociationImpl}s to be returned.
   * @return array An array containing {@link AssociationImpl}s.
   */
  public function getAssociationsByType(Topic $type) {
    $assocs = array();
    $query = 'SELECT id FROM ' . $this->config['table']['association'] . 
      ' WHERE type_id = ' . $type->dbId  . 
      ' AND topicmap_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {    
      $assoc = $this->getConstructById(self::ASSOC_CLASS_NAME . '-' . $result['id']);
      $assocs[] = $assoc;
    }
    return $assocs;
  }

  /**
   * Returns a topic by its subject identifier.
   * If no topic with the specified subject identifier exists, this method
   * returns <var>null</var>.
   * 
   * @param string The subject identifier of the topic to be returned.
   * @return TopicImpl|null A topic with the specified subject identifier or <var>null</var>
   *        if no such topic exists in the topic map.
   */
  public function getTopicBySubjectIdentifier($sid) {
    $query = 'SELECT t1.id FROM '.$this->config['table']['topic'].' AS t1' .
      ' INNER JOIN ' . $this->config['table']['subjectidentifier'].' t2' .
      ' ON t1.id = t2.topic_id' .
      ' WHERE t2.locator = "' . $sid . '"' .
      ' AND t1.topicmap_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return $this->getConstructById(self::TOPIC_CLASS_NAME . '-' . $result['id']);
    } else {
      return null;
    }
  }

  /**
   * Returns a topic by its subject locator.
   * If no topic with the specified subject locator exists, this method
   * returns <var>null</var>.
   * 
   * @param string The subject locator of the topic to be returned.
   * @return TopicImpl|null A topic with the specified subject locator or <var>null</var>
   *        if no such topic exists in the topic map.
   */
  public function getTopicBySubjectLocator($slo) {
    $query = 'SELECT t1.id FROM '.$this->config['table']['topic'].' AS t1' .
      ' INNER JOIN ' . $this->config['table']['subjectlocator'].' t2' .
      ' ON t1.id = t2.topic_id' .
      ' WHERE t2.locator = "' . $slo . '"' .
      ' AND t1.topicmap_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return $this->getConstructById(self::TOPIC_CLASS_NAME . '-' . $result['id']);
    } else {
      return null;
    }
  }

  /**
   * Returns a {@link ConstructImpl} by its item identifier.
   * If no construct with the specified item identifier exists, this method
   * returns <var>null</var>.
   *
   * @param string The item identifier of the construct to be returned.
   * @return ConstructImpl|null A construct with the specified item identifier or 
   *        <var>null</var> if no such construct exists in the topic map.
   */
  public function getConstructByItemIdentifier($iid) {
    $query = 'SELECT t1.*' .
      ' FROM ' . $this->config['table']['topicmapconstruct'] . ' t1' .
      ' INNER JOIN ' . $this->config['table']['itemidentifier'] . ' t2' .
      ' ON t1.id = t2.topicmapconstruct_id' .
      ' WHERE t2.locator = "' . $iid . '"' .
      ' AND t1.topicmap_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      return $this->factory($mysqlResult);
    } else {
      return null;
    }
  }

  /**
   * Returns a {@link ConstructImpl} by its (system specific) identifier.
   * If no construct with the specified identifier exists, this method
   * returns <var>null</var>.
   *
   * @param string The identifier of the construct to be returned.
   * @return ConstructImpl|null The construct with the specified id or <var>null</var> 
   *        if such a construct is unknown.
   */
  public function getConstructById($id) {
    if ($this->contains($id)) {
      $constituents = explode('-', $id);
      $className = $constituents[0];
      $dbId = $constituents[1];
      switch ($className) {
        case self::TOPIC_CLASS_NAME:
          return new $className($dbId, $this->mysql, $this->config, $this);
          break;
        case TopicImpl::NAME_CLASS_NAME:
          if ($this->constructParent instanceof Topic) {
            $construct = new $className($dbId, $this->mysql, $this->config, 
              $this->constructParent, $this);
            $this->constructParent = null;
            return $construct;
          } else {
            throw new PHPTMAPIRuntimeException(__METHOD__ . ': No parent defined for ' .
              $className . '!');
          }
          break;
        case self::ASSOC_CLASS_NAME:
          return new $className($dbId, $this->mysql, $this->config, $this);
          break;
        case AssociationImpl::ROLE_CLASS_NAME:
          if ($this->constructParent instanceof Association) {
            $construct = new $className($dbId, $this->mysql, $this->config, 
              $this->constructParent, $this);
            $this->constructParent = null;
            return $construct;
          } else {
            throw new PHPTMAPIRuntimeException(__METHOD__ . ': No parent defined for ' .
              $className . '!');
          }
          break;
        case TopicImpl::OCC_CLASS_NAME:
          if ($this->constructParent instanceof Topic) {
            $construct = new $className($dbId, $this->mysql, $this->config, 
              $this->constructParent, $this);
            $this->constructParent = null;
            return $construct;
          } else {
            throw new PHPTMAPIRuntimeException(__METHOD__ . ': No parent defined for ' .
              $className . '!');
          }
          break;
        case NameImpl::VARIANT_CLASS_NAME:
          if ($this->constructParent instanceof Name) {
            $construct = new $className($dbId, $this->mysql, $this->config, 
              $this->constructParent, $this);
            $this->constructParent = null;
            return $construct;
          } else {
            throw new PHPTMAPIRuntimeException(__METHOD__ . ': No parent defined for ' .
              $className . '!');
          }
          break;
        case __CLASS__:
          return new $className($dbId, $this->mysql, $this->config);
          break;
      }
    } else {
      return null;
    }
  }

  /**
   * Creates an {@link AssociationImpl} in this topic map with the specified 
   * <var>type</var> and <var>scope</var>. 
   *
   * @param TopicImpl The association type.
   * @param array An array containing {@link TopicImpl}s - each representing a theme.
   *        If the array's length is 0 (default), the association will be in the 
   *        unconstrained scope.
   * @return AssociationImpl The newly created {@link AssociationImpl}.
   */
  public function createAssociation(Topic $type, array $scope=array()) {
    $hash = $this->getAssocHash($type, $scope, $roles=array());
    $assocId = $this->hasAssoc($hash);
    if (!$assocId) {
      $this->mysql->startTransaction(true);
      $query = 'INSERT INTO ' . $this->config['table']['association'] . 
        ' (id, type_id, topicmap_id, hash) VALUES' .
        ' (NULL, ' . $type->dbId . ', ' . $this->dbId . ', "' . $hash . '")';
      $mysqlResult = $this->mysql->execute($query);
      $lastAssocId = $mysqlResult->getLastId();
      
      $query = 'INSERT INTO ' . $this->config['table']['topicmapconstruct'] . 
        ' (association_id, topicmap_id, parent_id) VALUES' .
        ' (' . $lastAssocId . ', '.$this->dbId . ', ' . $this->dbId . ')';
      $this->mysql->execute($query);
      
      $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope);
      $query = 'INSERT INTO ' . $this->config['table']['association_scope'] . 
        ' (scope_id, association_id) VALUES' .
        ' (' . $scopeObj->dbId . ', ' . $lastAssocId . ')';
      $this->mysql->execute($query);
      
      $this->mysql->finishTransaction(true);
      $assoc = $this->getConstructById(self::ASSOC_CLASS_NAME . '-' . $lastAssocId);
      if (is_null($this->assocsCache)) {
        return $assoc;
      } else {
        $this->assocsCache[] = $assoc;
        return $assoc;
      }
    } else {
      return $this->getConstructById(self::ASSOC_CLASS_NAME . '-' . $assocId);
    }
  }

  /**
   * Returns a {@link TopicImpl} instance with the specified item identifier.
   * This method returns either an existing {@link TopicImpl} or creates a new
   * {@link TopicImpl} instance with the specified item identifier.
   * 
   * If a topic with the specified item identifier exists in the topic map,
   * that topic is returned. If a topic with a subject identifier equals to
   * the specified item identifier exists, the specified item identifier
   * is added to that topic and the topic is returned.
   * If neither a topic with the specified item identifier nor with a
   * subject identifier equals to the subject identifier exists, a topic with
   * the item identifier is created.
   *
   * @param string The item identifier the topic should contain; must not be <var>null</var>.
   * @return TopicImpl A {@link TopicImpl} instance with the specified item identifier.
   * @throws {@link ModelConstraintException} If the item identifier <var>iid</var> is 
   *        <var>null</var>.
   * @throws {@link IdentityConstraintException} If another {@link ConstructImpl} with the
   *        specified item identifier exists which is not a {@link TopicImpl}.
   */
  public function createTopicByItemIdentifier($iid) {
    if (!is_null($iid)) {
      $construct = $this->getConstructByItemIdentifier($iid);
      if (!is_null($construct)) {
        if ($construct instanceof Topic) {
          return $construct;
        } else {
          throw new IdentityConstraintException($this, $construct, $iid, __METHOD__ . 
            ConstructImpl::ITEM_IDENTIFIER_EXISTS_ERR_MSG);
        }
      } else {
        $topic = $this->getTopicBySubjectIdentifier($iid);
        if (!is_null($topic)) {
          $topic->addItemIdentifier($iid);
          return $topic;
        } else {
          $this->setIid = false;
          $this->mysql->startTransaction(true);
          $topic = $this->createTopic();
          $topic->addItemIdentifier($iid);
          $this->mysql->finishTransaction(true);
          return $topic;
        }
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ .
        TopicImpl::IDENTITY_NULL_ERR_MSG);
    }
  }

  /**
   * Returns a {@link TopicImpl} instance with the specified subject identifier.
   * This method returns either an existing {@link TopicImpl} or creates a new
   * {@link TopicImpl} instance with the specified subject identifier.
   * 
   * If a topic with the specified subject identifier exists in the topic map,
   * that topic is returned. If a topic with an item identifier equals to
   * the specified subject identifier exists, the specified subject identifier
   * is added to that topic and the topic is returned.
   * If neither a topic with the specified subject identifier nor with an
   * item identifier equals to the subject identifier exists, a topic with
   * the subject identifier is created.
   * 
   * @param string The subject identifier the topic should contain; must not be <var>null</var>.
   * @return TopicImpl A {@link TopicImpl} instance with the specified subject identifier.
   * @throws {@link ModelConstraintException} If the subject identifier <var>sid</var> 
   *        is <var>null</var>.
   */
  public function createTopicBySubjectIdentifier($sid) {
    if (!is_null($sid)) {
      $topic = $this->getTopicBySubjectIdentifier($sid);
      if (!is_null($topic)) {
        return $topic;
      } else {
        $construct = $this->getConstructByItemIdentifier($sid);
        if ($construct instanceof Topic) {
          // add subject identifier to this topic
          $construct->addSubjectIdentifier($sid);
          return $construct;
        } else {// create new topic
          $this->setIid = false;
          $this->mysql->startTransaction(true);
          $topic = $this->createTopic();
          // add subject identifier to this topic
          $topic->addSubjectIdentifier($sid);
          $this->mysql->finishTransaction(true);
          return $topic;
        }
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ .
        TopicImpl::IDENTITY_NULL_ERR_MSG);
    }
  }

  /**
   * Returns a {@link TopicImpl} instance with the specified subject locator.
   * This method returns either an existing {@link TopicImpl} or creates a new
   * {@link TopicImpl} instance with the specified subject locator.
   * 
   * @param string The subject locator the topic should contain; must not be <var>null</var>.
   * @return TopicImpl A {@link TopicImpl} instance with the specified subject locator.
   * @throws {@link ModelConstraintException} If the subject locator <var>slo</var> 
   *        is <var>null</var>.
   */
  public function createTopicBySubjectLocator($slo) {
    if (!is_null($slo)) {
      $topic = $this->getTopicBySubjectLocator($slo);
      if (!is_null($topic)) {
        return $topic;
      } else {// create new topic
        $this->setIid = false;
        $this->mysql->startTransaction(true);
        $topic = $this->createTopic();
        // add subject locator to this topic
        $topic->addSubjectLocator($slo);
        $this->mysql->finishTransaction(true);
        return $topic;
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ .
        TopicImpl::IDENTITY_NULL_ERR_MSG);
    }
  }

  /**
   * Returns a {@link TopicImpl} instance with an automatically generated item 
   * identifier.
   * 
   * This method returns never an existing {@link Topic} but creates a 
   * new one with an automatically generated item identifier.
   * How that item identifier is generated depends on the implementation.
   *
   * @return TopicImpl The newly created {@link TopicImpl} instance with an automatically 
   *        generated item identifier.
   */
  public function createTopic() {
    $this->mysql->startTransaction();
    $query = 'INSERT INTO ' . $this->config['table']['topic'] . 
      ' (id, topicmap_id) VALUES (NULL, ' . $this->dbId . ')';
    $mysqlResult = $this->mysql->execute($query);
    $lastTopicId = $mysqlResult->getLastId();
    
    $query = 'INSERT INTO ' . $this->config['table']['topicmapconstruct'] . 
      ' (topic_id, topicmap_id, parent_id) VALUES' .
      ' (' . $lastTopicId . ', ' . $this->dbId . ', ' . $this->dbId . ')';
    $this->mysql->execute($query);
    $lastConstructId = $mysqlResult->getLastId();
    
    if ($this->setIid) {
      $iidFragment = '#topic' . $lastTopicId;
      $query = 'INSERT INTO ' . $this->config['table']['itemidentifier'] . 
        ' (topicmapconstruct_id, locator) VALUES' .
        ' (' . $lastConstructId . ', "' . $iidFragment . '")';
      $this->mysql->execute($query);
    }
    $this->mysql->finishTransaction();
    $topic = $this->getConstructById(self::TOPIC_CLASS_NAME . '-' . $lastTopicId);
    if (is_null($this->topicsCache)) {
      return $topic;
    } else {
      $this->topicsCache[] = $topic;
      return $topic;
    }
  }

  /**
   * Merges the topic map <var>other</var> into this topic map.
   * 
   * All {@link TopicImpl}s and {@link AssociationImpl}s and all of their contents in
   * <var>other</var> will be added to this topic map.
   * 
   * All information items in <var>other</var> will be merged into this 
   * topic map as defined by the Topic Maps - Data Model (TMDM) merging rules.
   * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-merging}.
   * 
   * The merge process will not modify <var>other</var> in any way.
   * 
   * If <var>$this->equals($other)</var> no changes are made to the topic map.
   * 
   * @param TopicMapImpl The topic map to be merged with this topic map instance.
   * @return void
   */
  public function mergeIn(TopicMap $other) {
    if (!$this->equals($other)) {
      $otherTopics = $other->getTopics();
      foreach ($otherTopics as $otherTopic) {
        $merge = false;
        // check by subject identifiers
        $sids = $otherTopic->getSubjectIdentifiers();
        foreach ($sids as $sid) {
          $topic = $this->getTopicBySubjectIdentifier($sid);
          if ($topic) {
            $topic->mergeIn($otherTopic);
            $merge = true;
            break;
          }
          // check by item identifiers too
          $construct = $this->getConstructByItemIdentifier($sid);
          if ($construct instanceof Topic) {
            $construct->mergeIn($otherTopic);
            $merge = true;
            break;
          }
        }
        
        if (!$merge) {
          // not merged; check by item identifiers
          $iids = $otherTopic->getItemIdentifiers();
          foreach ($iids as $iid) {
            $construct = $this->getConstructByItemIdentifier($iid);
            if ($construct instanceof Topic) {
              $construct->mergeIn($otherTopic);
              $merge = true;
              break;
            }
            // check by subject identifiers too
            $topic = $this->getTopicBySubjectIdentifier($iid);
            if ($topic) {
              $topic->mergeIn($otherTopic);
              $merge = true;
              break;
            }
          }
        }
        
        if (!$merge) {
          // not merged; check by subject locators
          $slos = $otherTopic->getSubjectLocators();
          foreach ($slos as $slo) {
            $topic = $this->getTopicBySubjectIdentifier($slo);
            if ($topic) {
              $topic->mergeIn($otherTopic);
              $merge = true;
              break;
            }
          }
        }
        
        if (!$merge) {
          // not merged; add other topic to this topic map
          $this->mysql->startTransaction();
          
          $query = 'UPDATE ' . $this->config['table']['topic'] . 
            ' SET topicmap_id = ' . $this->dbId . 
            ' WHERE id = ' . $otherTopic->dbId;
          $this->mysql->execute($query);
          
          $query = 'UPDATE ' . $this->config['table']['topicmapconstruct'] . 
            ' SET topicmap_id = ' . $this->dbId . ',' . 
            ' parent_id = ' . $this->dbId . 
            ' WHERE topic_id = ' . $otherTopic->dbId;
          $this->mysql->execute($query);
          
          $this->mysql->finishTransaction();
        }
      }
      
      // gain or merge reifier
      $query = 'SELECT reifier_id FROM ' . $this->config['table']['topicmapconstruct'] . 
        ' WHERE topicmap_id = ' . $other->dbId . 
        ' AND parent_id IS NULL';
      $mysqlResult = $this->mysql->execute($query);
      $result = $mysqlResult->fetch();
      $reifierIdToMerge = !is_null($result['reifier_id']) ? $result['reifier_id'] : null;
      if (!is_null($reifierIdToMerge)) {
        $otherReifier = $this->getConstructById(self::TOPIC_CLASS_NAME . '-' . 
          $reifierIdToMerge);
        if (!is_null($this->getReifier())) {// merge
          $this->getReifier()->mergeIn($otherReifier);
        } else {// set other reifier
          $this->setReifier($otherReifier);
        }
      }
      
      // other assocs
      $otherAssocs = $other->getAssociations();
      foreach ($otherAssocs as $otherAssoc) {
          $this->mysql->startTransaction();
          
          $query = 'UPDATE ' . $this->config['table']['association'] . 
            ' SET topicmap_id = ' . $this->dbId . 
            ' WHERE id = ' . $otherAssoc->dbId;
          $this->mysql->execute($query);
          
          $query = 'UPDATE ' . $this->config['table']['topicmapconstruct'] . 
            ' SET topicmap_id = ' . $this->dbId . ',' . 
            ' parent_id = ' . $this->dbId . 
            ' WHERE association_id = ' . $otherAssoc->dbId;
          $this->mysql->execute($query);
          
          $this->mysql->finishTransaction();
      }
      
      // remove other topic map
      $other->remove();
    } else {
      return;
    }
  }

  /**
   * Returns the specified index.
   *
   * @param string The classname of the index.
   * @return Index An index.
   * @throws FeatureNotSupportedException If the implementation does not support indices, 
   *        if the specified index is unsupported, or if the specified index does not exist.
   */
  public function getIndex($className) {
    throw new FeatureNotSupportedException(__METHOD__ . 
      ': Indices are not supported yet!');
  }

  /**
   * Closes use of this topic map instance. 
   * This method should be invoked by the application once it has finished 
   * using this topic map instance.
   * Implementations may release any resources required for the 
   * {@link TopicMapImpl} instance or any of the {@link ConstructImpl} instances 
   * contained by this instance.
   * 
   * @return void
   */
  public function close() {
    $this->topicsCache = $this->assocsCache = null;
  }
  
  /**
   * Returns the reifier of this construct.
   * 
   * @return TopicImpl The topic that reifies this construct or
   *        <var>null</var> if this construct is not reified.
   */
  public function getReifier() {
    return $this->_getReifier();
  }

  /**
   * Sets the reifier of this topic map.
   * The specified <var>reifier</var> MUST NOT reify another information item.
   *
   * @param TopicImpl|null The topic that should reify this topic map or null
   *        if an existing reifier should be removed.
   * @return void
   * @throws {@link ModelConstraintException} If the specified <var>reifier</var> 
   *        reifies another construct.
   */
  public function setReifier($reifier) {
    $this->_setReifier($reifier);
  }
  
  /**
   * Sets the construct's parent.
   * 
   * @param ConstructImpl
   * @return void
   */
  public function setConstructParent(Construct $parent) {
    $this->constructParent = $parent;
  }
  
  /**
   * Gets the association hash.
   * 
   * @param TopicImpl The association type.
   * @param array The scope.
   * @param array The roles.
   * @return string
   */
  public function getAssocHash(Topic $type, array $scope, array $roles) {
    $scopeIdsImploded = null;
    $roleIdsImploded = null;
    if (count($scope) > 0) {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) $ids[$theme->dbId] = $theme->dbId;
      }
      ksort($ids);
      $scopeIdsImploded = implode('', $ids);
    }
    if (count($roles) > 0) {
      $ids = array();
      foreach ($roles as $role) {
        if ($role instanceof Role) {
          $ids[$role->getType()->dbId . $role->getPlayer()->dbId] = $role->getType()->dbId . $role->getPlayer()->dbId; 
        }
      }
      ksort($ids);
      $roleIdsImploded = implode('', $ids);
    }
    return md5($type->dbId . $scopeIdsImploded . $roleIdsImploded);
  }
  
  /**
   * Updates association hash.
   * 
   * @param int The association id.
   * @param string The association hash.
   */
  public function updateAssocHash($assocId, $hash) {
    $query = 'UPDATE ' . $this->config['table']['association'] . 
      ' SET hash = "' . $hash . '"' .
      ' WHERE id = ' . $assocId;
    $this->mysql->execute($query);
  }
  
  /**
   * Checks if topic map has a certain association.
   * 
   * @param string The hash code.
   * @return false|int The association id or <var>false</var> otherwise.
   */
  public function hasAssoc($hash) {
    $return = false;
    $query = 'SELECT id FROM ' . $this->config['table']['association'] . 
      ' WHERE topicmap_id = ' . $this->dbId . 
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
   * Tells the topic map system that an association modification is finished and 
   * duplicate removal can take place.
   * 
   * NOTE: This may be a resource consuming process.
   * 
   * @param AssociationImpl The modified association.
   * @return void
   */
  public function finished(Association $assoc) {
    // get the hash of the finished association
    $query = 'SELECT hash FROM ' . $this->config['table']['association'] . 
      ' WHERE id = ' . $assoc->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    // detect duplicates
    $query = 'SELECT id FROM ' . $this->config['table']['association'] . 
      ' WHERE hash = "' . $result['hash'] . '"' .
      ' AND id <> ' . $assoc->dbId . 
      ' AND topicmap_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {// there exist duplicates
      while ($result = $mysqlResult->fetch()) {
        $duplicate = $this->getConstructById(self::ASSOC_CLASS_NAME . '-' . 
          $result['id']);
        // gain duplicate's item identities
        $assoc->gainItemIdentifiers($duplicate);
        // gain duplicate's reifier
        $assoc->gainReifier($duplicate);
        $duplicate->remove();
      }
    }
  }
  
  /**
   * Deletes this topic map.
   * 
   * @override
   * @return void
   */
  public function remove() {
    $this->mysql->startTransaction();
    
    // topic names
    $query = 'DELETE t1.*, t2.* FROM ' . $this->config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->config['table']['topicname'] . ' t2 ' .
      'ON t2.id = t1.topicname_id ' .
      'WHERE t1.topicmap_id = ' . $this->dbId;
    $this->mysql->execute($query);
    
    // occurrences
    $query = 'DELETE t1.*, t2.* FROM ' . $this->config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->config['table']['occurrence'] . ' t2 ' .
      'ON t2.id = t1.occurrence_id ' .
      'WHERE t1.topicmap_id = ' . $this->dbId;
    $this->mysql->execute($query);
    
    // associations; cascades to roles
    $query = 'DELETE t1.*, t2.* FROM ' . $this->config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->config['table']['association'] . ' t2 ' .
      'ON t2.id = t1.association_id ' .
      'WHERE t1.topicmap_id = ' . $this->dbId;
    $this->mysql->execute($query);
    
    // typed topics (instanceof)
    $query = 'DELETE t1.*, t2.* FROM ' . $this->config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->config['table']['instanceof'] . ' t2 ' .
      'ON t2.topic_id = t1.topic_id ' .
      'WHERE t1.topicmap_id = ' . $this->dbId;
    $this->mysql->execute($query);
    
    // scope and themes
    $query = 'DELETE t1.*, t2.*, t3.* FROM ' . $this->config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->config['table']['theme'] . ' t2 ' .
      'ON t2.topic_id = t1.topic_id ' . 
      'INNER JOIN ' . $this->config['table']['scope'] . ' t3 ' . 
      'ON t3.id = t2.scope_id ' . 
      'WHERE t1.topicmap_id = ' . $this->dbId;
    $this->mysql->execute($query);
    
    // unset topic map's reifier to prevent fk constraint errors
    $query = 'UPDATE ' . $this->config['table']['topicmapconstruct'] . 
      ' SET reifier_id = NULL' . 
      ' WHERE topicmap_id = ' . $this->dbId . 
      ' AND parent_id IS NULL';
    $this->mysql->execute($query);
    
    // topics; cascades to identifiers
    $query = 'DELETE t1.*, t2.* FROM ' . $this->config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->config['table']['topic'] . ' t2 ' .
      'ON t2.id = t1.topic_id ' .
      'WHERE t1.topicmap_id = ' . $this->dbId;
    $this->mysql->execute($query);
    
    // the topic map
    $query = 'DELETE FROM ' . $this->config['table']['topicmap'] .
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
    
    // clean up scope (an ucs may be left) and truncate all tables if no topic map exists anymore
    $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['topicmap'];
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    if ($result[0] == 0) {
      $query = 'TRUNCATE TABLE ' . $this->config['table']['scope'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['association'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['association_scope'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['assocrole'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['instanceof'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['occurrence'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['occurrence_scope'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['theme'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['subjectidentifier'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['subjectlocator'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['topic'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['topicmap'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['topicmapconstruct'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['itemidentifier'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['topicname'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['topicname_scope'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['variant'];
      $this->mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->config['table']['variant_scope'];
      $this->mysql->execute($query);
    }
    
    $this->mysql->finishTransaction();
    
    if (!$this->mysql->hasError()) {
      $this->id = null;
      $this->dbId = null;
    }
  }
  
  /**
   * Gets the construct's topicmapconstruct table <var>id</var>.
   * 
   * @return int The id.
   * @override
   */
  private function getConstructDbId() {
    $query = 'SELECT id FROM ' . $this->config['table']['topicmapconstruct'] . 
      ' WHERE topicmap_id = ' . $this->dbId . 
      ' AND parent_id IS NULL';
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return (int) $result['id'];
  }
  
  /**
   * Checks if this topic map contains a construct with the given id.
   * 
   * @param string The construct's id retrieved by {@link ConstructImpl::getId()}
   * @return boolean
   */
  private function contains($id) {
    $constituents = explode('-', $id);
    $className = $constituents[0];
    $dbId = $constituents[1];
    if (!empty($dbId)) {
      $fkColumn = $this->getFkColumn($className);
      if (!is_null($fkColumn)) {
        if ($fkColumn != ConstructImpl::TOPICMAP_FK_COL) {
          $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['topicmapconstruct'] . 
            ' WHERE ' . $fkColumn . ' = ' . $dbId . 
            ' AND topicmap_id = ' . $this->dbId;
        } else {
          $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['topicmapconstruct'] . 
            ' WHERE topicmap_id = ' . $dbId . 
            ' AND parent_id IS NULL';
        }
        $mysqlResult = $this->mysql->execute($query);
        $result = $mysqlResult->fetchArray();
        return (int) $result[0] > 0 ? true : false;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
}
?>