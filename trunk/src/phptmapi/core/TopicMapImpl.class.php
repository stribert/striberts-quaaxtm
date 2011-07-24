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
 * Represents a topic map item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#d0e657}.
 *
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns <var>null</var>.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class TopicMapImpl extends ConstructImpl implements TopicMap
{
  protected $_seenConstructsCache;
  
  private $_setIid,
          $_constructParent,
          $_constructPropertyHolder,
          $_topicsCache,
          $_assocsCache,
          $_tmSystem,
          $_locator,
          $_topicMapState;
          
  private static $_supportedIndices = array(
  	'TypeInstanceIndexImpl', 
    'LiteralIndexImpl', 
    'ScopedIndexImpl'
  );
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicMapSystem The underlying topic map system.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, TopicMapSystem $tmSystem)
  {  
    parent::__construct(__CLASS__ . '-' . $dbId, null, $mysql, $config, null);
    
    $this->_setIid = true;
    $this->_constructDbId = $this->_getConstructDbId();
    $this->_tmSystem = $tmSystem;
    $this->_constructParent = 
    $this->_constructPropertyHolder = 
    $this->_topicsCache = 
    $this->_assocsCache = 
    $this->_locator = null;
    $this->_seenConstructsCache = array();
    $this->_topicMapState = VocabularyUtils::QTM_STATE_REGULAR;
  }
  
  /**
   * Returns the underlying {@link TopicMapSystemImpl}.
   * 
   * @return TopicMapSystemImpl
   */
  public function getTopicMapSystem()
  {
    return $this->_tmSystem;
  }
  
  /**
   * Returns the storage address that is defined in 
   * {@link TopicMapSystemImpl::createTopicMap()}.
   * 
   * @return string A URI which is the storage address of the {@link TopicMapImpl}.
   */
  public function getLocator()
  {
    if (!is_null($this->_locator)) {
      return $this->_locator;
    }
    $query = 'SELECT locator FROM ' . $this->_config['table']['topicmap'] . 
      ' WHERE id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->_locator = $result['locator'];
  }

  /**
   * Returns all {@link TopicImpl}s contained in this topic map.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link TopicImpl}s.
   */
  public function getTopics()
  {
    if (is_null($this->_topicsCache)) {
      $this->_topicsCache = array();
      $query = 'SELECT id FROM ' . $this->_config['table']['topic'] . 
        ' WHERE topicmap_id = ' . $this->_dbId;
      $mysqlResult = $this->_mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $topic = $this->_getConstructByVerifiedId('TopicImpl-' . $result['id']);
        $this->_topicsCache[$topic->getId()] = $topic;
      }
      return array_values($this->_topicsCache);
    } else {
      return array_values($this->_topicsCache);
    }
  }

  /**
   * Returns all {@link AssociationImpl}s contained in this topic map.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link AssociationImpl}s.
   */
  public function getAssociations()
  {
    if (is_null($this->_assocsCache)) {
      $this->_assocsCache = 
      $assocsHashes = array();
      $query = 'SELECT id, type_id, hash FROM ' . $this->_config['table']['association'] . 
        ' WHERE topicmap_id = ' . $this->_dbId;
      $mysqlResult = $this->_mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {    
        $propertyHolder['type_id'] = $result['type_id'];
        $this->_setConstructPropertyHolder($propertyHolder);
        $assoc = $this->_getConstructByVerifiedId('AssociationImpl-' . $result['id']);
        if (!array_key_exists($result['hash'], $assocsHashes)) {
          $this->_assocsCache[$assoc->getId()] = $assoc;
        }
        $assocsHashes[$result['hash']] = null;
      }
      unset($assocsHashes);
      return array_values($this->_assocsCache);
    } else {
      return array_values($this->_assocsCache);
    }
  }
  
  /**
   * Returns the associations in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param TopicImpl The type of the {@link AssociationImpl}s to be returned.
   * @return array An array containing a set of {@link AssociationImpl}s.
   */
  public function getAssociationsByType(Topic $type)
  {
    $assocs = array();
    $query = 'SELECT id, type_id, hash FROM ' . $this->_config['table']['association'] . 
      ' WHERE type_id = ' . $type->_dbId  . 
      ' AND topicmap_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {    
      $propertyHolder['type_id'] = $result['type_id'];
      $this->_setConstructPropertyHolder($propertyHolder);
      $assoc = $this->_getConstructByVerifiedId('AssociationImpl-' . $result['id']);
      $assocs[$result['hash']] = $assoc;
    }
    return array_values($assocs);
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
  public function getTopicBySubjectIdentifier($sid)
  {
    $query = 'SELECT t1.id FROM ' . $this->_config['table']['topic'] . ' AS t1' .
      ' INNER JOIN ' . $this->_config['table']['subjectidentifier'] . ' t2' .
      ' ON t1.id = t2.topic_id' .
      ' WHERE t2.locator = "' . $sid . '"' .
      ' AND t1.topicmap_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return $this->_getConstructByVerifiedId('TopicImpl-' . $result['id']);
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
  public function getTopicBySubjectLocator($slo)
  {
    $query = 'SELECT t1.id FROM '.$this->_config['table']['topic'] . ' AS t1' .
      ' INNER JOIN ' . $this->_config['table']['subjectlocator'] . ' t2' .
      ' ON t1.id = t2.topic_id' .
      ' WHERE t2.locator = "' . $slo . '"' .
      ' AND t1.topicmap_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return $this->_getConstructByVerifiedId('TopicImpl-' . $result['id']);
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
  public function getConstructByItemIdentifier($iid)
  {
    $query = 'SELECT t1.*' .
      ' FROM ' . $this->_config['table']['topicmapconstruct'] . ' t1' .
      ' INNER JOIN ' . $this->_config['table']['itemidentifier'] . ' t2' .
      ' ON t1.id = t2.topicmapconstruct_id' .
      ' WHERE t2.locator = "' . $iid . '"' .
      ' AND t1.topicmap_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    return $rows > 0
      ? $this->_factory($mysqlResult)
      : null;
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
  public function getConstructById($id)
  {
    if (!$this->_verify($id)) {
      return null;
    }
    return $this->_getConstructByVerifiedId($id);
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
   * @throws {@link ModelConstraintException} If <var>type</var> or a <var>theme</var> in 
   * 				the scope does not belong to this topic map.
   */
  public function createAssociation(Topic $type, array $scope=array())
  {
    if (!$this->equals($type->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
    foreach ($scope as $theme) {
      if ($theme instanceof Topic && !$this->equals($theme->_topicMap)) {
        throw new ModelConstraintException(
          $this, 
          __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
        );
      }
    }
    $hash = $this->_getAssocHash($type, $scope, $roles=array());
    $assocId = $this->_hasAssoc($hash);
    if ($assocId) {
      return $this->_getConstructByVerifiedId('AssociationImpl-' . $assocId);
    }
    $this->_mysql->startTransaction(true);
    $query = 'INSERT INTO ' . $this->_config['table']['association'] . 
      ' (id, type_id, topicmap_id, hash) VALUES' .
      ' (NULL, ' . $type->_dbId . ', ' . $this->_dbId . ', "' . $hash . '")';
    $mysqlResult = $this->_mysql->execute($query);
    $lastAssocId = $mysqlResult->getLastId();
    
    $query = 'INSERT INTO ' . $this->_config['table']['topicmapconstruct'] . 
      ' (association_id, topicmap_id, parent_id) VALUES' .
      ' (' . $lastAssocId . ', '.$this->_dbId . ', ' . $this->_dbId . ')';
    $this->_mysql->execute($query);
    
    $scopeObj = new ScopeImpl($this->_mysql, $this->_config, $scope, $this, $this);
    $query = 'INSERT INTO ' . $this->_config['table']['association_scope'] . 
      ' (scope_id, association_id) VALUES' .
      ' (' . $scopeObj->_dbId . ', ' . $lastAssocId . ')';
    $this->_mysql->execute($query);
    
    $this->_mysql->finishTransaction(true);
    
    $propertyHolder['type_id']=$type->_dbId;
    $this->_setConstructPropertyHolder($propertyHolder);
    $assoc = $this->_getConstructByVerifiedId('AssociationImpl-' . $lastAssocId);
    
    if (!$this->_mysql->hasError()) {
      $assoc->_postInsert();
      $this->_postSave();
    }
    if (is_null($this->_assocsCache)) {
      return $assoc;
    } else {
      $this->_assocsCache[$assoc->getId()] = $assoc;
      return $assoc;
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
  public function createTopicByItemIdentifier($iid)
  {
    if (is_null($iid)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_identityNullErrMsg
      );
    }
    $construct = $this->getConstructByItemIdentifier($iid);
    if (!is_null($construct)) {
      if ($construct instanceof Topic) {
        return $construct;
      } else {
        throw new IdentityConstraintException(
          $this, 
          $construct, 
          $iid, 
          __METHOD__ . ConstructImpl::$_iidExistsErrMsg
        );
      }
    } else {
      $topic = $this->getTopicBySubjectIdentifier($iid);
      if (!is_null($topic)) {
        $topic->addItemIdentifier($iid);
        return $topic;
      } else {
        $this->_setIid = false;
        $this->_mysql->startTransaction(true);
        $topic = $this->createTopic();
        $topic->addItemIdentifier($iid);
        $this->_mysql->finishTransaction(true);
        $this->_setIid = true;
        return $topic;
      }
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
  public function createTopicBySubjectIdentifier($sid)
  {
    if (is_null($sid)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_identityNullErrMsg
      );
    }
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
        $this->_setIid = false;
        $this->_mysql->startTransaction(true);
        $topic = $this->createTopic();
        // add subject identifier to this topic
        $topic->addSubjectIdentifier($sid);
        $this->_mysql->finishTransaction(true);
        $this->_setIid = true;
        return $topic;
      }
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
  public function createTopicBySubjectLocator($slo)
  {
    if (is_null($slo)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_identityNullErrMsg
      );
    }
    $topic = $this->getTopicBySubjectLocator($slo);
    if (!is_null($topic)) {
      return $topic;
    } else {// create new topic
      $this->_setIid = false;
      $this->_mysql->startTransaction(true);
      $topic = $this->createTopic();
      // add subject locator to this topic
      $topic->addSubjectLocator($slo);
      $this->_mysql->finishTransaction(true);
      $this->_setIid = true;
      return $topic;
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
  public function createTopic()
  {
    $this->_mysql->startTransaction();
    $query = 'INSERT INTO ' . $this->_config['table']['topic'] . 
      ' (id, topicmap_id) VALUES (NULL, ' . $this->_dbId . ')';
    $mysqlResult = $this->_mysql->execute($query);
    $lastTopicId = $mysqlResult->getLastId();
    
    $query = 'INSERT INTO ' . $this->_config['table']['topicmapconstruct'] . 
      ' (topic_id, topicmap_id, parent_id) VALUES' .
      ' (' . $lastTopicId . ', ' . $this->_dbId . ', ' . $this->_dbId . ')';
    $this->_mysql->execute($query);
    $lastConstructId = $mysqlResult->getLastId();
    
    if ($this->_setIid) {
      $iidFragment = '#TopicImpl-' . $lastTopicId;
      $iid = $this->getLocator() . $iidFragment;
      $query = 'INSERT INTO ' . $this->_config['table']['itemidentifier'] . 
        ' (topicmapconstruct_id, locator) VALUES' .
        ' (' . $lastConstructId . ', "' . $iid . '")';
      $this->_mysql->execute($query);
    }
    $this->_mysql->finishTransaction();
    $topic = $this->_getConstructByVerifiedId('TopicImpl-' . $lastTopicId);
    if (!$this->_mysql->hasError()) {
      $topic->_postInsert();
      $this->_postSave();
    }
    if (is_null($this->_topicsCache)) {
      return $topic;
    } else {
      $this->_topicsCache[$topic->getId()] = $topic;
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
  public function mergeIn(TopicMap $other)
  {
    if ($this->equals($other)) {
      return;
    }
    $this->_setState(VocabularyUtils::QTM_STATE_MERGING);
    $this->_mysql->startTransaction(true);
    
    $otherTopics = $other->getTopics();
    foreach ($otherTopics as $otherTopic) {
      $this->_copyTopic($otherTopic);
    }
    
    // set or merge reifier
    $otherReifier = $other->getReifier();
    if (!is_null($otherReifier)) {
      if (is_null($this->getReifier())) {
        $reifier = $this->_copyTopic($otherReifier);
        $this->setReifier($reifier);
      } else {
        $reifierToMerge = $this->_copyTopic($otherReifier);
        $this->getReifier()->mergeIn($reifierToMerge);
      }
    }
    
    $this->_copyIids($this, $other);
    
    $this->_copyAssociations($other->getAssociations());
    
    $this->_mysql->finishTransaction(true);
    $this->_setState(VocabularyUtils::QTM_STATE_REGULAR);
  }

  /**
   * Returns the specified index.
   *
   * @param string The classname of the index.
   * @return Index An index.
   * @throws FeatureNotSupportedException If the implementation does not support indices, 
   *        if the specified index is unsupported, or if the specified index does not exist.
   */
  public function getIndex($className)
  {
    if (in_array($className, self::$_supportedIndices)) {
      return new $className($this->_mysql, $this->_config, $this);
    } else {
      throw new FeatureNotSupportedException(
        __METHOD__ . ': Index "' . $className . '" is not supported!'
      );
    }
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
  public function close()
  {
    $this->_topicsCache = 
    $this->_assocsCache = null;
  }
  
  /**
   * Returns the reifier of this construct.
   * 
   * @return TopicImpl The topic that reifies this construct or
   *        <var>null</var> if this construct is not reified.
   */
  public function getReifier()
  {
    return $this->_getReifier();
  }

  /**
   * (non-PHPDoc)
   * @see phptmapi/core/ConstructImpl#_setReifier()
   */
  public function setReifier(Topic $reifier=null)
  {
    $this->_setReifier($reifier);
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
  public function finished(Association $assoc)
  {
    // get the hash of the finished association
    $query = 'SELECT hash FROM ' . $this->_config['table']['association'] . 
      ' WHERE id = ' . $assoc->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    // detect duplicates
    $query = 'SELECT id FROM ' . $this->_config['table']['association'] . 
      ' WHERE hash = "' . $result['hash'] . '"' .
      ' AND id <> ' . $assoc->_dbId . 
      ' AND topicmap_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $duplicate = $this->_getConstructByVerifiedId('AssociationImpl-' . $result['id']);
      // gain duplicate's item identities
      $assoc->_gainItemIdentifiers($duplicate);
      // gain duplicate's reifier
      $assoc->_gainReifier($duplicate);
      $duplicate->remove();
    }
  }
  
  /**
   * Deletes this topic map.
   * 
   * @override
   * @return void
   * @throws PHPTMAPIRuntimeException If removal failed.
   */
  public function remove()
  {
    $this->_preDelete();
    $this->_mysql->startTransaction();
    
    // topic names
    $query = 'DELETE t1.*, t2.* FROM ' . $this->_config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->_config['table']['topicname'] . ' t2 ' .
      'ON t2.id = t1.topicname_id ' .
      'WHERE t1.topicmap_id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    // occurrences
    $query = 'DELETE t1.*, t2.* FROM ' . $this->_config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->_config['table']['occurrence'] . ' t2 ' .
      'ON t2.id = t1.occurrence_id ' .
      'WHERE t1.topicmap_id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    // associations; cascades to roles
    $query = 'DELETE t1.*, t2.* FROM ' . $this->_config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->_config['table']['association'] . ' t2 ' .
      'ON t2.id = t1.association_id ' .
      'WHERE t1.topicmap_id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    // typed topics (instanceof)
    $query = 'DELETE t1.* FROM ' . $this->_config['table']['instanceof'] . ' t1 ' .
      'INNER JOIN ' . $this->_config['table']['topicmapconstruct'] . ' t2 ' .
      'ON t2.topic_id = t1.topic_id ' .
      'WHERE t2.topicmap_id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    // scope and themes
    $query = 'DELETE t1.*, t2.* FROM ' . $this->_config['table']['theme'] . ' t1 ' .
      'INNER JOIN ' . $this->_config['table']['scope'] . ' t2 ' .
      'ON t2.id = t1.scope_id ' . 
      'INNER JOIN ' . $this->_config['table']['topicmapconstruct'] . ' t3 ' . 
      'ON t3.topic_id = t1.topic_id ' . 
      'WHERE t3.topicmap_id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    // unset topic map's reifier to prevent fk constraint errors
    $query = 'UPDATE ' . $this->_config['table']['topicmapconstruct'] . 
      ' SET reifier_id = NULL' . 
      ' WHERE topicmap_id = ' . $this->_dbId . 
      ' AND parent_id IS NULL';
    $this->_mysql->execute($query);
    
    // topics; cascades to identifiers
    $query = 'DELETE t1.*, t2.* FROM ' . $this->_config['table']['topicmapconstruct'] . ' t1 ' .
      'INNER JOIN ' . $this->_config['table']['topic'] . ' t2 ' .
      'ON t2.id = t1.topic_id ' .
      'WHERE t1.topicmap_id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    // the topic map
    $query = 'DELETE FROM ' . $this->_config['table']['topicmap'] .
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    // clean up scope (an ucs may be left) and truncate all tables if no topic map exists anymore
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topicmap'];
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    if ($result[0] == 0) {
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['scope'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['association'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['association_scope'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['assocrole'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['instanceof'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['occurrence'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['occurrence_scope'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['theme'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['subjectidentifier'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['subjectlocator'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['topic'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['topicmap'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['topicmapconstruct'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['itemidentifier'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['topicname'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['topicname_scope'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['variant'];
      $this->_mysql->execute($query);
      $query = 'TRUNCATE TABLE ' . $this->_config['table']['variant_scope'];
      $this->_mysql->execute($query);
    }
    
    $this->_mysql->finishTransaction();
    
    if ($this->_mysql->hasError()) {
      throw new PHPTMAPIRuntimeException(
      	'Error in ' . __METHOD__ . ': '. $this->_mysql->getError()
      );
    }
    
    $this->_id = 
    $this->_dbId = null;
  }
  
  /**
   * Clears the topics cache.
   * 
   * @return void
   */
  public function clearTopicsCache()
  {
    $this->_topicsCache = null;
  }
  
  /**
   * Clears the associations cache.
   * 
   * @return void
   */
  public function clearAssociationsCache()
  {
    $this->_assocsCache = null;
  }
  
  /**
   * Returns a {@link ConstructImpl} by its (system specific) identifier.
   *
   * @param string The identifier of the construct to be returned.
   * @param string The construct's hash.
   * @return ConstructImpl The construct with the specified id.
   */
  protected function _getConstructByVerifiedId($id, $hash=null)
  {
    $constituents = explode('-', $id);
    $className = $constituents[0];
    $dbId = $constituents[1];
    switch ($className) {
      case 'TopicImpl':
        return new $className($dbId, $this->_mysql, $this->_config, $this);
        break;
      case 'NameImpl':
        $parent = $this->_constructParent instanceof Topic 
          ? $this->_constructParent 
          : $this->_getNameParent($dbId);
        $this->_constructParent = null;
        $propertyHolder = $this->_constructPropertyHolder;
        $this->_constructPropertyHolder = array();
        return new $className(
          $dbId, 
          $this->_mysql, 
          $this->_config, 
          $parent, 
          $this, 
          $propertyHolder
        );
        break;
      case 'AssociationImpl':
        $propertyHolder = $this->_constructPropertyHolder;
        $this->_constructPropertyHolder = array();
        return new $className($dbId, $this->_mysql, $this->_config, $this, $propertyHolder);
        break;
      case 'RoleImpl':
        $parent = $this->_constructParent instanceof Association 
          ? $this->_constructParent 
          : $this->_getRoleParent($dbId);
        $this->_constructParent = null;
        $propertyHolder = $this->_constructPropertyHolder;
        $this->_constructPropertyHolder = array();
        return new $className(
          $dbId, 
          $this->_mysql, 
          $this->_config, 
          $parent, 
          $this, 
          $propertyHolder
        );
        break;
      case 'OccurrenceImpl':
        $parent = $this->_constructParent instanceof Topic 
          ? $this->_constructParent 
          : $this->_getOccurrenceParent($dbId);
        $this->_constructParent = null;
        $propertyHolder = $this->_constructPropertyHolder;
        $this->_constructPropertyHolder = array();
        return new $className(
          $dbId, 
          $this->_mysql, 
          $this->_config, 
          $parent, 
          $this, 
          $propertyHolder
        );
        break;
      case 'VariantImpl':
        $parent = $this->_constructParent instanceof Name 
          ? $this->_constructParent 
          : $this->_getVariantParent($dbId);
        $this->_constructParent = null;
        $propertyHolder = $this->_constructPropertyHolder;
        $this->_constructPropertyHolder = array();
        return new $className(
          $dbId, 
          $this->_mysql, 
          $this->_config, 
          $parent, 
          $this, 
          $propertyHolder, 
          $hash
        );
        break;
      case __CLASS__:
        return new $className($dbId, $this->_mysql, $this->_config, $this->getTopicMapSystem());
        break;
    }
  }
  
  /**
   * Sets the construct's parent.
   * 
   * @param ConstructImpl
   * @return void
   */
  protected function _setConstructParent(Construct $parent)
  {
    $this->_constructParent = $parent;
  }
  
  /**
   * Sets the construct property holder.
   * 
   * @param array The property holder.
   * @return void
   */
  protected function _setConstructPropertyHolder(array $propertyHolder)
  {
    $this->_constructPropertyHolder = $propertyHolder;
  }
  
  /**
   * Gets the association hash.
   * 
   * @param TopicImpl The association type.
   * @param array The scope.
   * @param array The roles.
   * @return string
   */
  protected function _getAssocHash(Topic $type, array $scope, array $roles)
  {
    $scopeIdsImploded = 
    $roleIdsImploded = null;
    if (count($scope) > 0) {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) $ids[$theme->_dbId] = $theme->_dbId;
      }
      ksort($ids);
      $scopeIdsImploded = implode('', $ids);
    }
    if (count($roles) > 0) {
      $ids = array();
      foreach ($roles as $role) {
        if ($role instanceof Role || $role instanceof MemoryRole) {
          $ids[$role->getType()->_dbId . $role->getPlayer()->_dbId] = $role->getType()->_dbId . $role->getPlayer()->_dbId; 
        }
      }
      ksort($ids);
      $roleIdsImploded = implode('', $ids);
    }
    return md5($type->_dbId . $scopeIdsImploded . $roleIdsImploded);
  }
  
  /**
   * Updates association hash.
   * 
   * @param int The association id.
   * @param string The association hash.
   */
  protected function _updateAssocHash($assocId, $hash)
  {
    $query = 'UPDATE ' . $this->_config['table']['association'] . 
      ' SET hash = "' . $hash . '"' .
      ' WHERE id = ' . $assocId;
    $this->_mysql->execute($query);
  }
  
  /**
   * Checks if topic map has a certain association.
   * 
   * @param string The hash code.
   * @return false|int The association id or <var>false</var> otherwise.
   */
  protected function _hasAssoc($hash)
  {
    $query = 'SELECT id FROM ' . $this->_config['table']['association'] . 
      ' WHERE topicmap_id = ' . $this->_dbId . 
      ' AND hash = "' . $hash . '"';
    $mysqlResult = $this->_mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return (int) $result['id'];
    }
    return false;
  }
  
  /**
   * Removes an association from the associations cache.
   * 
   * @param string The association id.
   * @return void
   */
  protected function _removeAssociationFromCache($assocId)
  {
    if (is_null($this->_assocsCache)) {
      return;
    }
    if (array_key_exists($assocId, $this->_assocsCache)) {
      unset($this->_assocsCache[$assocId]);
    }
  }
  
  /**
   * Removes a topic from the topics cache.
   * 
   * @param string The topic id.
   * @return void
   */
  protected function _removeTopicFromCache($topicId)
  {
    if (is_null($this->_topicsCache)) {
      return;
    }
    if (array_key_exists($topicId, $this->_topicsCache)) {
      unset($this->_topicsCache[$topicId]);
    }
  }
  
  /**
   * Sets the state of the topic map: <var>Merging</var> or <var>regular</var>.
   * The states are identified by URIs <var> http://quaaxtm.sourceforge.net/state/merging/</var>
   * and <var>http://quaaxtm.sourceforge.net/state/regular/</var>.
   * 
   * @param string The state URI.
   * @return void
   */
  protected function _setState($state)
  {
    $this->_topicMapState = $state;
  }
  
  /**
   * Gets the URI representing the state of the topic map.
   * 
   * @see {@link TopicMapImpl::setState()}
   * @return string The state URI.
   */
  protected function _getState()
  {
    return $this->_topicMapState;
  }
  
  /**
   * Gets the construct's topicmapconstruct table <var>id</var>.
   * 
   * @return int The id.
   * @override
   */
  private function _getConstructDbId()
  {
    $query = 'SELECT id FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE topicmap_id = ' . $this->_dbId . 
      ' AND parent_id IS NULL';
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return (int) $result['id'];
  }
  
  /**
   * Verifies if this topic map contains a construct with the given id.
   * 
   * @param string The construct's id retrieved by {@link ConstructImpl::getId()}
   * @return boolean <var>True</var> if the id is valid in the current topic map;
   * 				<var>false</var> otherwise.
   */
  private function _verify($id)
  {
    if (!preg_match('/^[a-z]+\-[0-9]+$/i', $id)) {
      return false;
    }
    if (array_key_exists($id, $this->_seenConstructsCache)) {
      return true;
    }
    $constituents = explode('-', $id);
    $className = $constituents[0];
    $dbId = $constituents[1];
    $fkColumn = $this->_getFkColumn($className);
    if (is_null($fkColumn)) {
      return false;
    }
    if ($fkColumn != parent::TOPICMAP_FK_COL) {
      $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topicmapconstruct'] . 
        ' WHERE ' . $fkColumn . ' = ' . $dbId . 
        ' AND topicmap_id = ' . $this->_dbId;
    } else {
      $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topicmapconstruct'] . 
        ' WHERE topicmap_id = ' . $dbId . 
        ' AND parent_id IS NULL';
    }
    $mysqlResult = $this->_mysql->execute($query);
    if ($this->_mysql->hasError()) {
      return false;
    }
    $result = $mysqlResult->fetchArray();
    if ((int)$result[0] > 0) {
      $this->_seenConstructsCache[$id] = $this->_id;
      return true;
    }
    return false;
  }
  
  /**
   * Gets the parent topic of a topic name.
   * 
   * @param int The name id in database.
   * @return TopicImpl
   */
  private function _getNameParent($nameId)
  {
    $query = 'SELECT parent_id FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE topicname_id = ' . $nameId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->_getConstructByVerifiedId('TopicImpl-' . $result['parent_id']);
  }
  
  /**
   * Gets the parent topic of an occurrence.
   * 
   * @param int The occurrence id in database.
   * @return TopicImpl
   */
  private function _getOccurrenceParent($occId)
  {
    $query = 'SELECT parent_id FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE occurrence_id = ' . $occId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->_getConstructByVerifiedId('TopicImpl-' . $result['parent_id']);
  }
  
  /**
   * Gets the parent association of a role.
   * 
   * @param int The role id in database.
   * @return AssociationImpl
   */
  private function _getRoleParent($roleId)
  {
    $query = 'SELECT parent_id FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE assocrole_id = ' . $roleId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->_getConstructByVerifiedId('AssociationImpl-' . $result['parent_id']);
  }
  
  /**
   * Gets the parent topic name of a variant name.
   * 
   * @param int The variant name id in database.
   * @return NameImpl
   */
  private function _getVariantParent($variantId)
  {
    $query = 'SELECT parent_id FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE variant_id = ' . $variantId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    $nameId = $result['parent_id'];
    $parentTopic = $this->_getNameParent($nameId);
    $this->_setConstructParent($parentTopic);
    return $this->_getConstructByVerifiedId('NameImpl-' . $result['parent_id']);
  }
  
  /**
   * Copies a given topic to this topic map.
   * 
   * @param TopicImpl The source topic.
   * @return TopicImpl
   */
  private function _copyTopic(Topic $sourceTopic)
  {
    $existingTopic = $this->_getTopicByOthersIdentities($sourceTopic);
    if ($existingTopic instanceof Topic) {
      $targetTopic = $existingTopic;
    } else {
      $this->_setIid = false;
      $targetTopic = $this->createTopic();
      $this->_setIid = true;
    }
    
    // copy identities
    $iids = $sourceTopic->getItemIdentifiers();
    foreach ($iids as $iid) {
      $targetTopic->addItemIdentifier($iid);
    }
    $sids = $sourceTopic->getSubjectIdentifiers();
    foreach ($sids as $sid) {
      $targetTopic->addSubjectIdentifier($sid);
    }
    $slos = $sourceTopic->getSubjectLocators();
    foreach ($slos as $slo) {
      $targetTopic->addSubjectLocator($slo);
    }

    // copy types
    $this->_copyTopicTypes($targetTopic, $sourceTopic);
    
    // copy names
    $this->_copyNames($targetTopic, $sourceTopic);
    
    // copy occurrences
    $this->_copyOccurrences($targetTopic, $sourceTopic);
    
    return $targetTopic;
  }
  
  /**
   * Copies the types of the source topic to the target topic.
   * 
   * @param TopicImpl The target topic.
   * @param TopicImpl The source topic.
   * @return void
   */
  private function _copyTopicTypes(Topic $targetTopic, Topic $sourceTopic)
  {
    $sourceTypes = $sourceTopic->getTypes();
    foreach ($sourceTypes as $sourceType) {
      $targetType = $this->_copyTopic($sourceType);
      $targetTopic->addType($targetType);
    }
  }
  
  /**
   * Copies the names of the source topic to the target topic.
   * 
   * @param TopicImpl The target topic.
   * @param TopicImpl The source topic.
   * @return void
   */
  private function _copyNames(Topic $targetTopic, Topic $sourceTopic)
  {
    $sourceNames = $sourceTopic->getNames();
    foreach ($sourceNames as $sourceName) {
      $targetType = $this->_copyTopic($sourceName->getType());
      $targetNameScope = $this->_copyScope($sourceName->getScope());
      $targetName = $targetTopic->createName( 
        $sourceName->getValue(), 
        $targetType, 
        $targetNameScope
      );
      // source name's iids
      $this->_copyIids($targetName, $sourceName);
      
      // source name's reifier
      $this->_copyReifier($targetName, $sourceName);

      // other's name's variants
      $sourceVariants = $sourceName->getVariants();
      foreach ($sourceVariants as $sourceVariant) {
        $targetVariantScope = $this->_copyScope($sourceVariant->getScope());
        $targetVariant = $targetName->createVariant(
          $sourceVariant->getValue(), 
          $sourceVariant->getDatatype(), 
          $targetVariantScope
        );
        // source variant's iids
        $this->_copyIids($targetVariant, $sourceVariant);
        
        // source variant's reifier
        $this->_copyReifier($targetVariant, $sourceVariant);
      }
    }
  }
  
  /**
   * Copies the occurrences of the source topic to the target topic.
   * 
   * @param TopicImpl The target topic.
   * @param TopicImpl The source topic.
   * @return void
   */
  private function _copyOccurrences(Topic $targetTopic, Topic $sourceTopic)
  {
    $sourceOccurrences = $sourceTopic->getOccurrences();
    foreach ($sourceOccurrences as $sourceOccurrence) {
      $targetType = $this->_copyTopic($sourceOccurrence->getType());
      $targetScope = $this->_copyScope($sourceOccurrence->getScope());
      $targetOccurrence = $targetTopic->createOccurrence(
        $targetType, 
        $sourceOccurrence->getValue(), 
        $sourceOccurrence->getDatatype(), 
        $targetScope
      );
      // source occurrence's iids
      $this->_copyIids($targetOccurrence, $sourceOccurrence);
      
      // source occurrence's reifier
      $this->_copyReifier($targetOccurrence, $sourceOccurrence);
    }
  }
  
  /**
   * Copies association from a source topic map to this topic map.
   * 
   * @param array The source topic map's associations.
   * @return void
   */
  private function _copyAssociations(array $sourceAssocs)
  {
    foreach ($sourceAssocs as $sourceAssoc) {
      $targetAssocType = $this->_copyTopic($sourceAssoc->getType());
      $targetScope = $this->_copyScope($sourceAssoc->getScope());
      $targetAssoc = $this->createAssociation($targetAssocType, $targetScope);
      
      // copy roles
      $sourceRoles = $sourceAssoc->getRoles();
      foreach ($sourceRoles as $sourceRole) {
        $targetRoleType = $this->_copyTopic($sourceRole->getType());
        $targetPlayer = $this->_copyTopic($sourceRole->getPlayer());
        $targetRole = $targetAssoc->createRole($targetRoleType, $targetPlayer);
        
        // source role's iids
        $this->_copyIids($targetRole, $sourceRole);
        
        // source role's reifier
        $this->_copyReifier($targetRole, $sourceRole);
      }
      
      // source associations's iids
      $this->_copyIids($targetAssoc, $sourceAssoc);
      
      // source associations's reifier
      $this->_copyReifier($targetAssoc, $sourceAssoc);
    }
  }
  
  /**
   * Copies the item identifiers of the source construct to the target construct.
   * 
   * @param ConstructImpl The target construct.
   * @param ConstructImpl The source construct.
   * @return void
   */
  private function _copyIids(Construct $targetConstruct, Construct $sourceConstruct)
  {
    $iids = $sourceConstruct->getItemIdentifiers();
    foreach ($iids as $iid) {
      $targetConstruct->addItemIdentifier($iid);
    }
  }
  
  /**
   * Copies the reifier of the source reifiable to the target reifiable.
   * 
   * @param Reifiable The target reifiable.
   * @param Reifiable The source reifiable.
   * @return void
   */
  private function _copyReifier(Reifiable $targetReifiable, Reifiable $sourceReifiable)
  {
    $sourceReifier = $sourceReifiable->getReifier();
    if (!is_null($sourceReifier)) {
      $reifier = $this->_copyTopic($sourceReifier);
      $targetReifiable->setReifier($reifier);
    }
  }
  
  /**
   * Copies the themes of the source scope to the target scope.
   * 
   * @param array The source scope.
   * @return array The target scope.
   */
  private function _copyScope(array $sourceScope)
  {
    $targetScope = array();
    foreach ($sourceScope as $sourceTheme) {
      $targetTheme = $this->_copyTopic($sourceTheme);
      $targetScope[] = $targetTheme;
    }
    return $targetScope;
  }
  
  /**
   * Gets a possibly existing topic by the other topic's identities 
   * or null if such a topic does not exists.
   * 
   * @param TopicImpl The source topic.
   * @return TopicImpl|null
   */
  private function _getTopicByOthersIdentities(Topic $sourceTopic)
  {
    $iids = $sourceTopic->getItemIdentifiers();
    foreach ($iids as $iid) {
      $construct = $this->getConstructByItemIdentifier($iid);
      if ($construct instanceof Topic) {
        return $construct;
      }
      // check subject identifiers too
      $topic = $this->getTopicBySubjectIdentifier($iid);
      if (!is_null($topic)) {
        return $topic;
      }
    }
    $sids = $sourceTopic->getSubjectIdentifiers();
    foreach ($sids as $sid) {
      $topic = $this->getTopicBySubjectIdentifier($sid);
      if (!is_null($topic)) {
        return $topic;
      }
      // check item identifiers too
      $construct = $this->getConstructByItemIdentifier($sid);
      if ($construct instanceof Topic) {
        return $construct;
      }
    }
    $slos = $sourceTopic->getSubjectLocators();
    foreach ($slos as $slo) {
      $topic = $this->getTopicBySubjectLocator($slo);
      if (!is_null($topic)) {
        return $topic;
      }
    }
    return null;
  }
}
?>