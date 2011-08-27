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
final class TopicImpl extends ConstructImpl implements Topic
{  
  /**
   * The default name type.
   * 
   * @var TopicImpl
   */
  private $_defaultNameType;
  
  /**
   * Constructor.
   * 
   * @param int The construct id in its table representation in the MySQL database.
   * @param Mysql The MySQL wrapper.
   * @param array The configuration data.
   * @param TopicMapImpl The parent topic map.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, TopicMap $parent)
  {
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $parent);
    $this->_defaultNameType = null;
  }

  /**
   * Returns the subject identifiers assigned to this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the subject identifiers.
   */
  public function getSubjectIdentifiers()
  {
    $sids = array();
    $query = 'SELECT locator FROM ' . $this->_config['table']['subjectidentifier'] . 
      ' WHERE topic_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
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
  public function addSubjectIdentifier($sid)
  {
    if (is_null($sid)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_identityNullErrMsg
      );
    }
    $sids = $this->getSubjectIdentifiers();
    if (in_array($sid, $sids)) {
      return;
    }
    // check others' subject identifiers
    $query = 'SELECT t2.id' .
      ' FROM ' . $this->_config['table']['subjectidentifier'] . ' t1' .
      ' INNER JOIN ' . $this->_config['table']['topic'] . ' t2' .
      ' ON t2.id = t1.topic_id' .
      ' WHERE t1.locator = "' . $sid . '"' .
      ' AND t2.topicmap_id = ' . $this->_topicMap->_dbId . 
      ' AND t2.id <> ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    if ($numRows == 0) {
      // check others' item identifiers too
      $query = 'SELECT t1.*' . 
        ' FROM ' . $this->_config['table']['topicmapconstruct'] . ' t1' . 
        ' INNER JOIN ' . $this->_config['table']['itemidentifier'] . ' t2' .
        ' ON t1.id = t2.topicmapconstruct_id' .
        ' WHERE t2.locator = "' . $sid . '"' . 
        ' AND t1.topicmap_id = ' . $this->_topicMap->_dbId . 
        ' AND t1.topic_id <> ' . $this->_dbId . 
        ' AND t1.topic_id IS NOT NULL';
      $mysqlResult = $this->_mysql->execute($query);
      $numRows = $mysqlResult->getNumRows();
      if ($numRows == 0) {// insert subject identifier
        $query = 'INSERT INTO ' . $this->_config['table']['subjectidentifier'] . 
          ' (topic_id, locator) VALUES (' . $this->_dbId . ', "' . $sid .'")';
        $this->_mysql->execute($query);
        if (!$this->_mysql->hasError()) {
          $this->_postSave();
        }
      } else {// merge
        $existingTopic = $this->_factory($mysqlResult);
        $this->mergeIn($existingTopic);
      }
    } else {// merge
      $result = $mysqlResult->fetch();
      $existingTopic = $this->_parent->_getConstructByVerifiedId(__CLASS__ . '-' . $result['id']);
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
  public function removeSubjectIdentifier($sid)
  {
    if (is_null($sid)) {
      return;
    }
    $query = 'DELETE FROM ' . $this->_config['table']['subjectidentifier'] . 
      ' WHERE topic_id = ' . $this->_dbId . 
      ' AND locator = "' . $sid . '"';
    $this->_mysql->execute($query);
    if (!$this->_mysql->hasError()) {
      $this->_postSave();
    }
  }

  /**
   * Returns the subject locators assigned to this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the subject locators.
   */
  public function getSubjectLocators()
  {
    $slos = array();
    $query = 'SELECT locator FROM ' . $this->_config['table']['subjectlocator'] . 
      ' WHERE topic_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
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
  public function addSubjectLocator($slo)
  {
    if (is_null($slo)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_identityNullErrMsg
      );
    }
    $slos = $this->getSubjectLocators();
    if (in_array($slo, $slos)) {
      return;
    }
    // check others' subject locators
    $query = 'SELECT t2.id' .
      ' FROM ' . $this->_config['table']['subjectlocator'] . ' t1' .
      ' INNER JOIN ' . $this->_config['table']['topic'] . ' t2' .
      ' ON t2.id = t1.topic_id' .
      ' WHERE t1.locator = "' . $slo . '"' .
      ' AND t2.topicmap_id = ' . $this->_topicMap->_dbId . 
      ' AND t2.id <> ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    if ($numRows == 0) {// insert subject locator
      $query = 'INSERT INTO ' . $this->_config['table']['subjectlocator'] . 
        ' (topic_id, locator) VALUES (' . $this->_dbId . ', "' . $slo .'")';
      $this->_mysql->execute($query);
      if (!$this->_mysql->hasError()) {
        $this->_postSave();
      }
    } else {// merge
      $result = $mysqlResult->fetch();
      $existingTopic = $this->_parent->_getConstructByVerifiedId(__CLASS__ . '-' . $result['id']);
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
  public function removeSubjectLocator($slo)
  {
    if (is_null($slo)) {
      return;
    }
    $query = 'DELETE FROM ' . $this->_config['table']['subjectlocator'] . 
      ' WHERE topic_id = ' . $this->_dbId . 
      ' AND locator = "' . $slo . '"';
    $this->_mysql->execute($query);
    if (!$this->_mysql->hasError()) {
      $this->_postSave();
    }
  }

  /**
   * Returns the names of this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param TopicImpl The type of the {@link NameImpl}s to be returned. Default <var>null</var>.
   * @return array An array containing a set of {@link NameImpl}s belonging to this topic.
   */
  public function getNames(Topic $type=null)
  {
    $names = array();
    $query = 'SELECT id, type_id, value, hash FROM ' . $this->_config['table']['topicname'] . 
      ' WHERE topic_id = ' . $this->_dbId;
    if (!is_null($type)) {
      $query .= ' AND type_id = ' . $type->_dbId;
    }
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['type_id'] = $result['type_id'];
      $propertyHolder['value'] = $result['value'];
      $this->_parent->_setConstructPropertyHolder($propertyHolder);
      
      $this->_parent->_setConstructParent($this);
      $name = $this->_parent->_getConstructByVerifiedId('NameImpl-' . $result['id']);
      
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
  public function createName($value, Topic $type=null, array $scope=array())
  {
    if (is_null($value)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_valueNullErrMsg
      );
    }
    $value = CharacteristicUtils::canonicalize($value, $this->_mysql->getConnection());
    $type = is_null($type) ? $this->_getDefaultNameType() : $type;
    
    if (!$this->_topicMap->equals($type->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
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
    $propertyHolder['type_id'] = $type->_dbId;
    $propertyHolder['value'] = $value;
    $this->_parent->_setConstructPropertyHolder($propertyHolder);
    
    $this->_parent->_setConstructParent($this);
      
    $hash = $this->_getNameHash($value, $type, $scope);
    $nameId = $this->_hasName($hash);
    
    if ($nameId) {
      return $this->_parent->_getConstructByVerifiedId('NameImpl-' . $nameId);
    }
    
    $this->_mysql->startTransaction(true);
    $query = 'INSERT INTO ' . $this->_config['table']['topicname'] . 
      ' (id, topic_id, type_id, value, hash) VALUES' .
      ' (NULL, ' . $this->_dbId . ', ' . $type->_dbId . ', "' . $value . '", "' . $hash . '")';
    $mysqlResult = $this->_mysql->execute($query);
    $lastNameId = $mysqlResult->getLastId();
    
    $query = 'INSERT INTO ' . $this->_config['table']['topicmapconstruct'] . 
      ' (topicname_id, topicmap_id, parent_id) VALUES' .
      ' (' . $lastNameId . ', ' . $this->_parent->_dbId . ', ' . $this->_dbId . ')';
    $this->_mysql->execute($query);
    
    $scopeObj = new ScopeImpl($this->_mysql, $this->_config, $scope, $this->_topicMap, $this);
    $query = 'INSERT INTO ' . $this->_config['table']['topicname_scope'] . 
      ' (scope_id, topicname_id) VALUES' .
      ' (' . $scopeObj->_dbId . ', ' . $lastNameId . ')';
    $this->_mysql->execute($query);
    
    $this->_mysql->finishTransaction(true);
    
    $name = $this->_parent->_getConstructByVerifiedId('NameImpl-' . $lastNameId);
    
    if (!$this->_mysql->hasError()) {
      $name->_postInsert();
      $this->_postSave();
    }
    return $name;
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
  public function getOccurrences(Topic $type=null)
  {
    $occurrences = array();
    $query = 'SELECT id, type_id, value, datatype, hash 
    	FROM ' . $this->_config['table']['occurrence'] . ' WHERE topic_id = ' . $this->_dbId;
    if (!is_null($type)) {
      $query .= ' AND type_id = ' . $type->_dbId;
    }
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['type_id'] = $result['type_id'];
      $propertyHolder['value'] = $result['value'];
      $propertyHolder['datatype'] = $result['datatype'];
      $this->_parent->_setConstructPropertyHolder($propertyHolder);
      
      $this->_parent->_setConstructParent($this);
      
      $occurrence = $this->_parent->_getConstructByVerifiedId(
      	'OccurrenceImpl-' . $result['id']
      );
      
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
  public function createOccurrence(Topic $type, $value, $datatype, array $scope=array())
  {
    if (!$this->_topicMap->equals($type->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
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
    $value = CharacteristicUtils::canonicalize($value, $this->_mysql->getConnection());
    $datatype = CharacteristicUtils::canonicalize($datatype, $this->_mysql->getConnection());
    
    $propertyHolder['type_id'] = $type->_dbId;
    $propertyHolder['value'] = $value;
    $propertyHolder['datatype'] = $datatype;
    $this->_parent->_setConstructPropertyHolder($propertyHolder);
    
    $this->_parent->_setConstructParent($this);
      
    $hash = $this->_getOccurrenceHash($type, $value, $datatype, $scope);
    $occurrenceId = $this->_hasOccurrence($hash);
    
    if ($occurrenceId) {
      return $this->_parent->_getConstructByVerifiedId('OccurrenceImpl-' . $occurrenceId);
    }
    
    $this->_mysql->startTransaction(true);
    $query = 'INSERT INTO ' . $this->_config['table']['occurrence'] . 
      ' (id, topic_id, type_id, value, datatype, hash) VALUES' .
      ' (NULL, '.$this->_dbId.', ' . $type->_dbId . ', "' . $value . '", "' . $datatype . '", "' . $hash . '")';
    $mysqlResult = $this->_mysql->execute($query);
    $lastOccurrenceId = $mysqlResult->getLastId();
    
    $query = 'INSERT INTO ' . $this->_config['table']['topicmapconstruct'] . 
      ' (occurrence_id, topicmap_id, parent_id) VALUES' .
      ' (' . $lastOccurrenceId . ', ' . $this->_parent->_dbId . ', ' . $this->_dbId . ')';
    $this->_mysql->execute($query);
    
    $scopeObj = new ScopeImpl($this->_mysql, $this->_config, $scope, $this->_topicMap, $this);
    $query = 'INSERT INTO ' . $this->_config['table']['occurrence_scope'] . 
      ' (scope_id, occurrence_id) VALUES' .
      ' (' . $scopeObj->_dbId . ', ' . $lastOccurrenceId . ')';
    $this->_mysql->execute($query);
    
    $this->_mysql->finishTransaction(true);
    
    $occurrence = $this->_parent->_getConstructByVerifiedId(
    	'OccurrenceImpl-' . $lastOccurrenceId
    );
    
    if (!$this->_mysql->hasError()) {
      $occurrence->_postInsert();
      $this->_postSave();
    }
    return $occurrence;
  }
  
  /**
   * Returns the roles played by this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link RoleImpl}s to be returned. Default <var>null</var>.
   * @param TopicImpl The type of the {@link AssociationImpl} from which the
   * @return array An array containing a set of {@link RoleImpl}s played by this topic.
   */
  public function getRolesPlayed(Topic $type=null, Topic $assocType=null)
  {
    if (is_null($type) && is_null($assocType)) {
      return $this->_getRolesPlayedUntyped();
    } elseif (!is_null($type) && is_null($assocType)) {
      return $this->_getRolesPlayedByType($type);
    } elseif (!is_null($type) && !is_null($assocType)) {
      return $this->_getRolesPlayedByTypeAssocType($type, $assocType);
    } else {
      return $this->_getRolesPlayedByAssocType($assocType);
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
  public function getTypes()
  {
    $types = array();
    $query = 'SELECT type_id FROM ' . $this->_config['table']['instanceof'] . 
      ' WHERE topic_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $type = $this->_parent->_getConstructByVerifiedId(__CLASS__ . '-' . $result['type_id']);
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
  public function addType(Topic $type)
  {
    if (!$this->_topicMap->equals($type->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
    // duplicate suppression
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['instanceof'] . 
      ' WHERE topic_id = ' . $this->_dbId . 
      ' AND type_id = ' . $type->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    if ($result[0] == 0) {
      $query = 'INSERT INTO ' . $this->_config['table']['instanceof'] . 
        ' (topic_id, type_id) VALUES' .
        ' (' . $this->_dbId . ', ' . $type->_dbId . ')';
      $this->_mysql->execute($query);
      if (!$this->_mysql->hasError()) {
        $this->_postSave();
      }
    }
  }

  /**
   * Removes a type from this topic.
   *
   * @param TopicImpl The type to remove.
   * @return void
   */
  public function removeType(Topic $type)
  {
    $query = 'DELETE FROM ' . $this->_config['table']['instanceof'] . 
      ' WHERE topic_id = ' . $this->_dbId . 
      ' AND type_id = ' . $type->_dbId;
    $this->_mysql->execute($query);
    if (!$this->_mysql->hasError()) {
      $this->_postSave();
    }
  }

  /**
   * Returns the {@link ConstructImpl} which is reified by this topic.
   *
   * @return Reifiable|null The {@link Reifiable} that is reified by this topic or 
   *        <var>null</var> if this topic does not reify a statement.
   */
  public function getReified()
  {
    $query = 'SELECT * ' . 
      ' FROM ' . $this->_config['table']['topicmapconstruct'] . 
      ' WHERE reifier_id = ' . $this->_dbId . 
      ' AND topicmap_id = ' . $this->_parent->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    return $numRows > 0
      ? $this->_factory($mysqlResult)
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
  public function mergeIn(Topic $other)
  {
    if ($this->equals($other)) {
      return;
    }
    if (!$this->_parent->equals($other->_parent)) {
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
    
    $this->_mysql->startTransaction(true);
    
    // type properties and typing in table qtm_instanceof
    $query = 'UPDATE ' . $this->_config['table']['instanceof'] . 
      ' SET topic_id = ' . $this->_dbId . ' WHERE topic_id = ' . $other->_dbId;
    $this->_mysql->execute($query);
    $query = 'UPDATE ' . $this->_config['table']['instanceof'] . 
      ' SET type_id = ' . $this->_dbId . ' WHERE type_id = ' . $other->_dbId;
    $this->_mysql->execute($query);
    
    // clean up: remove possible duplicates from table qtm_instanceof
    $query = 'SELECT MIN(id) AS protected_id, topic_id, type_id, COUNT(*)' .
      ' FROM ' . $this->_config['table']['instanceof'] . 
      ' WHERE (topic_id = ' . $this->_dbId . ' OR type_id = ' . $this->_dbId . ')' . 
      ' GROUP BY topic_id, type_id HAVING COUNT(*) > 1';
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $query = 'DELETE FROM ' . $this->_config['table']['instanceof'] .
        ' WHERE id <> ' . $result['protected_id'] . 
        ' AND type_id = ' . $result['type_id'] . 
        ' AND topic_id = ' . $result['topic_id'];
      $this->_mysql->execute($query);
    }
    
    // clean up: remove possible self typing
    $query = 'DELETE FROM ' . $this->_config['table']['instanceof'] .
      ' WHERE topic_id = type_id';
    $this->_mysql->execute($query);
    
    // get the affected scopes in order to be able to update the constructs hashes
    $affectedScopeIds = array();
    $query = 'SELECT scope_id FROM ' . $this->_config['table']['theme'] .
      ' WHERE topic_id = ' . $other->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $affectedScopeIds[] = $result['scope_id'];
    }
    
    // scope properties (themes)
    $query = 'UPDATE ' . $this->_config['table']['theme'] . 
      ' SET topic_id = ' . $this->_dbId . ' WHERE topic_id = ' . $other->_dbId;
    $this->_mysql->execute($query);
    
    // update the scoped constructs hashes
    if (!empty($affectedScopeIds)) {
      
      $implodedScopeIds = implode(',', $affectedScopeIds);
      
      // occurrences
      $query = 'SELECT t1.id AS occ_id, t1.type_id, t1.value, t1.datatype 
      	FROM ' . $this->_config['table']['occurrence'] . ' t1
        INNER JOIN ' . $this->_config['table']['occurrence_scope'] . ' t2 
        ON t1.id = t2.occurrence_id WHERE t2.scope_id IN(' . $implodedScopeIds . ')';
      $mysqlResult = $this->_mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $propertyHolder['type_id'] = $result['type_id'];
        $propertyHolder['value'] = $result['value'];
        $propertyHolder['datatype'] = $result['datatype'];
        $this->_parent->_setConstructPropertyHolder($propertyHolder);
        
        $this->_parent->_setConstructParent($this);
        
        $occurrence = $this->_parent->_getConstructByVerifiedId(
        	'OccurrenceImpl-' . $result['occ_id']
        );
        $hash = $this->_getOccurrenceHash(
          $occurrence->getType(), 
          $occurrence->getValue(), 
          $occurrence->getDatatype(), 
          $occurrence->getScope()
        );
        $this->_updateOccurrenceHash($occurrence->_dbId, $hash);
      }
      
      // names
      $query = 'SELECT t1.id AS name_id, t1.type_id, t1.value 
      	FROM ' . $this->_config['table']['topicname'] . ' t1
        INNER JOIN ' . $this->_config['table']['topicname_scope'] . ' t2 
        ON t1.id = t2.topicname_id WHERE t2.scope_id IN(' . $implodedScopeIds . ')';
      $mysqlResult = $this->_mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $propertyHolder['type_id'] = $result['type_id'];
        $propertyHolder['value'] = $result['value'];
        $this->_parent->_setConstructPropertyHolder($propertyHolder);
        
        $this->_parent->_setConstructParent($this);
        
        $name = $this->_parent->_getConstructByVerifiedId('NameImpl-' . $result['name_id']);
        $hash = $this->_getNameHash(
          $name->getValue(), 
          $name->getType(), 
          $name->getScope()
        );
        $this->_updateNameHash($name->_dbId, $hash);
      }
      
      // variants
      $query = 'SELECT t1.id AS variant_id, t1.value, t1.datatype 
      	FROM ' . $this->_config['table']['variant'] . ' t1
        INNER JOIN ' . $this->_config['table']['variant_scope'] . ' t2 
        ON t1.id = t2.variant_id WHERE t2.scope_id IN(' . $implodedScopeIds . ')';
      $mysqlResult = $this->_mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $propertyHolder['value'] = $result['value'];
        $propertyHolder['datatype'] = $result['datatype'];
        $this->_parent->_setConstructPropertyHolder($propertyHolder);
        
        $variant = $this->_parent->_getConstructByVerifiedId('VariantImpl-' . $result['variant_id']);
        $parent = $variant->getParent();
        $hash = $parent->_getVariantHash(
          $variant->getValue(), 
          $variant->getDatatype(), 
          $variant->getScope()
        );
        $parent->_updateVariantHash($variant->_dbId, $hash);
      }
      
      // associations
      $query = 'SELECT t1.id AS assoc_id, t1.type_id 
      	FROM ' . $this->_config['table']['association'] . ' t1
        INNER JOIN ' . $this->_config['table']['association_scope'] . ' t2 
        ON t1.id = t2.association_id WHERE t2.scope_id IN(' . $implodedScopeIds . ')';
      $mysqlResult = $this->_mysql->execute($query);
      while ($result = $mysqlResult->fetch()) {
        $propertyHolder['type_id'] = $result['type_id'];
        $this->_parent->_setConstructPropertyHolder($propertyHolder);
        
        $assoc = $this->_parent->_getConstructByVerifiedId(
          'AssociationImpl-' . $result['assoc_id']
        );
        $hash = $this->_parent->_getAssocHash(
          $assoc->getType(), 
          $assoc->getScope(), 
          $assoc->_getRoles()
        );
        $this->_parent->_updateAssocHash($assoc->_dbId, $hash);
      }
    }
    
    // clean up: remove possible duplicates from qtm_theme
    $query = 'SELECT MIN(id) AS protected_id, scope_id, topic_id, COUNT(*)' .
      ' FROM ' . $this->_config['table']['theme'] . 
      ' WHERE topic_id = ' . $this->_dbId . 
      ' GROUP BY scope_id, topic_id HAVING COUNT(*) > 1';
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $query = 'DELETE FROM ' . $this->_config['table']['theme'] .
        ' WHERE id <> ' . $result['protected_id'] . 
        ' AND scope_id = ' . $result['scope_id'] . 
        ' AND topic_id = ' . $result['topic_id'];
      $this->_mysql->execute($query);
    }
    
    // roles properties
    $query = 'UPDATE ' . $this->_config['table']['assocrole'] . 
      ' SET player_id = ' . $this->_dbId . ' WHERE player_id = ' . $other->_dbId;
    $this->_mysql->execute($query);
    
    // type properties in roles
    $query = 'UPDATE ' . $this->_config['table']['assocrole'] . 
      ' SET type_id = ' . $this->_dbId . ' WHERE type_id = ' . $other->_dbId;
    $this->_mysql->execute($query);
    
    // clean up: remove possible duplicates from qtm_assocrole
    $query = 'SELECT MIN(id) AS protected_id, ' .
                    'association_id, ' .
                    'type_id, ' .
                    'player_id, ' .
                    'COUNT(*)' .
      ' FROM ' . $this->_config['table']['assocrole'] . 
      ' WHERE (type_id = ' . $this->_dbId . ' OR player_id = ' . $this->_dbId . ')' . 
      ' GROUP BY association_id, type_id, player_id HAVING COUNT(*) > 1';
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $query = 'DELETE FROM ' . $this->_config['table']['assocrole'] .
        ' WHERE id <> ' . $result['protected_id'] . 
        ' AND association_id = ' . $result['association_id'] . 
        ' AND type_id = ' . $result['type_id'] . 
        ' AND player_id = ' . $result['player_id'];
      $this->_mysql->execute($query);
    }
    
    /* type properties in association; 
      use get and set in order to update the hash and remove dupl. */
    $assocs = $this->_parent->getAssociationsByType($other);
    foreach ($assocs as $assoc) {
      $assoc->setType($this);
      $this->_parent->finished($assoc);
    }
    
    /* type properties in occurrence; 
      use get and set in order to update the hash and remove dupl. */
    $occurrences = $this->_getAllOccurrencesByType($other);
    foreach ($occurrences as $occurrence) {
      $occurrence->setType($this);
      $occurrence->getParent()->finished($occurrence);
    }
    
    /* type properties in name; 
      use get and set in order to update the hash and remove dupl. */
    $names = $this->_getAllNamesByType($other);
    foreach ($names as $name) {
      $name->setType($this);
      $name->getParent()->finished($name);
    }

    // reifier properties
    $query = 'UPDATE ' . $this->_config['table']['topicmapconstruct'] . 
      ' SET reifier_id = ' . $this->_dbId . ' WHERE reifier_id = ' . $other->_dbId;
    $this->_mysql->execute($query);
    
    // merge other's names
    $othersNames = $other->getNames();
    foreach ($othersNames as $othersName) {
      $name = $this->createName( 
                                $othersName->getValue(), 
                                $othersName->getType(), 
                                $othersName->getScope()
                              );
      // other's name's iids
      $name->_gainItemIdentifiers($othersName);
      
      // other's name's reifier
      $name->_gainReifier($othersName);

      // other's name's variants
      $othersNameVariants = $othersName->getVariants();
      foreach ($othersNameVariants as $othersNameVariant) {
        $variant = $name->createVariant($othersNameVariant->getValue(), 
                                          $othersNameVariant->getDatatype(), 
                                          $othersNameVariant->getScope()
                                        );
        // other's variant's iids
        $variant->_gainItemIdentifiers($othersNameVariant);

        // other's variant's reifier
        $variant->_gainReifier($othersNameVariant);
      }
    }
    
    // merge other's occurrences
    $othersOccurrences = $other->getOccurrences();
    foreach ($othersOccurrences as $othersOccurrence) {
      $occurrence = $this->createOccurrence($othersOccurrence->getType(), 
        $othersOccurrence->getValue(), 
        $othersOccurrence->getDatatype(), 
        $othersOccurrence->getScope()
      );
      // other's occurrence's iids
      $occurrence->_gainItemIdentifiers($othersOccurrence);

      // other's occurrence's reifier
      $occurrence->_gainReifier($othersOccurrence);
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

    $this->_mysql->finishTransaction(true);
    
    if (!$this->_mysql->hasError()) {
      $this->_postSave();
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
  public function remove()
  {
    if ($this->_isType()) {
      throw new TopicInUseException($this, __METHOD__ . ': Topic is typing one or more constructs!');
    }
    if ($this->_playsRole()) {
      throw new TopicInUseException($this, __METHOD__ . ': Topic plays one or more roles!');
    }
    if ($this->_isTheme()) {
      throw new TopicInUseException($this, __METHOD__ . ': Topic is a theme!');
    }
    if ($this->getReified() instanceof Reifiable) {
      throw new TopicInUseException($this, __METHOD__ . ': Topic is a reifier!');
    }
    $this->_preDelete();
    $query = 'DELETE FROM ' . $this->_config['table']['topic'] . 
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    if (!$this->_mysql->hasError()) {
      $this->_parent->_removeTopicFromCache($this->getId());
      $this->_id = 
      $this->_dbId = null;
    }
  }
  
  /**
   * Tells the topic map system that a property modification is finished and 
   * duplicate removal can take place.
   * 
   * Note: This may be a resource consuming process.
   * 
   * @param Reifiable The property (a {@link NameImpl} or an {@link OccurrenceImpl}).
   * @return void
   * @throws InvalidArgumentException If the property is not an instance of 
   * 				{@link NameImpl} or {@link OccurrenceImpl}.
   */
  public function finished(Reifiable $property)
  {
    $className = get_class($property);
    switch ($className) {
      case 'NameImpl':
        $tableName = 'topicname';
        break;
      case 'OccurrenceImpl':
        $tableName = 'occurrence';
        break;
      default:
        throw new InvalidArgumentException(
          __METHOD__ . ': The property is not an instance of Name or Occurrence.'
        );
    }
    // get the hash of the finished property
    $query = 'SELECT hash FROM ' . $this->_config['table'][$tableName] . 
      ' WHERE id = ' . $property->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetch();
    // detect duplicates
    $query = 'SELECT id FROM ' . $this->_config['table'][$tableName] . 
      ' WHERE hash = "' . $result['hash'] . '"' . 
      ' AND id <> ' . $property->_dbId . 
      ' AND topic_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $this->_parent->_setConstructParent($this);
      $duplicate = $this->_parent->_getConstructByVerifiedId(
        $className . '-' . $result['id']
      );
      // gain duplicate's item identities
      $property->_gainItemIdentifiers($duplicate);
      // gain duplicate's reifier
      $property->_gainReifier($duplicate);
      if ($property instanceof Name) {
        $property->_gainVariants($duplicate);
      }
      $duplicate->remove();
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
  protected function _getNameHash($value, Topic $type, array $scope)
  {
    if (empty($scope)) {
      return md5($value . $type->_dbId);
    }
    $ids = array();
    foreach ($scope as $theme) {
      if ($theme instanceof Topic) {
        $ids[$theme->_dbId] = $theme->_dbId;
      }
    }
    ksort($ids);
    $idsImploded = implode('', $ids);
    return md5($value . $type->_dbId . $idsImploded);
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
  protected function _getOccurrenceHash(Topic $type, $value, $datatype, array $scope)
  {
    if (empty($scope)) {
      return md5($value . $datatype . $type->_dbId);
    }
    $ids = array();
    foreach ($scope as $theme) {
      if ($theme instanceof Topic) {
        $ids[$theme->_dbId] = $theme->_dbId;
      }
    }
    ksort($ids);
    $idsImploded = implode('', $ids);
    return md5($value . $datatype . $type->_dbId . $idsImploded);
  }
  
  /**
   * Updates a name hash.
   * 
   * @param int The name id.
   * @param string The name hash.
   * @return void
   */
  protected function _updateNameHash($nameId, $hash)
  {
    $query = 'UPDATE ' . $this->_config['table']['topicname'] . 
      ' SET hash = "' . $hash . '"' .
      ' WHERE id = ' . $nameId;
    $this->_mysql->execute($query);
  }
  
  /**
   * Updates an occurrence hash.
   * 
   * @param int The occurrence id.
   * @param string The occurrence hash.
   * @return void
   */
  protected function _updateOccurrenceHash($occId, $hash)
  {
    $query = 'UPDATE ' . $this->_config['table']['occurrence'] . 
      ' SET hash = "' . $hash . '"' .
      ' WHERE id = ' . $occId;
    $this->_mysql->execute($query);
  }
  
  /**
   * Checks if this topic has a certain name.
   * 
   * @param string The hash code.
   * @return false|int The name id or <var>false</var> otherwise.
   */
  private function _hasName($hash)
  {
    $query = 'SELECT id FROM ' . $this->_config['table']['topicname'] . 
      ' WHERE topic_id = ' . $this->_dbId . 
      ' AND hash = "' . $hash . '"';
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    if ($numRows > 0) {
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
  private function _hasOccurrence($hash)
  {
    $query = 'SELECT id FROM ' . $this->_config['table']['occurrence'] . 
      ' WHERE topic_id = ' . $this->_dbId . 
      ' AND hash = "' . $hash . '"';
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    if ($numRows > 0) {
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
  private function _getDefaultNameType()
  {
    if (!is_null($this->_defaultNameType)) {
      return $this->_defaultNameType; 
    }
    $sid = VocabularyUtils::TMDM_PSI_DEFAULT_NAME_TYPE;
    $query = $query = 'SELECT t1.id FROM ' . $this->_config['table']['topic'] . ' AS t1' .
      ' INNER JOIN ' . $this->_config['table']['subjectidentifier'] . ' t2' .
      ' ON t1.id = t2.topic_id' .
      ' WHERE t2.locator = "' . $sid . '"' .
      ' AND t1.topicmap_id = ' . $this->_parent->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $numRows = $mysqlResult->getNumRows();
    if ($numRows > 0) {
      $result = $mysqlResult->fetch();
      $nameType = $this->_parent->_getConstructByVerifiedId(__CLASS__ . '-' . $result['id']);
      return $this->_defaultNameType = $nameType;
    }
    return $this->_defaultNameType = $this->_parent->createTopicBySubjectIdentifier($sid);
  }
  
  /**
   * Checks if topic plays a role.
   * 
   * @return boolean
   */
  private function _playsRole()
  {
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['assocrole'] . 
      ' WHERE player_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    return $result[0] == 0 ? false : true;
  }
  
  /**
   * Checks if topic is used as type.
   * 
   * @return boolean
   */
  private function _isType()
  {
    $tableNames = array(
      'instanceof',
      'association',
      'assocrole',
      'occurrence',
      'topicname'
    );
    foreach ($tableNames as $tableName) {
      $query = 'SELECT COUNT(*) FROM ' . $this->_config['table'][$tableName] . 
        ' WHERE type_id = ' . $this->_dbId;
      $mysqlResult = $this->_mysql->execute($query);
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
  private function _isTheme()
  {
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['theme'] . 
      ' WHERE topic_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
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
  private function _getAllOccurrencesByType(Topic $type)
  {
    $occurrences = array();
    $query = 'SELECT t1.id AS occ_id, t2.id AS topic_id FROM ' . $this->_config['table']['occurrence'] . ' t1' . 
      ' INNER JOIN ' . $this->_config['table']['topic'] . ' t2' . 
      ' ON t2.id = t1.topic_id' . 
      ' WHERE t2.topicmap_id = ' . $this->_parent->_dbId . 
      ' AND t1.type_id = ' . $type->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $parent = $this->_parent->_getConstructByVerifiedId(__CLASS__ . '-' . $result['topic_id']);
      $this->_parent->_setConstructParent($parent);
      $occurrence = $this->_parent->_getConstructByVerifiedId('OccurrenceImpl-' . $result['occ_id']);
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
  private function _getAllNamesByType(Topic $type)
  {
    $names = array();
    $query = 'SELECT t1.id AS name_id, t2.id AS topic_id FROM ' . $this->_config['table']['topicname'] . ' t1' . 
      ' INNER JOIN ' . $this->_config['table']['topic'] . ' t2' . 
      ' ON t2.id = t1.topic_id' . 
      ' WHERE t2.topicmap_id = ' . $this->_parent->_dbId . 
      ' AND t1.type_id = ' . $type->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $parent = $this->_parent->_getConstructByVerifiedId(__CLASS__ . '-' . $result['topic_id']);
      $this->_parent->_setConstructParent($parent);
      $name = $this->_parent->_getConstructByVerifiedId('NameImpl-' . $result['name_id']);
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
  private function _getRolesPlayedUntyped()
  {
    $roles = array();
    $query = 'SELECT t1.id AS role_id, t1.association_id, t1.type_id, t2.hash' .
    	' FROM ' . $this->_config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->_config['table']['association'] . ' t2' .
      ' ON t2.id = t1.association_id' .
      ' WHERE t1.player_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $assoc = $this->_parent->_getConstructByVerifiedId(
      	'AssociationImpl-' . $result['association_id']
      );
      $this->_parent->_setConstructParent($assoc);
      $role = $this->_parent->_getConstructByVerifiedId('RoleImpl-' . $result['role_id']);
      $roles[$result['hash'] . $result['type_id'] . $this->_dbId] = $role;
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
  private function _getRolesPlayedByType(Topic $type)
  {
    $roles = array();
    $query = 'SELECT t1.id AS role_id, t1.association_id, t1.type_id, t2.hash' .
    	' FROM ' . $this->_config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->_config['table']['association'] . ' t2' . 
      ' ON t2.id = t1.association_id' . 
      ' WHERE t1.type_id = ' . $type->_dbId . 
      ' AND t1.player_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $assoc = $this->_parent->_getConstructByVerifiedId(
      	'AssociationImpl-' . $result['association_id']
      );
      $this->_parent->_setConstructParent($assoc);
      $role = $this->_parent->_getConstructByVerifiedId('RoleImpl-' . $result['role_id']);
      $roles[$result['hash'] . $result['type_id'] . $this->_dbId] = $role;
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
  private function _getRolesPlayedByTypeAssocType(Topic $type, Topic $assocType)
  {
    $roles = array();
    $query = 'SELECT t1.id AS role_id, t1.association_id, t1.type_id, t2.hash' . 
    	' FROM ' . $this->_config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->_config['table']['association'] . ' t2' .
      ' ON t2.id = t1.association_id' .
      ' WHERE t2.type_id = ' . $assocType->_dbId . 
      ' AND t1.type_id = ' . $type->_dbId . 
      ' AND t1.player_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $assoc = $this->_parent->_getConstructByVerifiedId(
      	'AssociationImpl-' . $result['association_id']
      );
      $this->_parent->_setConstructParent($assoc);
      $role = $this->_parent->_getConstructByVerifiedId('RoleImpl-' . $result['role_id']);
      $roles[$result['hash'] . $result['type_id'] . $this->_dbId] = $role;
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
  private function _getRolesPlayedByAssocType(Topic $assocType)
  {
    $roles = array();
    $query = 'SELECT t1.id AS role_id, t1.association_id, t1.type_id, t2.hash' . 
    	' FROM ' . $this->_config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->_config['table']['association'] . ' t2' .
      ' ON t2.id = t1.association_id  ' .
      ' WHERE t2.type_id = ' . $assocType->_dbId . 
      ' AND t1.player_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $assoc = $this->_parent->_getConstructByVerifiedId(
      	'AssociationImpl-' . $result['association_id']
      );
      $this->_parent->_setConstructParent($assoc);
      $role = $this->_parent->_getConstructByVerifiedId('RoleImpl-' . $result['role_id']);
      $roles[$result['hash'] . $result['type_id'] . $this->_dbId] = $role;
    }
    return array_values($roles);
  }
}
?>