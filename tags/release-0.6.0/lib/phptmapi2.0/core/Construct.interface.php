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

/**
 * Base interface for all Topic Maps constructs.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Construct.interface.php 63 2010-02-14 21:17:19Z joschmidt $
 */
interface Construct {
    
  /**
   * Returns the parent of this construct. See the derived constructs for the particular
   * return value.
   * This method returns <var>null</var> iff this construct is a {@link TopicMap}
   * instance.
   *
   * @return Construct|null The parent of this construct or <var>null</var> iff the construct
   *        is an instance of {@link TopicMap}.
   */
  public function getParent();

  /**
   * Returns the {@link TopicMap} instance to which this Topic Maps construct 
   * belongs.
   * A {@link TopicMap} instance returns itself.
   *
   * @return TopicMap The topic map instance to which this construct belongs.
   */
  public function getTopicMap();

  /**
   * Returns the identifier of this construct.
   * This property has no representation in the Topic Maps - Data Model (TMDM).
   * The ID can be anything, so long as no other {@link Construct} in the 
   * same topic map has the same ID.
   *
   * @return string An identifier which identifies this construct uniquely within
   *        a topic map.
   */
  public function getId();

  /**
   * Returns the item identifiers of this Topic Maps construct.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of URIs representing the item identifiers.
   */
  public function getItemIdentifiers();

  /**
   * Adds an item identifier.
   * It is not allowed to have two {@link Construct}s in the same 
   * {@link TopicMap} with the same item identifier. 
   * If the two objects are {@link Topic}s, then they must be merged. 
   * If at least one of the two objects is not a {@link Topic}, an
   * {@link IdentityConstraintException} must be reported. 
   *
   * @param string The item identifier to be added; must not be <var>null</var>.
   * @return void
   * @throws {@link IdentityConstraintException} If another construct has an item
   *        identifier which is equal to <var>itemIdentifier</var>.
   * @throws {@link ModelConstraintException} If the item identifier is <var>null</var>.
   */
  public function addItemIdentifier($itemIdentifier);

  /**
   * Removes an item identifier.
   *
   * @param string The item identifier to be removed from this construct, 
   *        if present (<var>null</var> is ignored).
   * @return void
   */
  public function removeItemIdentifier($itemIdentifier);

  /**
   * Deletes this construct from its parent container.
   * Iff this construct is an instance of {@link Topic} this method throws a 
   * {@link TopicInUseException} if the topic plays a {@link Role}, is used as type 
   * of a {@link Typed} construct, or if it is used as theme for a {@link Scoped} 
   * construct, or if it reifies a {@link Reifiable}.
   * 
   * After invocation of this method, the construct is in an undefined state and 
   * MUST NOT be used further.
   * 
   * @return void
   */
  public function remove();

  /**
   * Returns <var>true</var> if the other construct is equal to this one,
   * <var>false</var> otherwise. 
   * Equality must be the result of comparing the ids of the two constructs.
   * 
   * Note: This equality test does not reflect any equality rule according
   * to the Topic Maps - Data Model (TMDM) by intention.
   *
   * @param Construct The construct to compare this construct against.
   * @return boolean
   */
  public function equals(Construct $other);

  /**
   * Returns a hash code value.
   * The returned hash code is equal to the hash code of the {@link getId()}
   * property.
   *
   * @return string
   */
  public function hashCode();
}
?>