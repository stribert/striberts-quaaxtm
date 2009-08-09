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
 * Represents an association role item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-assoc-role}.
 * 
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns the 
 * {@link AssociationImpl} to which this role belongs.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class RoleImpl extends ConstructImpl implements Role {
  
  private $propertyHolder;
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param AssociationImpl The parent association.
   * @param TopicMapImpl The containing topic map.
   * @param PropertyUtils The property holder.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, Association $parent, 
    TopicMap $topicMap, PropertyUtils $propertyHolder=null) {
    
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
    
    $this->propertyHolder = !is_null($propertyHolder) ? $propertyHolder : new PropertyUtils();
  }
  
  /**
   * Returns the topic playing this role.
   *
   * @return TopicImpl
   */
  public function getPlayer() {
    if (!is_null($this->propertyHolder->getPlayerId())) {
      $playerId = $this->propertyHolder->getPlayerId();
    } else {
      $query = 'SELECT player_id FROM ' . $this->config['table']['assocrole'] . 
        ' WHERE id = ' . $this->dbId;
      $mysqlResult = $this->mysql->execute($query);
      $result = $mysqlResult->fetch();
      $playerId = $result['player_id'];
      $this->propertyHolder->setPlayerId($playerId);
    }
    return $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
      $playerId);
  }

  /**
   * Sets the role player.
   * Any previous role player will be overridden by <var>player</var>.
   *
   * @param TopicImpl The topic which should play this role.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>player</var> does not belong 
   *        to the parent topic map.
   */
  public function setPlayer(Topic $player) {
    if (!$this->getPlayer()->equals($player)) {
      if (!$this->topicMap->equals($player->topicMap)) {
        throw new ModelConstraintException($this, __METHOD__ . 
          parent::SAME_TM_CONSTRAINT_ERR_MSG);
      }
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['assocrole'] . 
        ' SET player_id = ' . $player->dbId . 
        ' WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->topicMap->getAssocHash($this->parent->getType(), 
        $this->parent->getScope(), $this->parent->getRoles());
      $this->topicMap->updateAssocHash($this->parent->dbId, $hash);
      $this->mysql->finishTransaction();
      
      $this->propertyHolder->setPlayerId($player->dbId);
    }
  }
  
  /**
   * Returns the reifier of this construct.
   * 
   * @return TopicImpl The topic that reifies this role or
   *        <var>null</var> if this role is not reified.
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
   * Returns the type of this role.
   *
   * @return TopicImpl
   */
  public function getType() {
    if (!is_null($this->propertyHolder->getTypeId())) {
      $typeId = $this->propertyHolder->getTypeId();
    } else {
      $query = 'SELECT type_id FROM ' . $this->config['table']['assocrole'] . 
        ' WHERE id = ' . $this->dbId;
      $mysqlResult = $this->mysql->execute($query);
      $result = $mysqlResult->fetch();
      $typeId = $result['type_id'];
      $this->propertyHolder->setTypeId($typeId);
    }
    return $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
      $typeId);
  }

  /**
   * Sets the type of this role.
   * Any previous type is overridden.
   * 
   * @param TopicImpl The topic that should define the nature of this role.
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
      $query = 'UPDATE ' . $this->config['table']['assocrole'] . 
        ' SET type_id = ' . $type->dbId . 
        ' WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->topicMap->getAssocHash($this->parent->getType(), 
        $this->parent->getScope(), $this->parent->getRoles());
      $this->topicMap->updateAssocHash($this->parent->dbId, $hash);
      $this->mysql->finishTransaction();
      
      $this->propertyHolder->setTypeId($type->dbId);
    } else {
      return;
    }
  }
  
  /**
   * Deletes this role.
   * 
   * @override
   * @return void
   */
  public function remove() {
    $this->mysql->startTransaction();
    $query = 'DELETE FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
     
    $hash = $this->topicMap->getAssocHash($this->parent->getType(), 
      $this->parent->getScope(), $this->parent->getRoles());
    $this->topicMap->updateAssocHash($this->parent->dbId, $hash);
    $this->mysql->finishTransaction();
    
    $this->id = 
    $this->dbId = 
    $this->propertyHolder = null;
  }
}
?>