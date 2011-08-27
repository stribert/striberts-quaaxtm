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

require_once('Index.interface.php');

/**
 * Index for type-instance relationships between {@link Topic}s 
 * and for {@link Typed} Topic Maps constructs.
 * 
 * This index provides access to {@link Topic}s used in type-instance relationships 
 * (see http://www.isotopicmaps.org/sam/sam-model/#sect-types) or as type of a 
 * {@link Typed} construct.
 * Further, the retrieval of {@link Association}s, {@link Role}s, 
 * {@link Occurrence}s, and {@link Name}s by their <var>type</var> property is 
 * supported.
 *
 * @package index
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: TypeInstanceIndex.interface.php 68 2011-01-09 13:41:40Z joschmidt $
 */
interface TypeInstanceIndex extends Index
{
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
   * @param array An array containing the types of the {@link Topic}s to be returned.
   * @param boolean If <var>true</var>, a topic must be an instance of
   *        all <var>types</var>, if <var>false</var> the topic must be 
   *        an instance of one type at least. If types' length = 1, matchAll 
   *        is interpreted <var>true</var>.
   * @return array An array containing {@link Topic}s.
   * @throws InvalidArgumentException If <var>types</var> does not exclusively contain 
   * 				{@link Topic}s.
   */
  public function getTopics(array $types, $matchAll);

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
   * @return array An array containing {@link Topic}s.
   */
  public function getTopicTypes();

  /**
   * Returns the associations in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link Association}s to be returned.
   * @return array An array containing {@link Association}s.
   */
  public function getAssociations(Topic $type);

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link Association}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getAssociationTypes();

  /**
   * Returns the roles in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link Role}s to be returned.
   * @return array An array containing {@link Role}s.
   */
  public function getRoles(Topic $type);

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link Role}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getRoleTypes();

  /**
   * Returns the topic names in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link Name}s to be returned.
   * @return array An array containing {@link Name}s.
   */
  public function getNames(Topic $type);

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link Name}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getNameTypes();

  /**
   * Returns the occurrences in the topic map whose type property equals 
   * <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link Occurrence}s to be returned.
   * @return array An array containing {@link Occurrence}s.
   */
  public function getOccurrences(Topic $type);

  /**
   * Returns the topics in the topic map used in the type property of 
   * {@link Occurrence}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getOccurrenceTypes();
}
?>