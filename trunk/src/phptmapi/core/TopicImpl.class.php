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
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class TopicImpl extends ConstructImpl implements Topic {

  const NAME_CLASS_NAME = 'NameImpl',
        OCC_CLASS_NAME = 'OccurrenceImpl',
        
        IDENTITY_NULL_ERR_MSG = ': Identity locator must not be null!';
  
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
   * @return array An array containing URIs representing the subject identifiers.
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
    if (!is_null($sid)) {
      $sids = $this->getSubjectIdentifiers();
      if (!in_array($sid, $sids, true)) {
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
          } else {// merge
            $existingTopic = $this->factory($mysqlResult);
            $this->mergeIn($existingTopic);
          }
        } else {// merge
          $result = $mysqlResult->fetch();
          $existingTopic = $this->parent->getConstructById(__CLASS__ . '-' . $result['id']);
          $this->mergeIn($existingTopic);
        }
      } else {
        return;
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        self::IDENTITY_NULL_ERR_MSG);
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
    if (!is_null($sid)) {
      $query = 'DELETE FROM ' . $this->config['table']['subjectidentifier'] . 
        ' WHERE topic_id = ' . $this->dbId . 
        ' AND locator = "' . $sid . '"';
      $this->mysql->execute($query);
    }
  }

  /**
   * Returns the subject locators assigned to this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing URIs representing the subject locators.
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
    if (!is_null($slo)) {
      $slos = $this->getSubjectLocators();
      if (!in_array($slo, $slos, true)) {
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
        } else {// merge
          $result = $mysqlResult->fetch();
          $existingTopic = $this->parent->getConstructById(__CLASS__ . '-' . $result['id']);
          $this->mergeIn($existingTopic);
        }
      } else {
        return;
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        self::IDENTITY_NULL_ERR_MSG);
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
    if (!is_null($slo)) {
      $query = 'DELETE FROM ' . $this->config['table']['subjectlocator'] . 
        ' WHERE topic_id = ' . $this->dbId . 
        ' AND locator = "' . $slo . '"';
      $this->mysql->execute($query);
    }
  }

  /**
   * Returns the names of this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link NameImpl}s belonging to this topic.
   */
  public function getNames() {
    $names = array();
    $query = 'SELECT id FROM ' . $this->config['table']['topicname'] . 
      ' WHERE topic_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $this->parent->setConstructParent($this);
      $name = $this->parent->getConstructById(self::NAME_CLASS_NAME . '-' . $result['id']);
      $names[] = $name;
    }
    return $names;
  }

  /**
   * Returns the {@link NameImpl}s of this topic where the name type is <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>. 
   * 
   * @param TopicImpl The type of the {@link NameImpl}s to be returned.
   * @return array An array containing {@link NameImpl}s with the specified <var>type</var>.
   */
  public function getNamesByType(Topic $type) {
    $names = array();
    $query = 'SELECT id FROM ' . $this->config['table']['topicname'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' AND type_id = ' . $type->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $this->parent->setConstructParent($this);
      $name = $this->parent->getConstructById(self::NAME_CLASS_NAME . '-' . $result['id']);
      $names[] = $name;
    }
    return $names;
  }

  /**
   * Creates a {@link NameImpl} for this topic with the specified <var>value</var>, 
   * and <var>scope</var>.
   * The created {@link NameImpl} will have the default name type
   * (a {@link TopicImpl} with the subject identifier 
   * http://psi.topicmaps.org/iso13250/model/topic-name).
   * 
   * @param string The string value of the name; must not be <var>null</var>.
   * @param array An array containing {@link TopicImpl}s - each representing a theme. 
   *        If the array's length is 0 (default), the name will be in the 
   *        unconstrained scope.
   * @return NameImpl The newly created {@link NameImpl}.
   * @throws {@link ModelConstraintException} If the <var>value</var> is <var>null</var>.
   */
  public function createName($value, array $scope=array()) {
    if (!is_null($value)) {
      $type = $this->getDefaultNameType();
      return $this->createTypedName($type, $value, $scope);
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        ConstructImpl::VALUE_NULL_ERR_MSG);
    }
  }

  /**
   * Creates a {@link NameImpl} for this topic with the specified <var>type</var>,
   * <var>value</var>, and <var>scope</var>. 
   *
   * @param TopicImpl The name type.
   * @param string The string value of the name; must not be <var>null</var>.
   * @param array An array containing {@link TopicImpl}s - each representing a theme.
   *        If the array's length is 0 (default), the name will be in the 
   *        unconstrained scope.
   * @return NameImpl The newly created {@link NameImpl}.
   * @throws {@link ModelConstraintException} If the <var>value</var> is <var>null</var>.
   */
  public function createTypedName(Topic $type, $value, array $scope=array()) {
    if (!is_null($value)) {
      $value = CharacteristicUtils::canonicalize($value);
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
        
        $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope);
        $query = 'INSERT INTO ' . $this->config['table']['topicname_scope'] . 
          ' (scope_id, topicname_id) VALUES' .
          ' (' . $scopeObj->dbId . ', ' . $lastNameId . ')';
        $this->mysql->execute($query);
        
        $this->mysql->finishTransaction(true);
        $this->parent->setConstructParent($this);
        return $this->parent->getConstructById(self::NAME_CLASS_NAME . '-' . $lastNameId);
      } else {
        $this->parent->setConstructParent($this);
        return $this->parent->getConstructById(self::NAME_CLASS_NAME . '-' . $propertyId);
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        ConstructImpl::VALUE_NULL_ERR_MSG);
    }
  }

  /**
   * Returns the {@link OccurrenceImpl}s of this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing {@link OccurrenceImpl}s belonging to this topic.
   */
  public function getOccurrences() {
    $occurrences = array();
    $query = 'SELECT id FROM ' . $this->config['table']['occurrence'] . 
      ' WHERE topic_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $this->parent->setConstructParent($this);
      $occurrence = $this->parent->getConstructById(self::OCC_CLASS_NAME . '-' . 
        $result['id']);
      $occurrences[] = $occurrence;
    }
    return $occurrences;
  }

  /**
   * Returns the {@link OccurrenceImpl}s of this topic where the occurrence type 
   * is <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link OccurrenceImpl}s to be returned.
   * @return array An array containing {@link OccurrenceImpl}s with the 
   *        specified <var>type</var>.
   */
  public function getOccurrencesByType(Topic $type) {
    $occurrences = array();
    $query = 'SELECT id FROM ' . $this->config['table']['occurrence'] . 
      ' WHERE topic_id = ' . $this->dbId . 
      ' AND type_id = ' . $type->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $this->parent->setConstructParent($this);
      $occurrence = $this->parent->getConstructById(self::OCC_CLASS_NAME . '-' . 
        $result['id']);
      $occurrences[] = $occurrence;
    }
    return $occurrences;
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
   *        must not be <var>null</var>. I.e. http://www.w3.org/2001/XMLSchema#string 
   *        indicates a string value.
   * @param array An array containing {@link TopicImpl}s - each representing a theme; 
   *        must not be <var>null</var>. If the array's length is 0 (default), 
   *        the occurrence will be in the unconstrained scope.
   * @return OccurrenceImpl The newly created {@link OccurrenceImpl}.
   * @throws {@link ModelConstraintException} If either the the <var>value</var> or the
   *        <var>datatype</var> is <var>null</var>.
   */
  public function createOccurrence(Topic $type, $value, $datatype, array $scope=array()) {
    if (!is_null($value) && !is_null($datatype)) {
      $value = CharacteristicUtils::canonicalize($value);
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
        
        $scopeObj = new ScopeImpl($this->mysql, $this->config, $scope);
        $query = 'INSERT INTO ' . $this->config['table']['occurrence_scope'] . 
          ' (scope_id, occurrence_id) VALUES' .
          ' (' . $scopeObj->dbId . ', ' . $lastOccurrenceId . ')';
        $this->mysql->execute($query);
        
        $this->mysql->finishTransaction(true);
        $this->parent->setConstructParent($this);
        return $this->parent->getConstructById(self::OCC_CLASS_NAME . '-' . 
          $lastOccurrenceId);
      } else {
        $this->parent->setConstructParent($this);
        return $this->parent->getConstructById(self::OCC_CLASS_NAME . '-' . $propertyId);
      }
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        ConstructImpl::VALUE_DATATYPE_NULL_ERR_MSG);
    }
  }

  /**
   * Returns the roles played by this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing {@link RoleImpl}s played by this topic.
   */
  public function getRolesPlayed() {
    $roles = array();
    $query = 'SELECT id FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE player_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $query = 'SELECT association_id FROM ' . $this->config['table']['assocrole'] . 
        ' WHERE id = ' . $result['id'];
      $_mysqlResult = $this->mysql->execute($query);
      $_result = $_mysqlResult->fetch();
      $assoc = $this->parent->getConstructById(TopicMapImpl::ASSOC_CLASS_NAME . '-' . 
        $_result['association_id']);
      $this->parent->setConstructParent($assoc);
      $role = $this->parent->getConstructById(AssociationImpl::ROLE_CLASS_NAME . '-' . 
        $result['id']);
      $roles[] = $role;
    }
    return $roles;
  }

  /**
   * Returns the roles played by this topic where the role type is <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link RoleImpl}s to be returned.
   * @return array An array containing {@link RoleImpl}s with the specified <var>type</var>.
   */
  public function getRolesPlayedByType(Topic $type) {
    $roles = array();
    $query = 'SELECT id FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE type_id = ' . $type->dbId . 
      ' AND player_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $query = 'SELECT association_id FROM ' . $this->config['table']['assocrole'] . 
        ' WHERE id = ' . $result['id'];
      $_mysqlResult = $this->mysql->execute($query);
      $_result = $_mysqlResult->fetch();
      $assoc = $this->parent->getConstructById(TopicMapImpl::ASSOC_CLASS_NAME . '-' . 
        $_result['association_id']);
      $this->parent->setConstructParent($assoc);
      $role = $this->parent->getConstructById(AssociationImpl::ROLE_CLASS_NAME . '-' . 
        $result['id']);
      $roles[] = $role;
    }
    return $roles;
  }

  /**
   * Returns the {@link RoleImpl}s played by this topic where the role type is
   * <var>type</var> and the {@link AssociationImpl} type is <var>assocType</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param TopicImpl The type of the {@link RoleImpl}s to be returned.
   * @param TopicImpl The type of the {@link AssociationImpl} from which the
   *        returned roles must be part of.
   * @return array An array containing {@link RoleImpl}s with the specified <var>type</var>
   *        which are part of {@link AssociationImpl}s with the specified <var>assocType</var>.
   */
  public function getRolesPlayedByTypeAssocType(Topic $type, Topic $assocType) {
    $roles = array();
    $query = 'SELECT t1.id AS id FROM ' . $this->config['table']['assocrole'] . ' t1' .
      ' INNER JOIN ' . $this->config['table']['association'] . ' t2' .
      ' ON t2.id = t1.association_id  ' .
      ' WHERE t2.type_id = ' . $assocType->dbId . 
      ' AND t1.type_id = ' . $type->dbId . 
      ' AND t1.player_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      // parent association
      $query = 'SELECT association_id FROM ' . $this->config['table']['assocrole'] . 
        ' WHERE id = ' . $result['id'];
      $_mysqlResult = $this->mysql->execute($query);
      $_result = $_mysqlResult->fetch();
      $assoc = $this->parent->getConstructById(TopicMapImpl::ASSOC_CLASS_NAME . '-' . 
        $_result['association_id']);
      $this->parent->setConstructParent($assoc);
      $role = $this->parent->getConstructById(AssociationImpl::ROLE_CLASS_NAME . '-' . 
        $result['id']);
      $roles[] = $role;
    }
    return $roles;
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
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getTypes() {
    $types = array();
    $query = 'SELECT type_id FROM ' . $this->config['table']['instanceof'] . 
      ' WHERE topic_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $type = $this->parent->getConstructById(__CLASS__ . '-' . $result['type_id']);
      $types[] = $type;
    }
    return $types;
  }

  /**
   * Adds a type to this topic.
   * Implementations may or may not create an association for types added
   * by this method. In any case, every type which was added by this method 
   * must be returned by the {@link getTypes()} method.
   * 
   * @param TopicImpl The type of which this topic should become an instance of.
   * @return void
   */
  public function addType(Topic $type) {
    if (!$this->equals($type)) {
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
      }
    } else {
      return;
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
    if ($rows > 0) {
      return $this->factory($mysqlResult);
    } else {
      return null;
    }
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
    if ($this->equals($other)) return;
    if (!$this->parent->equals($other->parent)) {
      throw new InvalidArgumentException(__METHOD__ . 
        ': Both topics must belong to the same topic map!');
    }
    if (!is_null($this->getReified()) && !is_null($other->getReified())) {
      if (!$this->getReified()->equals($other->getReified())) {
        throw new ModelConstraintException($this, 
          __METHOD__ . ': Topics reify different Topic Maps constructs!');
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
    
    // scope properties (themes)
    $query = 'UPDATE ' . $this->config['table']['theme'] . 
      ' SET topic_id = ' . $this->dbId . ' WHERE topic_id = ' . $other->dbId;
    $this->mysql->execute($query);
    
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
      $name = $this->createTypedName($othersName->getType(), 
                                      $othersName->getValue(), 
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
  }
  
  /**
   * Gets a name hash.
   * 
   * @param string The name value.
   * @param TopicImpl The name type.
   * @param array The scope.
   * @return string
   */
  public function getNameHash($value, Topic $type, array $scope) {
    if (count($scope) == 0) {
      return md5($this->dbId . $value . $type->dbId);
    } else {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) $ids[$theme->dbId] = $theme->dbId;
      }
      ksort($ids);
      $idsImploded = implode('', $ids);
      return md5($this->dbId . $value . $type->dbId . $idsImploded);
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
  public function getOccurrenceHash(Topic $type, $value, $datatype, array $scope) {
    if (count($scope) == 0) {
      return md5($this->dbId . $value . $datatype . $type->dbId);
    } else {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) $ids[$theme->dbId] = $theme->dbId;
      }
      ksort($ids);
      $idsImploded = implode('', $ids);
      return md5($this->dbId . $value . $datatype . $type->dbId . $idsImploded);
    }
  }
  
  /**
   * Updates a name hash.
   * 
   * @param int The name id.
   * @param string The name hash.
   * @return void
   */
  public function updateNameHash($nameId, $hash) {
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
  public function updateOccurrenceHash($occId, $hash) {
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
  public function hasName($hash) {
    $return = false;
    $query = 'SELECT id FROM ' . $this->config['table']['topicname'] . 
      ' WHERE topic_id = ' . $this->dbId . 
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
   * Checks if this topic has a certain occurrence.
   * 
   * @param string The hash code.
   * @return false|int The occurrence id or <var>false</var> otherwise.
   */
  public function hasOccurrence($hash) {
    $return = false;
    $query = 'SELECT id FROM ' . $this->config['table']['occurrence'] . 
      ' WHERE topic_id = ' . $this->dbId . 
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
    $table = $className == self::NAME_CLASS_NAME ? 'topicname' : 'occurrence';
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
        $duplicate = $this->parent->getConstructById($className . '-' . 
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
   * Gets the default name type.
   * 
   * @return TopicImpl
   */
  private function getDefaultNameType() {
    if (is_null($this->defaultNameType)) {
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
        $nameType = $this->parent->getConstructById(__CLASS__ . '-' . $result['id']);
        return $this->defaultNameType = $nameType;
      } else {
        return $this->defaultNameType = $this->parent->createTopicBySubjectIdentifier($sid);
      }
    } else {
      return $this->defaultNameType;
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
    } else {
      if ($this->playsRole()) {
        throw new TopicInUseException($this, __METHOD__ . ': Topic plays one or more roles!');
      } else {
        if ($this->isTheme()) {
          throw new TopicInUseException($this, __METHOD__ . ': Topic is a theme!');
        } else {
          if ($this->getReified() instanceof Reifiable) {
            throw new TopicInUseException($this, __METHOD__ . ': Topic is a reifier!');
          } else {
            // all checks have been passed; delete this topic
            $query = 'DELETE FROM ' . $this->config['table']['topic'] . 
              ' WHERE id = ' . $this->dbId;
            $this->mysql->execute($query);
            if (!$this->mysql->hasError()) {
              $this->parent->removeTopic($this);
              $this->id = null;
              $this->dbId = null;
            }
          }
        }
      }
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
      $parent = $this->parent->getConstructById(__CLASS__ . '-' . $result['topic_id']);
      $this->parent->setConstructParent($parent);
      $occurrence = $this->parent->getConstructById(self::OCC_CLASS_NAME . '-' . 
        $result['occ_id']);
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
      $parent = $this->parent->getConstructById(__CLASS__ . '-' . $result['topic_id']);
      $this->parent->setConstructParent($parent);
      $name = $this->parent->getConstructById(self::NAME_CLASS_NAME . '-' . 
        $result['name_id']);
      $names[] = $name;
    }
    return $names;
  }
}
?>