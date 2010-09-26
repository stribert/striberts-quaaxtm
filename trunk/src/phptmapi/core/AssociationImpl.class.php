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
 * Represents an association item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-association}.
 * 
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns the 
 * {@link TopicMapImpl} to which this Association belongs.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class AssociationImpl extends ScopedImpl implements Association {
  
  private $propertyHolder;
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicMapImpl The containing topic map.
   * @param PropertyUtils The property holder.
   */
  public function __construct($dbId, Mysql $mysql, array $config, TopicMap $parent, 
    PropertyUtils $propertyHolder=null) {
    
      parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $parent);
      
      $this->propertyHolder = !is_null($propertyHolder) ? $propertyHolder : new PropertyUtils();
  }
  
  /**
   * Destructor. If enabled duplicate removal in database takes place.
   * 
   * @return void
   */
  public function __destruct() {
    $featureIsSet = $this->topicMap->getTopicMapSystem()->getFeature(
      VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL
    );
    if ($featureIsSet && !is_null($this->dbId) && !is_null($this->parent->dbId)) {
      $this->parent->finished($this);
    }
  }

  /**
   * Returns the roles participating in this association.
   * The return value must never be <var>null</var>.
   * 
   * @param TopicImpl The type of the {@link RoleImpl} instances to be returned. Default <var>null</var>.
   * @return array An array containing a set of {@link Role}s.
   */
  public function getRoles(Topic $type=null) {
    $roles = array();
    $query = 'SELECT id, type_id, player_id FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE association_id = ' . $this->dbId;
    if (!is_null($type)) {
      $query .= ' AND type_id = ' . $type->dbId;
    }
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId((int)$result['type_id'])
        ->setPlayerId((int)$result['player_id']);
      $this->parent->setConstructPropertyHolder($propertyHolder);
      
      $this->parent->setConstructParent($this);
      
      $role = $this->parent->getConstructById('RoleImpl-' . $result['id']);
      
      $roles[$result['type_id'] . $result['player_id']] = $role;
    }
    return array_values($roles);
  }

  /**
   * Creates a new {@link RoleImpl} representing a role in this association. 
   * 
   * @param TopicImpl The role type.
   * @param TopicImpl The role player.
   * @return RoleImpl A newly created association role.
   * @throws {@link ModelConstraintException} If either the <var>type</var> or the 
   *        <var>player</var> does not belong to the parent topic map.
   */
  public function createRole(Topic $type, Topic $player) {
    if (!$this->topicMap->equals($type->topicMap) || 
      !$this->topicMap->equals($player->topicMap)) {
      throw new ModelConstraintException($this, __METHOD__ . 
        parent::SAME_TM_CONSTRAINT_ERR_MSG);
    }
    // duplicate suppression
    $query = 'SELECT id FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE association_id = ' . $this->dbId . 
      ' AND type_id = ' . $type->dbId . 
      ' AND player_id = ' . $player->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows == 0) {
      $this->mysql->startTransaction();
      $query = 'INSERT INTO ' . $this->config['table']['assocrole'] . 
        ' (id, association_id, type_id, player_id) VALUES' .
        ' (NULL, ' . $this->dbId . ', ' . $type->dbId . ', ' . $player->dbId . ')';
      $mysqlResult = $this->mysql->execute($query);
      $lastRoleId = $mysqlResult->getLastId();
      
      $query = 'INSERT INTO ' . $this->config['table']['topicmapconstruct'] . 
        ' (assocrole_id, topicmap_id, parent_id) VALUES' .
        ' (' . $lastRoleId . ', ' . $this->parent->dbId . ', ' . $this->dbId . ')';
      $this->mysql->execute($query);
      
      $hash = $this->parent->getAssocHash($this->getType(), $this->getScope(), 
        $this->getRoles());
      $this->parent->updateAssocHash($this->dbId, $hash);
      $this->mysql->finishTransaction();
      
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId($type->dbId)
        ->setPlayerId($player->dbId);
      $this->parent->setConstructPropertyHolder($propertyHolder);
      $this->parent->setConstructParent($this);
      
      $role = $this->parent->getConstructById('RoleImpl-' . $lastRoleId);
      if (!$this->mysql->hasError()) {
        $role->postInsert();
        $this->postSave();
      }
      return $role;
    } else {
      $result = $mysqlResult->fetch();
      $this->parent->setConstructParent($this);
      return $this->parent->getConstructById('RoleImpl-' . $result['id']);
    }
  }

  /**
   * Returns the role types participating in this association.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link TopicImpl}s representing the 
   *        role types.
   */
  public function getRoleTypes() {
    $types = array();
    $query = 'SELECT type_id FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE association_id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $type = $this->parent->getConstructById('TopicImpl-' . $result['type_id']);
      $types[$type->getId()] = $type;
    }
    return array_values($types);
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
   * @see ConstructImpl::_setReifier()
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
    if (!is_null($this->propertyHolder->getTypeId())) {
      $typeId = $this->propertyHolder->getTypeId();
    } else {
      $query = 'SELECT type_id FROM ' . $this->config['table']['association'] . 
        ' WHERE id = ' . $this->dbId;
      $mysqlResult = $this->mysql->execute($query);
      $result = $mysqlResult->fetch();
      $typeId = $result['type_id'];
      $this->propertyHolder->setTypeId($typeId);
    }
    return $this->parent->getConstructById('TopicImpl-' . $typeId);
  }

  /**
   * Sets the type of this construct.
   * Any previous type is overridden.
   * 
   * @param TopicImpl The topic that should define the nature of this construct.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>type</var> does not belong 
   *        to the parent topic map.
   */
  public function setType(Topic $type) {
    if (!$this->getType()->equals($type)) {
      if (!$this->topicMap->equals($type->topicMap)) {
        throw new ModelConstraintException($this, __METHOD__ . 
          parent::SAME_TM_CONSTRAINT_ERR_MSG);
      }
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['association'] . 
        ' SET type_id = ' . $type->dbId . 
        ' WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->parent->getAssocHash($type, $this->getScope(), $this->getRoles());
      $this->parent->updateAssocHash($this->dbId, $hash);
      $this->mysql->finishTransaction();
      
      if (!$this->mysql->hasError()) {
        $this->propertyHolder->setTypeId($type->dbId);
        $this->postSave();
      }
    } else {
      return;
    }
  }
  
  /**
   * Removes this association.
   * 
   * @override
   * @return void
   */
  public function remove() {
    $this->preDelete();
    $scopeObj = $this->getScopeObject();
    $query = 'DELETE FROM ' . $this->config['table']['association'] . 
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      if (!$scopeObj->isUnconstrained()) {
        $this->unsetScope($scopeObj);// triggers clean up routine
      }
      $this->parent->removeAssociation($this);
      $this->id = 
      $this->dbId = null;
    }
  }
}
?>