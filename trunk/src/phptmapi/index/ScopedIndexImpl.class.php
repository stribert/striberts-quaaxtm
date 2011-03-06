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
 * Index for {@link ScopedImpl} statements and their scope.
 * 
 * This index provides access to {@link AssociationImpl}s, {@link OccurrenceImpl}s,
 * {@link NameImpl}s, and {@link VariantImpl}s by their scope property and to
 * {@link TopicImpl}s which are used as theme in a scope.
 *
 * @package index
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class ScopedIndexImpl extends IndexImpl implements ScopedIndex {

  /**
   * Returns the {@link Association}s in the topic map whose scope property 
   * equals one of those <var>themes</var> at least. If themes' length = 1,
   * <var>matchAll</var> is interpreted <var>true</var>. If themes' length = 0, 
   * <var>themes</var> is interpreted as the unconstrained scope.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array Scope of the {@link AssociationImpl}s to be returned. 
   * 				If <var>themes</var> is an empty array all {@link AssociationImpl}s in the 
   * 				unconstrained scope are returned.
   * @param boolean If true the scope property of an association must match all themes, 
   *        if false one theme must be matched at least. If themes' length = 1, matchAll 
   *        is interpreted true.
   * @return array An array containing {@link AssociationImpl}s.
   * @throws InvalidArgumentException If <var>themes</var> does not exclusively contain 
   * 				{@link TopicImpl}s.
   */
  public function getAssociations(array $themes, $matchAll) {
    $assocs = array();
    $count = count($themes);
    if ($count == 0) {
      $query = 'SELECT t1.id, t1.type_id FROM ' . $this->config['table']['association'] . ' t1 
      	INNER JOIN ' . $this->config['table']['association_scope'] . ' t2 ON t2.association_id = t1.id 
      	LEFT JOIN ' . $this->config['table']['theme'] . ' t3 ON t3.scope_id = t2.scope_id 
      	WHERE t1.topicmap_id = ' . $this->tmDbId . ' AND t3.scope_id IS NULL';
    } elseif ($count == 1) {
      $theme = $themes[0];
      if (!$theme instanceof Topic) {
        throw new InvalidArgumentException(
        	'Error in ' . __METHOD__ . ': Theme must be a topic.'
        );
      }
      $query = 'SELECT t1.id, t1.type_id FROM ' . $this->config['table']['association'] . ' t1 
      	INNER JOIN ' . $this->config['table']['association_scope'] . ' t2 ON t2.association_id = t1.id 
      	INNER JOIN ' . $this->config['table']['theme'] . ' t3 ON t3.scope_id = t2.scope_id 
      	WHERE t1.topicmap_id = ' . $this->tmDbId . ' AND t3.topic_id = ' . $theme->getDbId();
    } else {
      $_themes = array();
      foreach ($themes as $theme) {
        if (!$theme instanceof Topic) {
          throw new InvalidArgumentException(
          	'Error in ' . __METHOD__ . ': Theme must be a topic.'
          );
        }
        $_themes[$theme->getDbId()] = null;
      }
      $themesDbIds = array_keys($_themes);
      $idsImploded = implode(',', $themesDbIds);
      $query = 'SELECT t1.id, t1.type_id FROM ' . $this->config['table']['association'] . ' t1 
      	INNER JOIN ' . $this->config['table']['association_scope'] . ' t2 ON t2.association_id = t1.id 
      	INNER JOIN ' . $this->config['table']['theme'] . ' t3 ON t3.scope_id = t2.scope_id 
      	WHERE t1.topicmap_id = ' . $this->tmDbId . ' AND t3.topic_id IN(' . $idsImploded . ') 
      	GROUP BY t1.id';
      if ((boolean)$matchAll) {
        $query .= ' HAVING COUNT(*) = ' . count($themesDbIds);
      }
    }
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId($result['type_id']);
      
      $assocs[] = new AssociationImpl(
        $result['id'], 
        $this->mysql, 
        $this->config, 
        $this->topicMap, 
        $propertyHolder
      );
    }
    return $assocs;
  }

  /**
   * Returns the {@link TopicImpl}s in the topic map used in the scope property of 
   * {@link AssociationImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getAssociationThemes() {
    $themes = array();
    $query = 'SELECT t1.topic_id FROM ' . $this->config['table']['theme'] . ' t1 
    	INNER JOIN ' . $this->config['table']['association_scope'] . ' t2 ON t2.scope_id = t1.scope_id 
    	INNER JOIN ' . $this->config['table']['association'] . ' t3 ON t3.id = t2.association_id 
    	WHERE t3.topicmap_id = ' . $this->tmDbId . ' GROUP BY t1.topic_id';
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $themes[] = new TopicImpl(
        $result['topic_id'], $this->mysql, $this->config, $this->topicMap
      );
    }
    return $themes;
  }

  /**
   * Returns the {@link NameImpl}s in the topic map whose scope property 
   * equals one of those <var>themes</var> at least. If themes' length = 1,
   * <var>matchAll</var> is interpreted <var>true</var>. If themes' length = 0, 
   * <var>themes</var> is interpreted as the unconstrained scope.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array Scope of the {@link NameImpl}s to be returned.
   * 				If <var>themes</var> is an empty array all {@link NameImpl}s in the 
   * 				unconstrained scope are returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length = 1, matchAll 
   *        is interpreted true.
   * @return array An array containing {@link NameImpl}s.
   * @throws InvalidArgumentException If <var>themes</var> does not exclusively contain 
   * 				{@link TopicImpl}s.
   */
  public function getNames(array $themes, $matchAll) {
    $names = array();
    $count = count($themes);
    if ($count == 0) {
      $query = 'SELECT t1.id, t1.topic_id, t1.type_id, t1.value 
      	FROM ' . $this->config['table']['topicname'] . ' t1 
      	INNER JOIN ' . $this->config['table']['topic'] . ' t2 ON t2.id = t1.topic_id
      	INNER JOIN ' . $this->config['table']['topicname_scope'] . ' t3 ON t3.topicname_id = t1.id 
      	LEFT JOIN ' . $this->config['table']['theme'] . ' t4 ON t4.scope_id = t3.scope_id 
      	WHERE t2.topicmap_id = ' . $this->tmDbId . ' AND t4.scope_id IS NULL';
    } elseif ($count == 1) {
      $theme = $themes[0];
      if (!$theme instanceof Topic) {
        throw new InvalidArgumentException(
        	'Error in ' . __METHOD__ . ': Theme must be a topic.'
        );
      }
      $query = 'SELECT t1.id, t1.topic_id, t1.type_id, t1.value 
      	FROM ' . $this->config['table']['topicname'] . ' t1 
      	INNER JOIN ' . $this->config['table']['topic'] . ' t2 ON t2.id = t1.topic_id 
      	INNER JOIN ' . $this->config['table']['topicname_scope'] . ' t3 ON t3.topicname_id = t1.id 
      	INNER JOIN ' . $this->config['table']['theme'] . ' t4 ON t4.scope_id = t3.scope_id 
      	WHERE t2.topicmap_id = ' . $this->tmDbId . ' AND t4.topic_id = ' . $theme->getDbId();
    } else {
      $_themes = array();
      foreach ($themes as $theme) {
        if (!$theme instanceof Topic) {
          throw new InvalidArgumentException('Error in ' . __METHOD__ . 'Theme must be a topic.');
        }
        $_themes[$theme->getDbId()] = null;
      }
      $themesDbIds = array_keys($_themes);
      $idsImploded = implode(',', $themesDbIds);
      $query = 'SELECT t1.id, t1.topic_id, t1.type_id, t1.value 
      	FROM ' . $this->config['table']['topicname'] . ' t1 
      	INNER JOIN ' . $this->config['table']['topic'] . ' t2 ON t2.id = t1.topic_id 
      	INNER JOIN ' . $this->config['table']['topicname_scope'] . ' t3 ON t3.topicname_id = t1.id 
      	INNER JOIN ' . $this->config['table']['theme'] . ' t4 ON t4.scope_id = t3.scope_id 
      	WHERE t2.topicmap_id = ' . $this->tmDbId . ' AND t4.topic_id IN(' . $idsImploded . ') GROUP BY t1.id';
      if ((boolean)$matchAll) {
        $query .= ' HAVING COUNT(*) = ' . count($themesDbIds);
      }
    }
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId((int)$result['type_id'])->setValue($result['value']);
      
      $parent = new TopicImpl(
        $result['topic_id'], $this->mysql, $this->config, $this->topicMap
      );
      
      $names[] = new NameImpl(
        $result['id'], 
        $this->mysql, 
        $this->config, 
        $parent, 
        $this->topicMap, 
        $propertyHolder
      );
    }
    return $names;
  }

  /**
   * Returns the topics in the topic map used in the scope property of 
   * {@link NameImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getNameThemes() {
    return $this->getCharacteristicThemes('topicname');
  }

  /**
   * Returns the {@link OccurrenceImpl}s in the topic map whose scope property 
   * equals one of those <var>themes</var> at least. If themes' length = 1,
   * <var>matchAll</var> is interpreted <var>true</var>. If themes' length = 0, 
   * <var>themes</var> is interpreted as the unconstrained scope.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array Scope of the {@link OccurrenceImpl}s to be returned.
   * 				If <var>themes</var> is an empty array all {@link OccurrenceImpl}s in the 
   * 				unconstrained scope are returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length = 1, matchAll 
   *        is interpreted true.
   * @return array An array containing {@link OccurrenceImpl}s.
   * @throws InvalidArgumentException If <var>themes</var> does not exclusively contain 
   * 				{@link Topic}s.
   */
  public function getOccurrences(array $themes, $matchAll) {
    $occs = array();
    $count = count($themes);
    if ($count == 0) {
      $query = 'SELECT t1.id, t1.topic_id, t1.type_id, t1.value, t1.datatype 
      	FROM ' . $this->config['table']['occurrence'] . ' t1 
      	INNER JOIN ' . $this->config['table']['topic'] . ' t2 
      	ON t2.id = t1.topic_id
      	INNER JOIN ' . $this->config['table']['occurrence_scope'] . ' t3 
      	ON t3.occurrence_id = t1.id 
      	LEFT JOIN ' . $this->config['table']['theme'] . ' t4 
      	ON t4.scope_id = t3.scope_id 
      	WHERE t2.topicmap_id = ' . $this->tmDbId . ' AND t4.scope_id IS NULL';
    } elseif ($count == 1) {
      $theme = $themes[0];
      if (!$theme instanceof Topic) {
        throw new InvalidArgumentException(
        	'Error in ' . __METHOD__ . ': Theme must be a topic.'
        );
      }
      $query = 'SELECT t1.id, t1.topic_id, t1.type_id, t1.value, t1.datatype 
      	FROM ' . $this->config['table']['occurrence'] . ' t1 
      	INNER JOIN ' . $this->config['table']['topic'] . ' t2 
      	ON t2.id = t1.topic_id 
      	INNER JOIN ' . $this->config['table']['occurrence_scope'] . ' t3 
      	ON t3.occurrence_id = t1.id 
      	INNER JOIN ' . $this->config['table']['theme'] . ' t4 
      	ON t4.scope_id = t3.scope_id 
      	WHERE t2.topicmap_id = ' . $this->tmDbId . ' AND t4.topic_id = ' . $theme->getDbId();
    } else {
      $_themes = array();
      foreach ($themes as $theme) {
        if (!$theme instanceof Topic) {
          throw new InvalidArgumentException(
          	'Error in ' . __METHOD__ . ': Theme must be a topic.'
          );
        }
        $_themes[$theme->getDbId()] = null;
      }
      $themesDbIds = array_keys($_themes);
      $idsImploded = implode(',', $themesDbIds);
      $query = 'SELECT t1.id, t1.topic_id, t1.type_id, t1.value, t1.datatype 
      	FROM ' . $this->config['table']['occurrence'] . ' t1 
      	INNER JOIN ' . $this->config['table']['topic'] . ' t2 
      	ON t2.id = t1.topic_id 
      	INNER JOIN ' . $this->config['table']['occurrence_scope'] . ' t3 
      	ON t3.occurrence_id = t1.id 
      	INNER JOIN ' . $this->config['table']['theme'] . ' t4 
      	ON t4.scope_id = t3.scope_id 
      	WHERE t2.topicmap_id = ' . $this->tmDbId . ' 
      	AND t4.topic_id IN(' . $idsImploded . ') GROUP BY t1.id';
      if ((boolean)$matchAll) {
        $query .= ' HAVING COUNT(*) = ' . count($themesDbIds);
      }
    }
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setTypeId((int)$result['type_id'])
        ->setValue($result['value'])
        ->setDatatype($result['datatype']);
      
      $parent = new TopicImpl(
        $result['topic_id'], $this->mysql, $this->config, $this->topicMap
      );
      
      $occs[] = new OccurrenceImpl(
        $result['id'], 
        $this->mysql, 
        $this->config, 
        $parent, 
        $this->topicMap, 
        $propertyHolder
      );
    }
    return $occs;
  }

  /**
   * Returns the topics in the topic map used in the scope property of 
   * {@link OccurrenceImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getOccurrenceThemes() {
    return $this->getCharacteristicThemes('occurrence');
  }

  /**
   * Returns the {@link VariantImpl}s in the topic map whose scope property 
   * equals one of those <var>themes</var> at least. If themes' length = 1,
   * <var>matchAll</var> is interpreted <var>true</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array Scope of the {@link VariantImpl}s to be returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length = 1, matchAll 
   *        is interpreted true.
   * @return array An array containing {@link VariantImpl}s.
   * @throws InvalidArgumentException If <var>themes</var> is an empty array, or if 
   * 				<var>themes</var> does not exclusively contain {@link TopicImpl}s.
   */
  public function getVariants(array $themes, $matchAll) {
    $variants = array();
    $count = count($themes);
    if ($count == 0) {
      throw new InvalidArgumentException(
      	'Error in ' . __METHOD__ . ': Themes must not be an empty array.'
      );
    } else if ($count == 1) {
      $theme = $themes[0];
      if (!$theme instanceof Topic) {
        throw new InvalidArgumentException(
        	'Error in ' . __METHOD__ . ': Theme must be a topic.'
        );
      }
      $query = 'SELECT t1.id, t1.topicname_id, t1.value, t1.datatype, t1.hash, t2.topic_id 
      	FROM ' . $this->config['table']['variant'] . ' t1 
      	INNER JOIN ' . $this->config['table']['topicname'] . ' t2 
      	ON t2.id = t1.topicname_id 
      	INNER JOIN ' . $this->config['table']['topic'] . ' t3 
      	ON t3.id = t2.topic_id 
      	INNER JOIN ' . $this->config['table']['variant_scope'] . ' t4 
      	ON t4.variant_id = t1.id 
      	INNER JOIN ' . $this->config['table']['theme'] . ' t5 
      	ON t5.scope_id = t4.scope_id 
      	WHERE t3.topicmap_id = ' . $this->tmDbId . ' AND t5.topic_id = ' . $theme->getDbId();
    } else {
      $_themes = array();
      foreach ($themes as $theme) {
        if (!$theme instanceof Topic) {
          throw new InvalidArgumentException(
          	'Error in ' . __METHOD__ . ': Theme must be a topic.'
          );
        }
        $_themes[$theme->getDbId()] = null;
      }
      $themesDbIds = array_keys($_themes);
      $idsImploded = implode(',', $themesDbIds);
      $query = 'SELECT t1.id, t1.topicname_id, t1.value, t1.datatype, t1.hash, t2.topic_id 
      	FROM ' . $this->config['table']['variant'] . ' t1 
      	INNER JOIN ' . $this->config['table']['topicname'] . ' t2 
      	ON t2.id = t1.topicname_id 
      	INNER JOIN ' . $this->config['table']['topic'] . ' t3 
      	ON t3.id = t2.topic_id 
      	INNER JOIN ' . $this->config['table']['variant_scope'] . ' t4 
      	ON t4.variant_id = t1.id 
      	INNER JOIN ' . $this->config['table']['theme'] . ' t5 
      	ON t5.scope_id = t4.scope_id 
      	WHERE t3.topicmap_id = ' . $this->tmDbId . ' 
      	AND t5.topic_id IN(' . $idsImploded . ') GROUP BY t1.id';
      if ((boolean)$matchAll) {
        $query .= ' HAVING COUNT(*) = ' . count($themesDbIds);
      }
    }
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder = new PropertyUtils();
      $propertyHolder->setValue($result['value'])->setDataType($result['datatype']);

      $parentTopic = new TopicImpl(
        $result['topic_id'], $this->mysql, $this->config, $this->topicMap
      );
      
      $parentName = new NameImpl(
        $result['topicname_id'], 
        $this->mysql, 
        $this->config, 
        $parentTopic, 
        $this->topicMap
      );
      
      $variants[] = new VariantImpl(
        $result['id'], 
        $this->mysql, 
        $this->config, 
        $parentName, 
        $this->topicMap, 
        $propertyHolder, 
        $result['hash']
      );
    }
    return $variants;
  }

  /**
   * Returns the topics in the topic map used in the scope property of 
   * {@link VariantImpl}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link TopicImpl}s.
   */
  public function getVariantThemes() {
    return $this->getCharacteristicThemes('variant');
  }
  
  /**
   * Returns the topics in the topic map used in the scope property of topic names,
   * occurrences, or variants.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param string The construct (table) name.
   * @return array An array containing {@link TopicImpl}s.
   */
  private function getCharacteristicThemes($constructName) {
    $themes = array();
    $query = 'SELECT t1.topic_id FROM ' . $this->config['table']['theme'] . ' t1 
    	INNER JOIN ' . $this->config['table'][$constructName . '_scope'] . ' t2 
    	ON t2.scope_id = t1.scope_id 
    	INNER JOIN ' . $this->config['table']['topicmapconstruct'] . ' t3 
    	ON t3.' . $constructName . '_id = t2.' . $constructName . '_id 
    	WHERE t3.topicmap_id = ' . $this->tmDbId . 
    	' GROUP BY t1.topic_id';
    $mysqlResult = $this->mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $themes[] = new TopicImpl(
        $result['topic_id'], $this->mysql, $this->config, $this->topicMap
      );
    }
    return $themes;
  }
}
?>