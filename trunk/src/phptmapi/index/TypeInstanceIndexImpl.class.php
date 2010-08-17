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
   */
  public function getTopics(array $types, $matchAll) {
    $topics = array();
    $count = count($types);
    if ($count == 0) {
      $query = 'SELECT t1.id FROM ' . $this->config['table']['topic'] . ' t1  
        INNER JOIN ' . $this->config['table']['instanceof'] . ' t2 ON t1.id = t2.topic_id
				WHERE t1.topicmap_id = ' . $this->tmDbId . ' GROUP BY t1.id';
    } elseif ($count == 1) {
      $type = $types[0];
      if ($type instanceof Topic) {
        $typeDbId = $this->getConstructDbId($type);
        $query = 'SELECT t1.id FROM ' . $this->config['table']['topic'] . ' t1  
          INNER JOIN ' . $this->config['table']['instanceof'] . ' t2 ON t1.id = t2.topic_id
  				WHERE t1.topicmap_id = ' . $this->tmDbId . ' AND t2.type_id = ' . $typeDbId;
      } else {
        return $this->getTopics(array(), true);
      }
    } else {
      $_types = array();
      foreach ($types as $type) {
        if ($type instanceof Topic) {
          $_types[$this->getConstructDbId($type)] = $type;
        }
      }
      $count = count($_types);
      if ($count == 0) {
        return $this->getTopics(array(), true);
      } elseif ($count == 1) {
        return $this->getTopics(array_values($_types), true);
      } else {
        $typesDbIds = array_keys($_types);
        $vals = implode(',', $typesDbIds);
        $query = 'SELECT t1.id FROM ' . $this->config['table']['topic'] . ' t1  
          INNER JOIN ' . $this->config['table']['instanceof'] . ' t2 ON t1.id = t2.topic_id
  				WHERE t1.topicmap_id = ' . $this->tmDbId . ' AND t2.type_id IN (' . $vals . ') 
  				GROUP BY t1.id';
        if ((boolean)$matchAll) {
          $query .= ' HAVING COUNT(*) = ' . count($typesDbIds);
        }
      }
    }
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $topic = $this->topicMap->getConstructById(
        TopicMapImpl::TOPIC_CLASS_NAME . '-' . $result['id']
      );
      $topics[] = $topic;
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
    return array();
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
    return array();
  }

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link AssociationImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getAssociationTypes() {
    return array();
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
    return array();
  }

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link RoleImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getRoleTypes() {
    return array();
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
    return array();
  }

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link NameImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getNameTypes() {
    return array();
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
    return array();
  }

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link OccurrenceImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getOccurrenceTypes() {
    return array();
  }
}
?>