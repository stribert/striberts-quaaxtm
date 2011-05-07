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
 * Represents a topic item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#d0e739}.
 * 
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns the 
 * {@link TopicMapImpl} to which this topic belongs.
 * 
 * Inherited method <var>addItemIdentifier()</var> from {@link ConstructImpl} throws an 
 * {@link IdentityConstraintException} if adding the specified item identifier would make 
 * this topic represent the same subject as another topic and the feature "automerge" 
 * ({@link http://tmapi.org/features/automerge}) is disabled.
 * 
 * Inherited method <var>remove()</var> from {@link ConstructImpl} throws a 
 * {@link TopicInUseException} if the topic plays a {@link RoleImpl}, is used as type of a 
 * {@link Typed} construct, or if it is used as theme for a {@link ScopedImpl} construct, 
 * or if it reifies a {@link Reifiable}.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class TopicImpl extends ConstructImpl implements Topic {

  const IDENTITY_NULL_ERR_MSG = ': Identity locator must not be null!';
  
  private $defaultNameType;
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicMapImpl The parent topic map.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, TopicMap $parent) {
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $parent);
    $this->defaultNameType = null;
  }

  /**
   * Returns the subject identifiers assigned to this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the subject identifiers.
   */
  public function getSubjectIdentifiers() {
    $sids = array();
    $query = 'SELECT locator FROM ' . $this->config['table']['subjectidentifier'] . 
      ' WHERE topic_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $sids[] = $result['locator'];
    }
    return $sids;
  }

  /**
   * Adds a subject identifier to this topic.
   * 
   * If adding the specified subject identifier would make this topic
   * represent the same subject as another topic and the feature 
   * "automerge" ({@link http://tmapi.org/features/automerge/}) is disabled,
   * an {@link IdentityConstraintException} is thrown.
   * 
   * @param string The subject identifier to be added; must not be <var>null</var>.
   * @return void
   * @throws {@link IdentityConstraintException} If the feature "automerge" is
   *        disabled and adding the subject identifier would make this 
   *        topic represent the same subject as another topic.
   * @throws {@link ModelConstraintException} If the subject identifier is <var>null</var>.
   */
  public function addSubjectIdentifier($sid) {
    if (is_null($sid)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . self::IDENTITY_NULL_ERR_MSG
      );
    }
    $sids = $this->getSubjectIdentifiers();
    if (in_array($sid, $sids)) {
      return;
    }
    // check others' subject identifiers
    $query = 'SELECT t2.id' .
      ' FROM ' . $this->config['table']['subjectidentifier'] . ' t1' .
      ' INNER JOIN ' . $this->config['table']['topic'] . ' t2' .
      ' ON t2.id = t1.topic_id' .
      ' WHERE t1.locator = "' . $sid . '"' .
      ' AND t2.topicmap_id = ' . $this->topicMap->dbId . 
      ' AND t2.id <> ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows == 0) {
      // check others' item identifiers too
      $query = 'SELECT t1.*' . 
        ' FROM ' . $this->config['table']['topicmapconstruct'] . ' t1' . 
        ' INNER JOIN ' . $this->config['table']['itemidentifier'] . ' t2' .
        ' ON t1.id = t2.topicmapconstruct_id' .
        ' WHERE t2.locator = "' . $sid . '"' . 
        ' AND t1.topicmap_id = ' . $this->topicMap->dbId . 
        ' AND t1.topic_id <> ' . $this->dbId . 
        ' AND t1.topic_id IS NOT NULL';
      $mysqlResult = $this->mysql->execute($query);
      $rows = $mysqlResult->getNumRows();
      if ($rows == 0) {// insert subject identifier
        $query = 'INSERT INTO ' . $this->config['table']['subjectidentifier'] . 
          ' (topic_id, locator) VALUES (' . $this->dbId . ', "' . $sid .'")';
        $this->mysql->execute($query);
        if (!$this->mysql->hasError()) {
          $this->postSave();
        }
      } else {// merge
        $existingTopic = $this->factory($mysqlResult);
        $this->mergeIn($existingTopic);
      }
    } else {// merge
      $result = $mysqlResult->fetch();
      $existingTopic = $this->parent->getConstructByVerifiedId(__CLASS__ . '-' . $result['id']);
      $this->mergeIn($existingTopic);
    }
  }

  /**
   * Removes a subject identifier from this topic.
   *
   * @param string The subject identifier to be removed from this topic, 
   *        if present (<var>null</var> is ignored).
   * @return void
   */
  public function removeSubjectIdentifier($sid) {
    if (is_null($sid)) {
      return;
    }
    $query = 'DELETE FROM ' . $this->config['table']['subjectidentifier'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' AND locator = "' . $sid . '"';
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      $this->postSave();
    }
  }

  /**
   * Returns the subject locators assigned to this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the subject locators.
   */
  public function getSubjectLocators() {
    $slos = array();
    $query = 'SELECT locator FROM ' . $this->config['table']['subjectlocator'] . 
      ' WHERE topic_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $slos[] = $result['locator'];
    }
    return $slos;
  }

  /**
   * Adds a subject locator to this topic.
   * 
   * If adding the specified subject locator would make this topic
   * represent the same subject as another topic and the feature 
   * "automerge" ({@link http://tmapi.org/features/automerge/}) is disabled,
   * an {@link IdentityConstraintException} is thrown.
   * 
   * @param string The subject locator to be added; must not be <var>null</var>.
   * @return void
   * @throws {@link IdentityConstraintException} If the feature "automerge" is
   *        disabled and adding the subject locator would make this 
   *        topic represent the same subject as another topic.
   * @throws {@link ModelConstraintException} If the subject locator is <var>null</var>.
   */
  public function addSubjectLocator($slo) {
    if (is_null($slo)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . self::IDENTITY_NULL_ERR_MSG
      );
    }
    $slos = $this->getSubjectLocators();
    if (in_array($slo, $slos)) {
      return;
    }
    // check others' subject locators
    $query = 'SELECT t2.id' .
      ' FROM ' . $this->config['table']['subjectlocator'] . ' t1' .
      ' INNER JOIN ' . $this->config['table']['topic'] . ' t2' .
      ' ON t2.id = t1.topic_id' .
      ' WHERE t1.locator = "' . $slo . '"' .
      ' AND t2.topicmap_id = ' . $this->topicMap->dbId . 
      ' AND t2.id <> ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows == 0) {// insert subject locator
      $query = 'INSERT INTO ' . $this->config['table']['subjectlocator'] . 
        ' (topic_id, locator) VALUES (' . $this->dbId . ', "' . $slo .'")';
      $this->mysql->execute($query);
      if (!$this->mysql->hasError()) {
        $this->postSave();
      }
    } else {// merge
      $result = $mysqlResult->fetch();
      $existingTopic = $this->parent->getConstructByVerifiedId(__CLASS__ . '-' . $result['id']);
      $this->mergeIn($existingTopic);
    }
  }

  /**
   * Removes a subject locator from this topic.
   *
   * @param string The subject locator to be removed from this topic, 
   *        if present (<var>null</var> is ignored).
   * @return void
   */
  public function removeSubjectLocator($slo) {
    if (is_null($slo)) {
      return;
    }
    $query = 'DELETE FROM ' . $this->config['table']['subjectlocator'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' AND locator = "' . $slo . '"';
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      $this->postSave();
    }
  }

  /**
   * Returns the names of this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param TopicImpl The type of the {@link NameImpl}s to be returned. Default <var>null</var>.
   * @return array An array containing a set of {@link NameImpl}s belonging to this topic.
   */
  public function getNames(Topic $type=null) {
    $names = array();
    $query = 'SELECT id, type_id, value, hash FROM ' . $this->config['table']['topicname'] . 
      ' WHERE topic_id = ' . $this->dbId;
    if (!is_null($type)) {
      $query .= ' AND type_id = ' . $type->dbId;
    }
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId((int)$result['type_id'])
        ->setValue($result['value']);
      $this->parent->setConstructPropertyHolder($propertyHolder);
      
      $this->parent->setConstructParent($this);
      
      $name = $this->parent->getConstructByVerifiedId('NameImpl-' . $result['id']);
      
      $names[$result['hash']] = $name;
    }
    return array_values($names);
  }

  /**
   * Creates a {@link NameImpl} for this topic with the specified <var>value</var>, 
   * <var>type</var>, and <var>scope</var>.
   * 
   * If <var>type</var> is <var>null</var> the created {@link NameImpl} will have the default 
   * name type (a {@link TopicImpl} with the subject identifier 
   * http://psi.topicmaps.org/iso13250/model/topic-name).
   * 
   * @param string The string value of the name; must not be <var>null</var>.
   * @param TopicImpl The name type. Default <var>null</var>.
   * @param array An array containing {@link TopicImpl}s - each representing a theme. 
   *        If the array's length is 0 (default), the name will be in the 
   *        unconstrained scope.
   * @return NameImpl The newly created {@link NameImpl}.
   * @throws {@link ModelConstraintException} If the <var>value</var> is <var>null</var>, or
   *        if <var>type</var> or a <var>theme</var> in the scope does not belong to the 
   *        parent topic map.
   */
  public function createName($value, Topic $type=null, array $scope=array()) {
    if (is_null($value)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . parent::VALUE_NULL_ERR_MSG
      );
    }
    $value = CharacteristicUtils::canonicalize($value, $this->mysql->getConnection());
    $type = is_null($type) ? $this->getDefaultNameType() : $type;
    if (!$this->topicMap->equals($type->topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . parent::SAME_TM_CONSTRAINT_ERR_MSG
      );
    }
    foreach ($scope as $theme) {
      if ($theme instanceof Topic && !$this->topicMap->equals($theme->topicMap)) {
        throw new ModelConstraintException(
          $this, 
          __METHOD__ . parent::SAME_TM_CONSTRAINT_ERR_MSG
        );
      }
    }
    $hash = $this->getNameHash($value, $type, $scope);
    $propertyId = $this->hasName($hash);
    if (!$propertyId) {
      $this->mysql->startTransaction(true);
      $query = 'INSERT INTO ' . $this->config['table']['topicname'] . 
        ' (id, topic_id, type_id, value, hash) VALUES' .
        ' (NULL, ' . $this->dbId . ', ' . $type->dbId . ', "' . $value . '", "' . $hash . '")';
      $mysqlResult = $this->mysql->execute($query);
      $lastNameId = $mysqlResult->getLastId();
      
      $query = 'INSERT INTO ' . $this->config['table']['topicmapconstruct'] . 
        ' (topicname_id, topicmap_id, parent_id) VALUES' .
        ' (' . $lastNameId . ', ' . $this->parent->dbId . ', ' . $this->dbId . ')';
      $this->mysql->execute($query);
      
      $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope, $this->topicMap, $this);
      $query = 'INSERT INTO ' . $this->config['table']['topicname_scope'] . 
        ' (scope_id, topicname_id) VALUES' .
        ' (' . $scopeObj->dbId . ', ' . $lastNameId . ')';
      $this->mysql->execute($query);
      
      $this->mysql->finishTransaction(true);
      
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId($type->dbId)
        ->setValue($value);
      $this->parent->setConstructPropertyHolder($propertyHolder);
      $this->parent->setConstructParent($this);
      
      $name = $this->parent->getConstructByVerifiedId('NameImpl-' . $lastNameId);
      if (!$this->mysql->hasError()) {
        $name->postInsert();
        $this->postSave();
      }
      return $name;
    } else {
      $this->parent->setConstructParent($this);
      return $this->parent->getConstructByVerifiedId('NameImpl-' . $propertyId);
    }
  }

  /**
   * Returns the {@link OccurrenceImpl}s of this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link OccurrenceImpl}s to be returned. 
   *        Default <var>null</var>.
   * @return array An array containing a set of {@link OccurrenceImpl}s belonging to 
   *        this topic.
   */
  public function getOccurrences(Topic $type=null) {
    $occurrences = array();
    $query = 'SELECT id, type_id, value, datatype, hash 
    	FROM ' . $this->config['table']['occurrence'] . ' WHERE topic_id = ' . $this->dbId;
    if (!is_null($type)) {
      $query .= ' AND type_id = ' . $type->dbId;
    }
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId((int)$result['type_id'])
        ->setValue($result['value'])
        ->setDataType($result['datatype']);
      $this->parent->setConstructPropertyHolder($propertyHolder);
      
      $this->parent->setConstructParent($this);
      
      $occurrence = $this->parent->getConstructByVerifiedId('OccurrenceImpl-' . $result['id']);
      
      $occurrences[$result['hash']] = $occurrence;
    }
    return array_values($occurrences);
  }

  /**
   * Creates an {@link OccurrenceImpl} for this topic with the specified 
   * <var>type</var>, <var>value</var>, <var>datatype</var>, and <var>scope</var>.
   * The newly created {@link OccurrenceImpl} will have the datatype specified 
   * by <var>datatype</var>.
   * 
   * @param TopicImpl The occurrence type.
   * @param string A string representation of the value; must not be <var>null</var>.
   * @param string A URI indicating the datatype of the <var>value</var>; 
   *        must not be <var>null</var>. E.g. http://www.w3.org/2001/XMLSchema#string 
   *        indicates a string value.
   * @param array An array containing {@link TopicImpl}s - each representing a theme; 
   *        must not be <var>null</var>. If the array's length is 0 (default), 
   *        the occurrence will be in the unconstrained scope.
   * @return OccurrenceImpl The newly created {@link OccurrenceImpl}.
   * @throws {@link ModelConstraintException} If either the <var>value</var> or the
   *        <var>datatype</var> is <var>null</var>, or if <var>type</var> or a 
   *        <var>theme</var> in the scope does not belong to the parent topic map.
   */
  public function createOccurrence(Topic $type, $value, $datatype, array $scope=array()) {
    if (!$this->topicMap->equals($type->topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . parent::SAME_TM_CONSTRAINT_ERR_MSG
      );
    }
    if (is_null($value) || is_null($datatype)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . parent::VALUE_DATATYPE_NULL_ERR_MSG
      );
    }
    foreach ($scope as $theme) {
      if ($theme instanceof Topic && !$this->topicMap->equals($theme->topicMap)) {
        throw new ModelConstraintException(
          $this, 
          __METHOD__ . parent::SAME_TM_CONSTRAINT_ERR_MSG
        );
      }
    }
    $value = CharacteristicUtils::canonicalize($value, $this->mysql->getConnection());
    $datatype = CharacteristicUtils::canonicalize($datatype, $this->mysql->getConnection());
    $hash = $this->getOccurrenceHash($type, $value, $datatype, $scope);
    $propertyId = $this->hasOccurrence($hash);
    if (!$propertyId) {
      $this->mysql->startTransaction(true);
      $query = 'INSERT INTO ' . $this->config['table']['occurrence'] . 
        ' (id, topic_id, type_id, value, datatype, hash) VALUES' .
        ' (NULL, '.$this->dbId.', ' . $type->dbId . ', "' . $value . '", "' . $datatype . '", "' . $hash . '")';
      $mysqlResult = $this->mysql->execute($query);
      $lastOccurrenceId = $mysqlResult->getLastId();
      
      $query = 'INSERT INTO ' . $this->config['table']['topicmapconstruct'] . 
        ' (occurrence_id, topicmap_id, parent_id) VALUES' .
        ' (' . $lastOccurrenceId . ', ' . $this->parent->dbId . ', ' . $this->dbId . ')';
      $this->mysql->execute($query);
      
      $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope, $this->topicMap, $this);
      $query = 'INSERT INTO ' . $this->config['table']['occurrence_scope'] . 
        ' (scope_id, occurrence_id) VALUES' .
        ' (' . $scopeObj->dbId . ', ' . $lastOccurrenceId . ')';
      $this->mysql->execute($query);
      
      $this->mysql->finishTransaction(true);
      
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId($type->dbId)
        ->setValue($value)
        ->setDataType($datatype);
      $this->parent->setConstructPropertyHolder($propertyHolder);
      $this->parent->setConstructParent($this);
      
      $occ = $this->parent->getConstructByVerifiedId('OccurrenceImpl-' . 
        $lastOccurrenceId);
      if (!$this->mysql->hasError()) {
        $occ->postInsert();
        $this->postSave();
      }
      return $occ;
    } else {
      $this->parent->setConstructParent($this);
      return $this->parent->getConstructByVerifiedId('OccurrenceImpl-' . $propertyId);
    }
  }
  
  /**
   * Returns the roles played by this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link RoleImpl}s to be returned. Default <var>null</var>.
   * @param TopicImpl The type of the {@link AssociationImpl} from which the
   * @return array An array containing a set of {@link RoleImpl}s played by this topic.
   */
  public function getRolesPlayed(Topic $type=null, Topic $assocType=null) {
    if (is_null($type) && is_null($assocType)) {
      return $this->getRolesPlayedUntyped();
    } elseif (!is_null($type) && is_null($assocType)) {
      return $this->getRolesPlayedByType($type);
    } elseif (!is_null($type) && !is_null($assocType)) {
      return $this->getRolesPlayedByTypeAssocType($type, $assocType);
    } else {
      return $this->getRolesPlayedByAssocType($assocType);
    }
  }

  /**
   * Returns the types of which this topic is an instance of.
   * 
   * This method may return only those types which where added by 
   * {@link addType(Topic $type)} and may ignore type-instance relationships 
   * (see {@link http://www.isotopicmaps.org/sam/sam-model/#sect-types}) which are modeled 
   * as association.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link TopicImpl}s.
   */
  public function getTypes() {
    $types = array();
    $query = 'SELECT type_id FROM ' . $this->config['table']['instanceof'] . 
      ' WHERE topic_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $type = $this->parent->getConstructByVerifiedId(__CLASS__ . '-' . $result['type_id']);
      $types[$type->getId()] = $type;
    }
    return array_values($types);
  }

  /**
   * Adds a type to this topic.
   * Implementations may or may not create an association for types added
   * by this method. In any case, every type which was added by this method 
   * must be returned by the {@link getTypes()} method.
   * 
   * @param TopicImpl The type of which this topic should become an instance of.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>type</var> does not belong 
   *        to the parent topic map.
   */
  public function addType(Topic $type) {
    if (!$this->topicMap->equals($type->topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . parent::SAME_TM_CONSTRAINT_ERR_MSG
      );
    }
    // duplicate suppression
    $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['instanceof'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' AND type_id = ' . $type->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    if ($result[0] == 0) {
      $query = 'INSERT INTO ' . $this->config['table']['instanceof'] . 
        ' (topic_id, type_id) VALUES' .
        ' (' . $this->dbId . ', ' . $type->dbId . ')';
      $this->mysql->execute($query);
      if (!$this->mysql->hasError()) {
        $this->postSave();
      }
    }
  }

  /**
   * Removes a type from this topic.
   *
   * @param TopicImpl The type to remove.
   * @return void
   */
  public function removeType(Topic $type) {
    $query = 'DELETE FROM ' . $this->config['table']['instanceof'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' AND type_id = ' . $type->dbId;
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      $this->postSave();
    }
  }

  /**
   * Returns the {@link ConstructImpl} which is reified by this topic.
   *
   * @return Reifiable|null The {@link Reifiable} that is reified by this topic or 
   *        <var>null</var> if this topic does not reify a statement.
   */
  public function getReified() {
    $query = 'SELECT * ' . 
      ' FROM ' . $this->config['table']['topicmapconstruct'] . 
      ' WHERE reifier_id = ' . $this->dbId . 
      ' AND topicmap_id = ' . $this->parent->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    return $rows > 0
      ? $this->factory($mysqlResult)
      : null;
  }

  /**
   * Merges another topic into this topic.
   * Merging a topic into this topic causes this topic to gain all 
   * of the characteristics of the other topic and to replace the other 
   * topic wherever it is used as type, theme, or reifier. 
   * After this method completes, <var>other</var> will have been removed from 
   * the {@link TopicMapImpl}.
   * 
   * NOTE: The other topic MUST belong to the same {@link TopicMapImpl} instance 
   * as this topic! 
   * 
   * @param TopicImpl The topic to be merged into this topic.
   * @return void
   * @throws InvalidArgumentException If the other topic to be merged does not belong 
   *        to the same topic map.
   * @throws ModelConstraintException If the two topics to be merged reify different 
   *        Topic Maps constructs.
   */
  public function mergeIn(Topic $other) {
    if ($this->equals($other)) {
      return;
    }
    if (!$this->parent->equals($other->parent)) {
      throw new InvalidArgumentException(
        __METHOD__ . ': Both topics must belong to the same topic map!'
      );
    }
    if (!is_null($this->getReified()) && !is_null($other->getReified())) {
      if (!$this->getReified()->equals($other->getReified())) {
        throw new ModelConstraintException(
          $this, __METHOD__ . ': Topics reify different Topic Maps constructs!'
        );
      }
    }
    
    $this->mysql->startTransaction(true);
    
    // type properties and typing in instanceof
    $query = 'UPDATE ' . $this->config['table']['instanceof'] . 
      ' SET topic_id = ' . $this->dbId . ' WHERE topic_id = ' . $other->dbId;
    $this->mysql->execute($query);
    $query = 'UPDATE ' . $this->config['table']['instanceof'] . 
      ' SET type_id = ' . $this->dbId . ' WHERE type_id = ' . $other->dbId;
    $this->mysql->execute($query);
    
    // clean up: remove possible duplicates from qtm_instanceof
    $query = 'SELECT MIN(id) AS protected_id, topic_id, type_id, COUNT(*)' .
      ' FROM ' . $this->config['table']['instanceof'] . 
      ' WHERE (topic_id = ' . $this->dbId . ' OR type_id = ' . $this->dbId . ')' . 
      ' GROUP BY topic_id, type_id HAVING COUNT(*) > 1';
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $query = 'DELETE FROM ' . $this->config['table']['instanceof'] .
        ' WHERE id <> ' . $result['protected_id'] . 
        ' AND type_id = ' . $result['type_id'] . 
        ' AND topic_id = ' . $result['topic_id'];
      $this->mysql->execute($query);
    }
    
    // clean up: remove possible self typing
    $query = 'DELETE FROM ' . $this->config['table']['instanceof'] .
      ' WHERE topic_id = type_id';
    $this->mysql->execute($query);
    
    // get the affected scopes in order to be able to update the constructs hashes
    $affectedScopeIds = array();
    $query = 'SELECT scope_id FROM ' . $this->config['table']['theme'] .
      ' WHERE topic_id = ' . $other->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $affectedScopeIds[] = $result['scope_id'];
    }
    
    // scope properties (themes)
    $query = 'UPDATE ' . $this->config['table']['theme'] . 
      ' SET topic_id = ' . $this->dbId . ' WHERE topic_id = ' . $other->dbId;
    $this->mysql->execute($query);
    
    // update the scoped constructs hashes
    if (!empty($affectedScopeIds)) {
      
      $implodedScopeIds = implode(',', $affectedScopeIds);
      
      // occurrences
      $query = 'SELECT t1.id AS occ_id, t1.type_id, t1.value, t1.datatype 
      	FROM ' . $this->config['table']['occurrence'] . ' t1
        INNER JOIN ' . $this->config['table']['occurrence_scope'] . ' t2 
        ON t1.id = t2.occurrence_id WHERE t2.scope_id IN(' . $implodedScopeIds . ')';
      $mysqlResult = $this->mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $propertyHolder = new PropertyUtils();
        $propertyHolder->setTypeId((int)$result['type_id'])
          ->setValue($result['value'])
          ->setDataType($result['datatype']);
        $this->parent->setConstructPropertyHolder($propertyHolder);
        
        $this->parent->setConstructParent($this);
        
        $occurrence = $this->parent->getConstructByVerifiedId(
        	'OccurrenceImpl-' . $result['occ_id']
        );
        $hash = $this->getOccurrenceHash(
          $occurrence->getType(), 
          $occurrence->getValue(), 
          $occurrence->getDatatype(), 
          $occurrence->getScope()
        );
        $this->updateOccurrenceHash($occurrence->dbId, $hash);
      }
      
      // names
      $query = 'SELECT t1.id AS name_id, t1.type_id, t1.value 
      	FROM ' . $this->config['table']['topicname'] . ' t1
        INNER JOIN ' . $this->config['table']['topicname_scope'] . ' t2 
        ON t1.id = t2.topicname_id WHERE t2.scope_id IN(' . $implodedScopeIds . ')';
      $mysqlResult = $this->mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $propertyHolder = new PropertyUtils();
        $propertyHolder->setTypeId((int)$result['type_id'])->setValue($result['value']);
        $this->parent->setConstructPropertyHolder($propertyHolder);
        
        $this->parent->setConstructParent($this);
        
        $name = $this->parent->getConstructByVerifiedId('NameImpl-' . $result['name_id']);
        $hash = $this->getNameHash(
          $name->getValue(), 
          $name->getType(), 
          $name->getScope()
        );
        $this->updateNameHash($name->dbId, $hash);
      }
      
      // variants
      $query = 'SELECT t1.id AS variant_id, t1.value, t1.datatype 
      	FROM ' . $this->config['table']['variant'] . ' t1
        INNER JOIN ' . $this->config['table']['variant_scope'] . ' t2 
        ON t1.id = t2.variant_id WHERE t2.scope_id IN(' . $implodedScopeIds . ')';
      $mysqlResult = $this->mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $propertyHolder = new PropertyUtils();
        $propertyHolder->setValue($result['value'])->setDataType($result['datatype']);
        $this->parent->setConstructPropertyHolder($propertyHolder);
        
        $variant = $this->parent->getConstructByVerifiedId('VariantImpl-' . $result['variant_id']);
        $parent = $variant->getParent();
        $hash = $parent->getVariantHash(
          $variant->getValue(), 
          $variant->getDatatype(), 
          $variant->getScope()
        );
        $parent->updateVariantHash($variant->dbId, $hash);
      }
      
      // associations
      $query = 'SELECT t1.id AS assoc_id, t1.type_id 
      	FROM ' . $this->config['table']['association'] . ' t1
        INNER JOIN ' . $this->config['table']['association_scope'] . ' t2 
        ON t1.id = t2.association_id WHERE t2.scope_id IN(' . $implodedScopeIds . ')';
      $mysqlResult = $this->mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $propertyHolder = new PropertyUtils();
        $propertyHolder->setTypeId((int)$result['type_id']);
        $this->parent->setConstructPropertyHolder($propertyHolder);
        
        $assoc = $this->parent->getConstructByVerifiedId(
          'AssociationImpl-' . $result['assoc_id']
        );
        $hash = $this->parent->getAssocHash(
          $assoc->getType(), 
          $assoc->getScope(), 
          $assoc->getRoles()
        );
        $this->parent->updateAssocHash($assoc->dbId, $hash);
      }
    }
    
    // clean up: remove possible duplicates from qtm_theme
    $query = 'SELECT MIN(id) AS protected_id, scope_id, topic_id, COUNT(*)' .
      ' FROM ' . $this->config['table']['theme'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' GROUP BY scope_id, topic_id HAVING COUNT(*) > 1';
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $query = 'DELETE FROM ' . $this->config['table']['theme'] .
        ' WHERE id <> ' . $result['protected_id'] . 
        ' AND scope_id = ' . $result['scope_id'] . 
        ' AND topic_id = ' . $result['topic_id'];
      $this->mysql->execute($query);
    }
    
    // roles properties
    $query = 'UPDATE ' . $this->config['table']['assocrole'] . 
      ' SET player_id = ' . $this->dbId . ' WHERE player_id = ' . $other->dbId;
    $this->mysql->execute($query);
    
    // type properties in roles
    $query = 'UPDATE ' . $this->config['table']['assocrole'] . 
      ' SET type_id = ' . $this->dbId . ' WHERE type_id = ' . $other->dbId;
    $this->mysql->execute($query);
    
    // clean up: remove possible duplicates from qtm_assocrole
    $query = 'SELECT MIN(id) AS protected_id, ' .
                    'association_id, ' .
                    'type_id, ' .
                    'player_id, ' .
                    'COUNT(*)' .
      ' FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE (type_id = ' . $this->dbId . ' OR player_id = ' . $this->dbId . ')' . 
      ' GROUP BY association_id, type_id, player_id HAVING COUNT(*) > 1';
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $query = 'DELETE FROM ' . $this->config['table']['assocrole'] .
        ' WHERE id <> ' . $result['protected_id'] . 
        ' AND association_id = ' . $result['association_id'] . 
        ' AND type_id = ' . $result['type_id'] . 
        ' AND player_id = ' . $result['player_id'];
      $this->mysql->execute($query);
    }
    
    /* type properties in association; 
      use get and set in order to update the hash and remove dupl. */
    $assocs = $this->parent->getAssociationsByType($other);
    foreach ($assocs as $assoc) {
      $assoc->setType($this);
      $this->parent->finished($assoc);
    }
    
    /* type properties in occurrence; 
      use get and set in order to update the hash and remove dupl. */
    $occs = $this->getAllOccurrencesByType($other);
    foreach ($occs as $occ) {
      $occ->setType($this);
      $occ->getParent()->finished($occ);
    }
    
    /* type properties in name; 
      use get and set in order to update the hash and remove dupl. */
    $names = $this->getAllNamesByType($other);
    foreach ($names as $name) {
      $name->setType($this);
      $name->getParent()->finished($name);
    }

    // reifier properties
    $query = 'UPDATE ' . $this->config['table']['topicmapconstruct'] . 
      ' SET reifier_id = ' . $this->dbId . ' WHERE reifier_id = ' . $other->dbId;
    $this->mysql->execute($query);
    
    // merge other's names
    $othersNames = $other->getNames();
    foreach ($othersNames as $othersName) {
      $name = $this->createName( 
                                $othersName->getValue(), 
                                $othersName->getType(), 
                                $othersName->getScope()
                              );
      // other's name's iids
      $name->gainItemIdentifiers($othersName);
      
      // other's name's reifier
      $name->gainReifier($othersName);

      // other's name's variants
      $othersNameVariants = $othersName->getVariants();
      foreach ($othersNameVariants as $othersNameVariant) {
        $variant = $name->createVariant($othersNameVariant->getValue(), 
                                          $othersNameVariant->getDatatype(), 
                                          $othersNameVariant->getScope()
                                        );
        // other's variant's iids
        $variant->gainItemIdentifiers($othersNameVariant);

        // other's variant's reifier
        $variant->gainReifier($othersNameVariant);
      }
    }
    
    // merge other's occurrences
    $othersOccurrences = $other->getOccurrences();
    foreach ($othersOccurrences as $othersOccurrence) {
      $occ = $this->createOccurrence($othersOccurrence->getType(), 
                                      $othersOccurrence->getValue(), 
                                      $othersOccurrence->getDatatype(), 
                                      $othersOccurrence->getScope()
                                    );
      // other's occurrence's iids
      $occ->gainItemIdentifiers($othersOccurrence);

      // other's occurrence's reifier
      $occ->gainReifier($othersOccurrence);
    }
    
    // merge other's sids
    $othersSids = $other->getSubjectIdentifiers();
    foreach ($othersSids as $othersSid) {
      $other->removeSubjectIdentifier($othersSid);// prevent nested merging
      $this->addSubjectIdentifier($othersSid);
    }
    
    // merge other's slos
    $othersSlos = $other->getSubjectLocators();
    foreach ($othersSlos as $othersSlo) {
      $other->removeSubjectLocator($othersSlo);// prevent nested merging
      $this->addSubjectLocator($othersSlo);
    }
    
    // merge other's iids
    $othersIids = $other->getItemIdentifiers();
    foreach ($othersIids as $othersIid) {
      $other->removeItemIdentifier($othersIid);// prevent nested merging
      $this->addItemIdentifier($othersIid);
    }
    
    $other->remove();

    $this->mysql->finishTransaction(true);
    
    if (!$this->mysql->hasError()) {
      $this->postSave();
    }
  }
  
  /**
   * Deletes this topic.
   * 
   * @override
   * @return void
   * @throws {@link TopicInUseException} If the topic plays a {@link RoleImpl}, is used as type 
   * of a {@link Typed} construct, or if it is used as theme for a {@link ScopedImpl} 
   * construct, or if it reifies a {@link Reifiable}.
   */
  public function remove() {
    if ($this->isType()) {
      throw new TopicInUseException($this, __METHOD__ . ': Topic is typing one or more constructs!');
    }
    if ($this->playsRole()) {
      throw new TopicInUseException($this, __METHOD__ . ': Topic plays one or more roles!');
    }
    if ($this->isTheme()) {
      throw new TopicInUseException($this, __METHOD__ . ': Topic is a theme!');
    }
    if ($this->getReified() instanceof Reifiable) {
      throw new TopicInUseException($this, __METHOD__ . ': Topic is a reifier!');
    }
    $this->preDelete();
    $query = 'DELETE FROM ' . $this->config['table']['topic'] . 
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      $this->parent->removeTopicFromCache($this->getId());
      $this->id = 
      $this->dbId = null;
    }
  }
  
  /**
   * Tells the topic map system that a property modification is finished and 
   * duplicate removal can take place.
   * 
   * Note: This may be a resource consuming process.
   * 
   * @param ConstructImpl The property.
   * @return void
   */
  public function finished(Construct $property) {
    $className = get_class($property);
    $table = $className == 'NameImpl' ? 'topicname' : 'occurrence';
    // get the hash of the finished property
    $query = 'SELECT hash FROM ' . $this->config['table'][$table] . 
      ' WHERE id = ' . $property->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    // detect duplicates
    $query = 'SELECT id FROM ' . $this->config['table'][$table] . 
      ' WHERE hash = "' . $result['hash'] . '"' . 
      ' AND id <> ' . $property->dbId . 
      ' AND topic_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {// there exist duplicates
      while ($result = $mysqlResult->fetch()) {
        $this->parent->setConstructParent($this);
        $duplicate = $this->parent->getConstructByVerifiedId($className . '-' . 
          $result['id']);
        // gain duplicate's item identities
        $property->gainItemIdentifiers($duplicate);
        // gain duplicate's reifier
        $property->gainReifier($duplicate);
        if ($property instanceof Name) {
          $property->gainVariants($duplicate);
        }
        $duplicate->remove();
      }
    }
  }
  
  /**
   * Gets a name hash.
   * 
   * @param string The name value.
   * @param TopicImpl The name type.
   * @param array The scope.
   * @return string
   */
  protected function getNameHash($value, Topic $type, array $scope) {
    if (empty($scope)) {
      return md5($value . $type->dbId);
    } else {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) {
          $ids[$theme->dbId] = $theme->dbId;
        }
      }
      ksort($ids);
      $idsImploded = implode('', $ids);
      return md5($value . $type->dbId . $idsImploded);
    }
  }
  
  /**
   * Gets an occurrence hash.
   * 
   * @param TopicImpl The occurrence type.
   * @param string The occurrence value.
   * @param string The occurrence datatype.
   * @param array The scope.
   * @return string
   */
  protected function getOccurrenceHash(Topic $type, $value, $datatype, array $scope) {
    if (empty($scope)) {
      return md5($value . $datatype . $type->dbId);
    } else {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) {
          $ids[$theme->dbId] = $theme->dbId;
        }
      }
      ksort($ids);
      $idsImploded = implode('', $ids);
      return md5($value . $datatype . $type->dbId . $idsImploded);
    }
  }
  
  /**
   * Updates a name hash.
   * 
   * @param int The name id.
   * @param string The name hash.
   * @return void
   */
  protected function updateNameHash($nameId, $hash) {
    $query = 'UPDATE ' . $this->config['table']['topicname'] . 
      ' SET hash = "' . $hash . '"' .
      ' WHERE id = ' . $nameId;
    $this->mysql->execute($query);
  }
  
  /**
   * Updates an occurrence hash.
   * 
   * @param int The occurrence id.
   * @param string The occurrence hash.
   * @return void
   */
  protected function updateOccurrenceHash($occId, $hash) {
    $query = 'UPDATE ' . $this->config['table']['occurrence'] . 
      ' SET hash = "' . $hash . '"' .
      ' WHERE id = ' . $occId;
    $this->mysql->execute($query);
  }
  
  /**
   * Checks if this topic has a certain name.
   * 
   * @param string The hash code.
   * @return false|int The name id or <var>false</var> otherwise.
   */
  protected function hasName($hash) {
    $query = 'SELECT id FROM ' . $this->config['table']['topicname'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' AND hash = "' . $hash . '"';
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return (int) $result['id'];
    }
    return false;
  }
  
  /**
   * Checks if this topic has a certain occurrence.
   * 
   * @param string The hash code.
   * @return false|int The occurrence id or <var>false</var> otherwise.
   */
  protected function hasOccurrence($hash) {
    $query = 'SELECT id FROM ' . $this->config['table']['occurrence'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' AND hash = "' . $hash . '"';
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return (int) $result['id'];
    }
    return false;
  }
  
  /**
   * Gets the default name type.
   * 
   * @return TopicImpl
   */
  private function getDefaultNameType() {
    if (!is_null($this->defaultNameType)) {
      return $this->defaultNameType; 
    }
    $sid = VocabularyUtils::TMDM_PSI_DEFAULT_NAME_TYPE;
    $query = $query = 'SELECT t1.id FROM ' . $this->config['table']['topic'] . ' AS t1' .
      ' INNER JOIN ' . $this->config['table']['subjectidentifier'] . ' t2' .
      ' ON t1.id = t2.topic_id' .
      ' WHERE t2.locator = "' . $sid . '"' .
      ' AND t1.topicmap_id = ' . $this->parent->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      $nameType = $this->parent->getConstructByVerifiedId(__CLASS__ . '-' . $result['id']);
      return $this->defaultNameType = $nameType;
    } else {
      return $this->defaultNameType = $this->parent->createTopicBySubjectIdentifier($sid);
    }
  }
  
  /**
   * Checks if topic plays a role.
   * 
   * @return boolean
   */
  private function playsRole() {
    $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE player_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    return $result[0] == 0 ? false : true;
  }
  
  /**
   * Checks if topic is used as type.
   * 
   * @return boolean
   */
  private function isType() {
    $tableNames = array(
      'instanceof',
      'association',
      'assocrole',
      'occurrence',
      'topicname'
    );
    foreach ($tableNames as $tableName) {
      $query = 'SELECT COUNT(*) FROM ' . $this->config['table'][$tableName] . 
        ' WHERE type_id = ' . $this->dbId;
      $mysqlResult = $this->mysql->execute($query);
      $result = $mysqlResult->fetchArray();
      if ($result[0] > 0) return true;
    }
    return false;
  }
  
  /**
   * Checks if topic is a theme.
   * 
   * @return boolean
   */
  private function isTheme() {
    $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['theme'] . 
      ' WHERE topic_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    return $result[0] == 0 ? false : true;
  }
  
  /**
   * Gets all occurrences of a given type in this topic map (the parent construct).
   * Helper function for self::mergeIn().
   * 
   * @param TopicImpl The occurrence type.
   * @return array An array containing occurrences.
   */
  private function getAllOccurrencesByType(Topic $type) {
    $occurrences = array();
    $query = 'SELECT t1.id AS occ_id, t2.id AS topic_id FROM ' . $this->config['table']['occurrence'] . ' t1' . 
      ' INNER JOIN ' . $this->config['table']['topic'] . ' t2' . 
      ' ON t2.id = t1.topic_id' . 
      ' WHERE t2.topicmap_id = ' . $this->parent->dbId . 
      ' AND t1.type_id = ' . $type->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $parent = $this->parent->getConstructByVerifiedId(__CLASS__ . '-' . $result['topic_id']);
      $this->parent->setConstructParent($parent);
      $occurrence = $this->parent->getConstructByVerifiedId('OccurrenceImpl-' . $result['occ_id']);
      $occurrences[] = $occurrence;
    }
    return $occurrences;
  }
  
  /**
   * Gets all topic names of a given type in this topic map (the parent construct).
   * Helper function for self::mergeIn().
   * 
   * @param TopicImpl The name type.
   * @return array An array containing topic names.
   */
  private function getAllNamesByType(Topic $type) {
    $names = array();
    $query = 'SELECT t1.id AS name_id, t2.id AS topic_id FROM ' . $this->config['table']['topicname'] . ' t1' . 
      ' INNER JOIN ' . $this->config['table']['topic'] . ' t2' . 
      ' ON t2.id = t1.topic_id' . 
      ' WHERE t2.topicmap_id = ' . $this->parent->dbId . 
      ' AND t1.type_id = ' . $type->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $parent = $this->parent->getConstructByVerifiedId(__CLASS__ . '-' . $result['topic_id']);
      $this->parent->setConstructParent($parent);
      $name = $this->parent->getConstructByVerifiedId('NameImpl-' . $result['name_id']);
      $names[] = $name;
    }
    return $names;
  }
  
  /**
   * Returns the roles played by this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link RoleImpl}s to be returned. Default <var>null</var>.
   * @param TopicImpl The type of the {@link AssociationImpl} from which the
   * @return array An array containing a set of {@link RoleImpl}s played by this topic.
   */
  private function getRolesPlayedUntyped() {
    $roles = array();
    $query = 'SELECT t1.id AS role_id, t1.association_id, t1.type_id, t2.hash' .
    	' FROM ' . $this->config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->config['table']['association'] . ' t2' .
      ' ON t2.id = t1.association_id' .
      ' WHERE t1.player_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $assoc = $this->parent->getConstructByVerifiedId(
      	'AssociationImpl-' . $result['association_id']
      );
      if (is_null($assoc)) {
        continue;
      }
      $this->parent->setConstructParent($assoc);
      $role = $this->parent->getConstructByVerifiedId('RoleImpl-' . $result['role_id']);
      $roles[$result['hash'] . $result['type_id'] . $this->dbId] = $role;
    }
    return array_values($roles);
  }

  /**
   * Returns the roles played by this topic where the role type is <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link RoleImpl}s to be returned.
   * @return array An array containing a set of {@link RoleImpl}s with the specified 
   *        <var>type</var>.
   */
  private function getRolesPlayedByType(Topic $type) {
    $roles = array();
    $query = 'SELECT t1.id AS role_id, t1.association_id, t1.type_id, t2.hash' .
    	' FROM ' . $this->config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->config['table']['association'] . ' t2' . 
      ' ON t2.id = t1.association_id' . 
      ' WHERE t1.type_id = ' . $type->dbId . 
      ' AND t1.player_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $assoc = $this->parent->getConstructByVerifiedId(
      	'AssociationImpl-' . $result['association_id']
      );
      if (is_null($assoc)) {
        continue;
      }
      $this->parent->setConstructParent($assoc);
      $role = $this->parent->getConstructByVerifiedId('RoleImpl-' . $result['role_id']);
      $roles[$result['hash'] . $result['type_id'] . $this->dbId] = $role;
    }
    return array_values($roles);
  }

  /**
   * Returns the {@link RoleImpl}s played by this topic where the role type is
   * <var>type</var> and the {@link AssociationImpl} type is <var>assocType</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link RoleImpl}s to be returned.
   * @param TopicImpl The type of the {@link AssociationImpl} from which the
   *        returned roles must be part of.
   * @return array An array containing a set of {@link RoleImpl}s with the specified 
   *        <var>type</var> which are part of {@link AssociationImpl}s with the specified 
   *        <var>assocType</var>.
   */
  private function getRolesPlayedByTypeAssocType(Topic $type, Topic $assocType) {
    $roles = array();
    $query = 'SELECT t1.id AS role_id, t1.association_id, t1.type_id, t2.hash' . 
    	' FROM ' . $this->config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->config['table']['association'] . ' t2' .
      ' ON t2.id = t1.association_id' .
      ' WHERE t2.type_id = ' . $assocType->dbId . 
      ' AND t1.type_id = ' . $type->dbId . 
      ' AND t1.player_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $assoc = $this->parent->getConstructByVerifiedId(
      	'AssociationImpl-' . $result['association_id']
      );
      if (is_null($assoc)) {
        continue;
      }
      $this->parent->setConstructParent($assoc);
      $role = $this->parent->getConstructByVerifiedId('RoleImpl-' . $result['role_id']);
      $roles[$result['hash'] . $result['type_id'] . $this->dbId] = $role;
    }
    return array_values($roles);
  }
  
  /**
   * Returns the {@link RoleImpl}s played by this topic where the 
   * {@link AssociationImpl} type is <var>assocType</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link AssociationImpl} from which the
   *        returned roles must be part of.
   * @return array An array containing a set of {@link RoleImpl}s which are part of 
   *        {@link AssociationImpl}s with the specified <var>assocType</var>.
   */
  private function getRolesPlayedByAssocType(Topic $assocType) {
    $roles = array();
    $query = 'SELECT t1.id AS role_id, t1.association_id, t1.type_id, t2.hash' . 
    	' FROM ' . $this->config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->config['table']['association'] . ' t2' .
      ' ON t2.id = t1.association_id  ' .
      ' WHERE t2.type_id = ' . $assocType->dbId . 
      ' AND t1.player_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $assoc = $this->parent->getConstructByVerifiedId(
      	'AssociationImpl-' . $result['association_id']
      );
      if (is_null($assoc)) {
        continue;
      }
      $this->parent->setConstructParent($assoc);
      $role = $this->parent->getConstructByVerifiedId('RoleImpl-' . $result['role_id']);
      $roles[$result['hash'] . $result['type_id'] . $this->dbId] = $role;
    }
    return array_values($roles);
  }
}
?>