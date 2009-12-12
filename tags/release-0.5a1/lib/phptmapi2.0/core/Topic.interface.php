<?php
/*
 * PHPTMAPI is hereby released into the public domain; 
 * and comes with NO WARRANTY.
 * 
 * No one owns PHPTMAPI: you may use it freely in both commercial and
 * non-commercial applications, bundle it with your software
 * distribution, include it on a CD-ROM, list the source code in a
 * book, mirror the documentation at your own web site, or use it in
 * any other way you see fit.
 */

require_once('Construct.interface.php');

/**
 * Represents a topic item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#d0e739}.
 * 
 * Inherited method <var>getParent()</var> from {@link Construct} returns the {@link TopicMap}
 * to which this topic belongs.
 * 
 * Inherited method <var>addItemIdentifier()</var> from {@link Construct} throws an 
 * {@link IdentityConstraintException} if adding the specified item identifier would make 
 * this topic represent the same subject as another topic and the feature "automerge" 
 * ({@link http://tmapi.org/features/automerge}) is disabled.
 * 
 * Inherited method <var>remove()</var> from {@link Construct} throws a 
 * {@link TopicInUseException} if the topic plays a {@link Role}, is used as type of a 
 * {@link Typed} construct, or if it is used as theme for a {@link Scoped} construct, 
 * or if it reifies a {@link Reifiable}.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Topic.interface.php 54 2009-07-15 21:59:42Z joschmidt $
 */
interface Topic extends Construct {

  /**
   * Returns the subject identifiers assigned to this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the subject identifiers.
   */
  public function getSubjectIdentifiers();

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
  public function addSubjectIdentifier($subjectIdentifier);

  /**
   * Removes a subject identifier from this topic.
   *
   * @param string The subject identifier to be removed from this topic, 
   *        if present (<var>null</var> is ignored).
   * @return void
   */
  public function removeSubjectIdentifier($subjectIdentifier);

  /**
   * Returns the subject locators assigned to this topic.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the subject locators.
   */
  public function getSubjectLocators();

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
  public function addSubjectLocator($subjectLocator);

  /**
   * Removes a subject locator from this topic.
   *
   * @param string The subject locator to be removed from this topic, 
   *        if present (<var>null</var> is ignored).
   * @return void
   */
  public function removeSubjectLocator($subjectLocator);

  /**
   * Returns the {@link Name}s of this topic. 
   * If <var>type</var> is not <var>null</var> all names with the specified <var>type</var> 
   * are returned.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link Name}s to be returned. Default <var>null</var>.
   * @return array An array containing a set of {@link Name}s belonging to this topic.
   */
  public function getNames(Topic $type=null);

  /**
   * Creates a {@link Name} for this topic with the specified <var>value</var>, <var>type</var>, 
   * and <var>scope</var>.
   * If <var>type</var> is <var>null</var> the created {@link Name} will have the default 
   * name type (a {@link Topic} with the subject identifier 
   * http://psi.topicmaps.org/iso13250/model/topic-name).
   * 
   * @param string The string value of the name; must not be <var>null</var>.
   * @param Topic The name type. Default <var>null</var>.
   * @param array An array containing {@link Topic}s - each representing a theme. 
   *        If the array's length is 0 (default), the name will be in the 
   *        unconstrained scope.
   * @return Name The newly created {@link Name}.
   * @throws {@link ModelConstraintException} If the <var>value</var> is <var>null</var> or
   *        the <var>type</var> or a theme does not belong to the parent topic map.
   */
  public function createName($value, Topic $type=null, array $scope=array());

  /**
   * Returns the {@link Occurrence}s of this topic. 
   * If <var>type</var> is not <var>null</var> all occurrences with the specified 
   * <var>type</var> are returned.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param Topic The type of the {@link Occurrence}s to be returned. Default <var>null</var>.
   * @return array An array containing a set of {@link Occurrence}s belonging to 
   *        this topic.
   */
  public function getOccurrences(Topic $type=null);

  /**
   * Creates an {@link Occurrence} for this topic with the specified 
   * <var>type</var>, <var>value</var>, <var>datatype</var>, and <var>scope</var>.
   * The newly created {@link Occurrence} will have the datatype specified 
   * by <var>datatype</var>.
   * 
   * @param Topic The occurrence type.
   * @param string A string representation of the value; must not be <var>null</var>.
   * @param string A URI indicating the datatype of the <var>value</var>; 
   *        must not be <var>null</var>. E.g. http://www.w3.org/2001/XMLSchema#string 
   *        indicates a string value.
   * @param array An array containing {@link Topic}s - each representing a theme. 
   *        If the array's length is 0 (default), the occurrence will be in the 
   *        unconstrained scope.
   * @return Occurrence The newly created {@link Occurrence}.
   * @throws {@link ModelConstraintException} If either the <var>value</var> or the
   *        <var>datatype</var> is <var>null</var>; or the <var>type</var> or a theme 
   *        does not belong to the parent topic map.
   */
  public function createOccurrence(Topic $type, $value, $datatype, array $scope=array());

  /**
   * Returns the {@link Role}s played by this topic. 
   * If <var>type</var> is not <var>null</var> all roles played by this topic with the 
   * specified <var>type</var> are returned. 
   * If <var>assocType</var> is not <var>null</var> only the {@link Association}s with the 
   * specified <var>assocType</var> are considered.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @param Topic The type of the {@link Role}s to be returned. Default <var>null</var>.
   * @param Topic The type of the {@link Association} from which the
   *        returned roles must be part of. Default <var>null</var>.
   * @return array An array containing a set of {@link Role}s played by this topic.
   */
  public function getRolesPlayed(Topic $type=null, Topic $assocType=null);

  /**
   * Returns the types of which this topic is an instance of.
   * This method may return only those types which where added by 
   * {@link addType(Topic $type)} and may ignore type-instance relationships 
   * (see {@link http://www.isotopicmaps.org/sam/sam-model/#sect-types}) which are modeled 
   * as association.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link Topic}s.
   */
  public function getTypes();

  /**
   * Adds a type to this topic.
   * Implementations may or may not create an association for types added
   * by this method. In any case, every type which was added by this method 
   * must be returned by the {@link getTypes()} method.
   * 
   * @param Topic The type of which this topic should become an instance of.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>type</var> does not belong 
   *        to the parent topic map.
   */
  public function addType(Topic $type);

  /**
   * Removes a type from this topic.
   *
   * @param Topic The type to remove.
   * @return void
   */
  public function removeType(Topic $type);

  /**
   * Returns the {@link Construct} which is reified by this topic.
   *
   * @return Reifiable|null The {@link Reifiable} that is reified by this topic or 
   *        <var>null</var> if this topic does not reify a statement.
   */
  public function getReified();

  /**
   * Merges another topic into this topic.
   * Merging a topic into this topic causes this topic to gain all 
   * of the characteristics of the other topic and to replace the other 
   * topic wherever it is used as type, theme, or reifier. 
   * After this method completes, <var>other</var> will have been removed from 
   * the {@link TopicMap}.
   * 
   * If <var>$this->equals($other)</var> no changes are made to the topic.
   * 
   * NOTE: The other topic MUST belong to the same {@link TopicMap} instance 
   * as this topic! 
   * 
   * @param Topic The topic to be merged into this topic.
   * @return void
   * @throws InvalidArgumentException If the other topic to be merged does not belong 
   *        to the same topic map.
   * @throws {@link ModelConstraintException} If the two topics to be merged reify different 
   *        Topic Maps constructs.
   */
  public function mergeIn(Topic $other);
}
?>