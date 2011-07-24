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
final class RoleImpl extends ConstructImpl implements Role
{  
  private $_propertyHolder;
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param AssociationImpl The parent association.
   * @param TopicMapImpl The containing topic map.
   * @param array The property holder.
   * @return void
   */
  public function __construct(
    $dbId, 
    Mysql $mysql, 
    array $config, 
    Association $parent, 
    TopicMap $topicMap, 
    array $propertyHolder=array()
    )
  {  
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
    $this->_propertyHolder = $propertyHolder;
  }
  
  /**
   * Returns the topic playing this role.
   *
   * @return TopicImpl
   */
  public function getPlayer()
  {
    if (
      isset($this->_propertyHolder['player_id']) && 
      !empty($this->_propertyHolder['player_id'])
    ) {
      return $this->_topicMap->_getConstructByVerifiedId(
      	'TopicImpl-' . $this->_propertyHolder['player_id']
      );
    } else {
      $query = 'SELECT player_id FROM ' . $this->_config['table']['assocrole'] . 
        ' WHERE id = ' . $this->_dbId;
      $mysqlResult = $this->_mysql->execute($query);
      $result = $mysqlResult->fetch();
      $this->_propertyHolder['player_id'] = $result['player_id'];
      return $this->_topicMap->_getConstructByVerifiedId('TopicImpl-' . $result['player_id']);
    }
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
  public function setPlayer(Topic $player)
  {
    if (!$this->_topicMap->equals($player->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
    $this->_mysql->startTransaction();
    $query = 'UPDATE ' . $this->_config['table']['assocrole'] . 
      ' SET player_id = ' . $player->_dbId . 
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    $hash = $this->_topicMap->_getAssocHash($this->_parent->getType(), 
      $this->_parent->getScope(), $this->_parent->getRoles());
    $this->_topicMap->_updateAssocHash($this->_parent->_dbId, $hash);
    $this->_mysql->finishTransaction();
    
    if (!$this->_mysql->hasError()) {
      $this->_propertyHolder['player_id'] = $player->_dbId;
      $this->_postSave();
    }
  }
  
  /**
   * Returns the reifier of this construct.
   * 
   * @return TopicImpl The topic that reifies this role or
   *        <var>null</var> if this role is not reified.
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
   * Returns the type of this role.
   *
   * @return TopicImpl
   */
  public function getType()
  {
    if (
      isset($this->_propertyHolder['type_id']) && 
      !empty($this->_propertyHolder['type_id'])
    ) {
      return $this->_topicMap->_getConstructByVerifiedId(
      	'TopicImpl-' . $this->_propertyHolder['type_id']
      );
    } else {
      $query = 'SELECT type_id FROM ' . $this->_config['table']['assocrole'] . 
        ' WHERE id = ' . $this->_dbId;
      $mysqlResult = $this->_mysql->execute($query);
      $result = $mysqlResult->fetch();
      $this->_propertyHolder['type_id'] = $result['type_id'];
      return $this->_topicMap->_getConstructByVerifiedId('TopicImpl-' . $result['type_id']);
    }
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
  public function setType(Topic $type)
  {
    if (!$this->_topicMap->equals($type->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
    $this->_mysql->startTransaction();
    $query = 'UPDATE ' . $this->_config['table']['assocrole'] . 
      ' SET type_id = ' . $type->_dbId . 
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    $hash = $this->_topicMap->_getAssocHash($this->_parent->getType(), 
      $this->_parent->getScope(), $this->_parent->getRoles());
    $this->_topicMap->_updateAssocHash($this->_parent->_dbId, $hash);
    $this->_mysql->finishTransaction();
    
    if (!$this->_mysql->hasError()) {
      $this->_propertyHolder['type_id'] = $type->_dbId;
      $this->_postSave();
    }
  }
  
  /**
   * Deletes this role.
   * 
   * @override
   * @return void
   */
  public function remove()
  {
    $this->_preDelete();
    $this->_mysql->startTransaction();
    $query = 'DELETE FROM ' . $this->_config['table']['assocrole'] . 
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
     
    $hash = $this->_topicMap->_getAssocHash($this->_parent->getType(), 
      $this->_parent->getScope(), $this->_parent->getRoles());
    $this->_topicMap->_updateAssocHash($this->_parent->_dbId, $hash);
    $this->_mysql->finishTransaction();
    
    $this->_id = 
    $this->_dbId = null;
    $this->_propertyHolder = array();
  }
}
?>