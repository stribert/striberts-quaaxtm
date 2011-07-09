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
 * Index for type-instance relationships between {@link TopicImpl}s 
 * and for {@link Typed} Topic Maps constructs.
 * 
 * This index provides access to {@link TopicImpl}s used in type-instance relationships 
 * (see http://www.isotopicmaps.org/sam/sam-model/#sect-types) or as type of a 
 * {@link Typed} construct.
 * Further, the retrieval of {@link AssociationImpl}s, {@link RoleImpl}s, 
 * {@link OccurrenceImpl}s, and {@link NameImpl}s by their <var>type</var> property is 
 * supported.
 *
 * @package index
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class TypeInstanceIndexImpl extends IndexImpl implements TypeInstanceIndex {
  
  /**
   * Returns the topics in the topic map whose type property equals one of those 
   * <var>types</var> at least. If types' length = 1, <var>matchAll</var> is 
   * interpreted <var>true</var>
   * 
   * Note: Implementations may return only those topics whose <var>types</var>
   * property contains the type and may ignore type-instance relationships 
   * (see {@link http://www.isotopicmaps.org/sam/sam-model/#sect-types}) which are 
   * modeled as association.
   * Further, supertype-subtype relationships (see 
   * {@link http://www.isotopicmaps.org/sam/sam-model/#sect-subtypes}) may also be ignored.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array An array containing the types of the {@link TopicImpl}s to be returned.
   * @param boolean If <var>true</var>, a topic must be an instance of
   *        all <var>types</var>, if <var>false</var> the topic must be 
   *        an instance of one type at least. If types' length = 1, matchAll 
   *        is interpreted <var>true</var>.
   * @return array An array containing {@link TopicImpl}s.
   * @throws InvalidArgumentException If <var>types</var> does not exclusively contain 
   * 				{@link TopicImpl}s.
   */
  public function getTopics(array $types, $matchAll) {
    $topics = array();
    $count = count($types);
    if ($count == 0) {
      $query = 'SELECT t1.id FROM ' . $this->_config['table']['topic'] . ' t1  
        INNER JOIN ' . $this->_config['table']['instanceof'] . ' t2 ON t1.id = t2.topic_id
				WHERE t1.topicmap_id = ' . $this->_tmDbId . ' GROUP BY t1.id';
    } elseif ($count == 1) {
      if (!$types[0] instanceof Topic) {
        throw new InvalidArgumentException('Type must be a topic.');
      }
      $query = 'SELECT t1.id FROM ' . $this->_config['table']['topic'] . ' t1 
      	INNER JOIN ' . $this->_config['table']['instanceof'] . ' t2 
      	ON t1.id = t2.topic_id  
				WHERE t1.topicmap_id = ' . $this->_tmDbId . ' AND t2.type_id = ' . $types[0]->getDbId();
    } else {
      $_types = array();
      foreach ($types as $type) {
        if (!$type instanceof Topic) {
          throw new InvalidArgumentException('Type must be a topic.');
        }
        $_types[$type->getDbId()] = $type;
      }
      $typesDbIds = array_keys($_types);
      $idsImploded = implode(',', $typesDbIds);
      $query = 'SELECT t1.id FROM ' . $this->_config['table']['topic'] . ' t1 
      	INNER JOIN ' . $this->_config['table']['instanceof'] . ' t2 
      	ON t1.id = t2.topic_id  
				WHERE t1.topicmap_id = ' . $this->_tmDbId . ' AND t2.type_id IN (' . $idsImploded . ') 
				GROUP BY t1.id';
      if ((boolean)$matchAll) {
        $query .= ' HAVING COUNT(*) = ' . count($typesDbIds);
      }
    }
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $topics[] = new TopicImpl(
        $result['id'], $this->_mysql, $this->_config, $this->_topicMap
      );
    }
    return $topics;
  }

  /**
   * Returns the topics in topic map which are used as type in an 
   * "type-instance"-relationship.
   * 
   * Note: Implementations may return only those topics which are member
   * of the <var>types</var> property of other topics and may ignore
   * type-instance relationships (see {@link http://www.isotopicmaps.org/sam/sam-model/#sect-types}) 
   * which are modelled as association.
   * Further, supertype-subtype relationships (see 
   * {@link http://www.isotopicmaps.org/sam/sam-model/#sect-subtypes}) may also be ignored.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getTopicTypes() {
    $types = array();
    $query = 'SELECT t1.id FROM ' . $this->_config['table']['topic'] . ' t1  
      INNER JOIN ' . $this->_config['table']['instanceof'] . ' t2 ON t1.id = t2.type_id
			WHERE t1.topicmap_id = ' . $this->_tmDbId . ' GROUP BY t1.id';
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $types[] = new TopicImpl(
        $result['id'], $this->_mysql, $this->_config, $this->_topicMap
      );
    }
    return $types;
  }

  /**
   * Returns the associations in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link AssociationImpl}s to be returned.
   * @return array An array containing {@link AssociationImpl}s.
   */
  public function getAssociations(Topic $type) {
    $assocs = array();
    $query = 'SELECT id FROM ' . $this->_config['table']['association'] . ' 
			WHERE type_id = ' . $type->getDbId() . ' AND topicmap_id = ' . $this->_tmDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['type_id'] = $type->getDbId();
      
      $assocs[] = new AssociationImpl(
        $result['id'], 
        $this->_mysql, 
        $this->_config, 
        $this->_topicMap, 
        $propertyHolder
      );
    }
    return $assocs;
  }

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link AssociationImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getAssociationTypes() {
    $types = array();
    $query = 'SELECT type_id FROM ' . $this->_config['table']['association'] . ' 
			WHERE topicmap_id = ' . $this->_tmDbId . ' GROUP BY type_id';
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $types[] = new TopicImpl(
        $result['type_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
    }
    return $types;
  }

  /**
   * Returns the roles in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link RoleImpl}s to be returned.
   * @return array An array containing {@link RoleImpl}s.
   */
  public function getRoles(Topic $type) {
    $roles = array();
    $query = 'SELECT t1.id, t1.association_id, t1.player_id 
    	FROM ' . $this->_config['table']['assocrole'] . ' t1 
    	INNER JOIN ' . $this->_config['table']['association'] . ' t2 
    	ON t1.association_id = t2.id 
			WHERE t1.type_id = ' . $type->getDbId() . ' AND t2.topicmap_id = ' . $this->_tmDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['type_id'] = $type->getDbId();
      $propertyHolder['player_id'] = $result['player_id'];
      
      $parent = new AssociationImpl(
        $result['association_id'], 
        $this->_mysql, 
        $this->_config, 
        $this->_topicMap
      );
      
      $roles[] = new RoleImpl(
        $result['id'], 
        $this->_mysql, 
        $this->_config, 
        $parent, 
        $this->_topicMap, 
        $propertyHolder
      );
    }
    return $roles;
  }

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link RoleImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getRoleTypes() {
    $types = array();
    $query = 'SELECT t1.type_id FROM ' . $this->_config['table']['assocrole'] . ' t1  
      INNER JOIN ' . $this->_config['table']['association'] . ' t2 ON t1.association_id = t2.id
			WHERE t2.topicmap_id = ' . $this->_tmDbId . ' GROUP BY t1.type_id';
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $types[] = new TopicImpl(
        $result['type_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
    }
    return $types;
  }

  /**
   * Returns the topic names in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link NameImpl}s to be returned.
   * @return array An array containing {@link NameImpl}s.
   */
  public function getNames(Topic $type) {
    $names = array();
    $query = 'SELECT t1.id, t1.topic_id, t1.value 
    	FROM ' . $this->_config['table']['topicname'] . ' t1 
    	INNER JOIN ' . $this->_config['table']['topic'] . ' t2 
    	ON t1.topic_id = t2.id 
			WHERE t1.type_id = ' . $type->getDbId() . ' AND t2.topicmap_id = ' . $this->_tmDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['type_id'] = $type->getDbId();
      $propertyHolder['value'] = $result['value'];
      
      $parent = new TopicImpl(
        $result['topic_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
      
      $names[] = new NameImpl(
        $result['id'], 
        $this->_mysql, 
        $this->_config, 
        $parent, 
        $this->_topicMap, 
        $propertyHolder
      );
    }
    return $names;
  }

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link NameImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getNameTypes() {
    $types = array();
    $query = 'SELECT t1.type_id FROM ' . $this->_config['table']['topicname'] . ' t1  
      INNER JOIN ' . $this->_config['table']['topic'] . ' t2 ON t1.topic_id = t2.id
			WHERE t2.topicmap_id = ' . $this->_tmDbId . ' GROUP BY t1.type_id';
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $types[] = new TopicImpl(
        $result['type_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
    }
    return $types;
  }

  /**
   * Returns the occurrences in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link OccurrenceImpl}s to be returned.
   * @return array An array containing {@link OccurrenceImpl}s.
   */
  public function getOccurrences(Topic $type) {
    $occs = array();
    $query = 'SELECT t1.id, t1.topic_id, t1.value, t1.datatype 
    	FROM ' . $this->_config['table']['occurrence'] . ' t1 
    	INNER JOIN ' . $this->_config['table']['topic'] . ' t2 
    	ON t1.topic_id = t2.id 
			WHERE t1.type_id = ' . $type->getDbId() . ' AND t2.topicmap_id = ' . $this->_tmDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['type_id'] = $type->getDbId();
      $propertyHolder['value'] = $result['value'];
      $propertyHolder['datatype'] = $result['datatype'];
      
      $parent = new TopicImpl(
        $result['topic_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
      
      $occs[] = new OccurrenceImpl(
        $result['id'], 
        $this->_mysql, 
        $this->_config, 
        $parent, 
        $this->_topicMap, 
        $propertyHolder
      );
    }
    return $occs;
  }

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link OccurrenceImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getOccurrenceTypes() {
    $types = array();
    $query = 'SELECT t1.type_id FROM ' . $this->_config['table']['occurrence'] . ' t1  
      INNER JOIN ' . $this->_config['table']['topic'] . ' t2 ON t1.topic_id = t2.id
			WHERE t2.topicmap_id = ' . $this->_tmDbId . ' GROUP BY t1.type_id';
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $types[] = new TopicImpl(
        $result['type_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
    }
    return $types;
  }
}
?>