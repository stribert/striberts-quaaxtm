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
 * Represents an association role item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-assoc-role}.
 * 
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns the 
 * {@link AssociationImpl} to which this role belongs.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class RoleImpl extends ConstructImpl implements Role {
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param AssociationImpl The parent association.
   * @param TopicMapImpl The containing topic map.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, Association $parent, 
    TopicMap $topicMap) {
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
  }
  
  /**
   * Returns the topic playing this role.
   *
   * @return TopicImpl
   */
  public function getPlayer() {
    $query = 'SELECT player_id FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
      $result['player_id']);
  }

  /**
   * Sets the role player.
   * Any previous role player will be overridden by <var>player</var>.
   *
   * @param TopicImpl The topic which should play this role.
   * @return void
   */
  public function setPlayer(Topic $player) {
    if (!$this->getPlayer()->equals($player)) {
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['assocrole'] . 
        ' SET player_id = ' . $player->dbId . 
        ' WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->topicMap->getAssocHash($this->parent->getType(), 
        $this->parent->getScope(), $this->parent->getRoles());
      $this->topicMap->updateAssocHash($this->parent->dbId, $hash);
      $this->mysql->finishTransaction();
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
   * Sets the reifier of this role.
   * The specified <var>reifier</var> MUST NOT reify another information item.
   *
   * @param TopicImpl|null The topic that should reify this construct or null
   *        if an existing reifier should be removed.
   * @return void
   * @throws {@link ModelConstraintException} If the specified <var>reifier</var> 
   *        reifies another construct.
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
    $query = 'SELECT type_id FROM ' . $this->config['table']['assocrole'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
      $result['type_id']);
  }

  /**
   * Sets the type of this role.
   * Any previous type is overridden.
   * 
   * @param TopicImpl The topic that should define the nature of this role.
   * @return void
   */
  public function setType(Topic $type) {
    if (!$this->getType()->equals($type)) {
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['assocrole'] . 
        ' SET type_id = ' . $type->dbId . 
        ' WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->topicMap->getAssocHash($this->parent->getType(), 
        $this->parent->getScope(), $this->parent->getRoles());
      $this->topicMap->updateAssocHash($this->parent->dbId, $hash);
      $this->mysql->finishTransaction();
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
    
    $this->id = null;
    $this->dbId = null;
  }
}
?>