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
final class AssociationImpl extends ScopedImpl implements Association
{  
  private $_propertyHolder;
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicMapImpl The containing topic map.
   * @param array The property holder.
   */
  public function __construct(
    $dbId, 
    Mysql $mysql, 
    array $config, 
    TopicMap $parent, 
    array $propertyHolder=array()
    )
  {  
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $parent); 
    $this->_propertyHolder = $propertyHolder;
  }
  
  /**
   * Destructor. If enabled duplicate removal in database takes place.
   * 
   * @return void
   */
  public function __destruct()
  {
    $featureIsSet = $this->_topicMap->getTopicMapSystem()->getFeature(
      VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL
    );
    if (
      $featureIsSet && 
      !is_null($this->_dbId) && 
      !is_null($this->_parent->_dbId) && 
      $this->_mysql->isConnected()
    ) {
      $this->_parent->finished($this);
    }
  }

  /**
   * Returns the roles participating in this association.
   * The return value must never be <var>null</var>.
   * 
   * @param TopicImpl The type of the {@link RoleImpl} instances to be returned. Default <var>null</var>.
   * @return array An array containing a set of {@link Role}s.
   */
  public function getRoles(Topic $type=null)
  {
    $roles = array();
    $query = 'SELECT id, type_id, player_id FROM ' . $this->_config['table']['assocrole'] . 
      ' WHERE association_id = ' . $this->_dbId;
    if (!is_null($type)) {
      $query .= ' AND type_id = ' . $type->_dbId;
    }
    $resultCachePerm = $this->_getResultCachePermission();
    $results = $this->_mysql->fetch(
      $query, 
      $resultCachePerm, 
      $this->_config['resultcache']['expiration']
    );
    if (is_array($results)) {
      foreach ($results as $result) {
        $propertyHolder['type_id'] = $result['type_id'];
        $propertyHolder['player_id'] = $result['player_id'];
        $this->_parent->_setConstructPropertyHolder($propertyHolder);
        
        $this->_parent->_setConstructParent($this);
        
        $role = $this->_parent->_getConstructByVerifiedId('RoleImpl-' . $result['id']);
        
        $roles[$result['type_id'] . $result['player_id']] = $role;
      }
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
  public function createRole(Topic $type, Topic $player)
  {
    if (
      !$this->_topicMap->equals($type->_topicMap) || 
      !$this->_topicMap->equals($player->_topicMap)
    ) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
    // duplicate suppression
    $query = 'SELECT id FROM ' . $this->_config['table']['assocrole'] . 
      ' WHERE association_id = ' . $this->_dbId . 
      ' AND type_id = ' . $type->_dbId . 
      ' AND player_id = ' . $player->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows == 0) {
      $this->_mysql->startTransaction();
      $query = 'INSERT INTO ' . $this->_config['table']['assocrole'] . 
        ' (id, association_id, type_id, player_id) VALUES' .
        ' (NULL, ' . $this->_dbId . ', ' . $type->_dbId . ', ' . $player->_dbId . ')';
      $mysqlResult = $this->_mysql->execute($query);
      $lastRoleId = $mysqlResult->getLastId();
      
      $query = 'INSERT INTO ' . $this->_config['table']['topicmapconstruct'] . 
        ' (assocrole_id, topicmap_id, parent_id) VALUES' .
        ' (' . $lastRoleId . ', ' . $this->_parent->_dbId . ', ' . $this->_dbId . ')';
      $this->_mysql->execute($query);
      
      $hash = $this->_parent->_getAssocHash($this->getType(), $this->getScope(), 
        $this->getRoles());
      $this->_parent->_updateAssocHash($this->_dbId, $hash);
      $this->_mysql->finishTransaction();
      
      $propertyHolder['type_id'] = $type->_dbId;
      $propertyHolder['player_id'] = $player->_dbId;
      $this->_parent->_setConstructPropertyHolder($propertyHolder);
      
      $this->_parent->_setConstructParent($this);
      
      $role = $this->_parent->_getConstructByVerifiedId('RoleImpl-' . $lastRoleId);
      
      if (!$this->_mysql->hasError()) {
        $role->_postInsert();
        $this->_postSave();
      }
      return $role;
    } else {
      $result = $mysqlResult->fetch();
      $this->_parent->_setConstructParent($this);
      return $this->_parent->_getConstructByVerifiedId('RoleImpl-' . $result['id']);
    }
  }

  /**
   * Returns the role types participating in this association.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link TopicImpl}s representing the 
   *        role types.
   */
  public function getRoleTypes()
  {
    $types = array();
    $query = 'SELECT type_id FROM ' . $this->_config['table']['assocrole'] . 
      ' WHERE association_id = ' . $this->_dbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $type = $this->_parent->_getConstructByVerifiedId('TopicImpl-' . $result['type_id']);
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
  public function getReifier()
  {
    return $this->_getReifier();
  }

  /**
   * @see ConstructImpl::_setReifier()
   */
  public function setReifier(Topic $reifier=null)
  {
    $this->_setReifier($reifier);
  }
  
  /**
   * Returns the type of this construct.
   *
   * @return TopicImpl
   */
  public function getType()
  {
    if (
      isset($this->_propertyHolder['type_id']) && 
      !empty($this->_propertyHolder['type_id'])
    ) {
      return $this->_parent->_getConstructByVerifiedId(
      	'TopicImpl-' . $this->_propertyHolder['type_id']
      );
    } else {
      $query = 'SELECT type_id FROM ' . $this->_config['table']['association'] . 
        ' WHERE id = ' . $this->_dbId;
      $mysqlResult = $this->_mysql->execute($query);
      $result = $mysqlResult->fetch();
      $this->_propertyHolder['type_id'] = $result['type_id'];
      return $this->_parent->_getConstructByVerifiedId('TopicImpl-' . $result['type_id']);
    }
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
  public function setType(Topic $type)
  {
    if (!$this->_topicMap->equals($type->_topicMap)) {
      throw new ModelConstraintException(
        $this, 
        __METHOD__ . ConstructImpl::$_sameTmConstraintErrMsg
      );
    }
    $this->_mysql->startTransaction();
    $query = 'UPDATE ' . $this->_config['table']['association'] . 
      ' SET type_id = ' . $type->_dbId . 
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    
    $hash = $this->_parent->_getAssocHash($type, $this->getScope(), $this->getRoles());
    $this->_parent->_updateAssocHash($this->_dbId, $hash);
    $this->_mysql->finishTransaction();
    
    if (!$this->_mysql->hasError()) {
      // reference needed for merging context
      $this->_propertyHolder['type_id'] =& $type->_dbId;
      $this->_postSave();
    }
  }
  
  /**
   * Removes this association.
   * 
   * @override
   * @return void
   */
  public function remove()
  {
    $this->_preDelete();
    $scopeObj = $this->_getScopeObject();
    $query = 'DELETE FROM ' . $this->_config['table']['association'] . 
      ' WHERE id = ' . $this->_dbId;
    $this->_mysql->execute($query);
    if (!$this->_mysql->hasError()) {
      if (!$scopeObj->isUnconstrained()) {
        $this->_unsetScope($scopeObj);// triggers clean up routine
      }
      $this->_parent->_removeAssociationFromCache($this->getId());
      $this->_id = 
      $this->_dbId = null;
    }
  }
}
?>