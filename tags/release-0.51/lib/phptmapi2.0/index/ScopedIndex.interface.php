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
 * Index for {@link Scoped} statements and their scope.
 * 
 * This index provides access to {@link Association}s, {@link Occurrence}s,
 * {@link Name}s, and {@link Variant}s by their scope property and to
 * {@link Topic}s which are used as theme in a scope.
 *
 * @package index
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: ScopedIndex.interface.php 9 2008-11-03 20:55:37Z joschmidt $
 */
interface ScopedIndex extends Index {

  /**
   * Returns the {@link Association}s in the topic map whose scope property 
   * equals one of those <var>themes</var> at least. If themes' length = 1,
   * <var>matchAll</var> is interpreted <var>true</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array Scope of the {@link Association}s to be returned.
   * @param boolean If true the scope property of an association must match all themes, 
   *        if false one theme must be matched at least. If themes' length = 1, matchAll 
   *        is interpreted true.
   * @return array An array containing {@link Association}s.
   * @throws InvalidArgumentException If <var>themes</var> is <var>null</var>.
   */
  public function getAssociations($themes, $matchAll);

  /**
   * Returns the topics in the topic map used in the scope property of 
   * {@link Association}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getAssociationThemes();

  /**
   * Returns the {@link Name}s in the topic map whose scope property 
   * equals one of those <var>themes</var> at least. If themes' length = 1,
   * <var>matchAll</var> is interpreted <var>true</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array Scope of the {@link Name}s to be returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length = 1, matchAll 
   *        is interpreted true.
   * @return array An array containing {@link Name}s.
   * @throws InvalidArgumentException If <var>themes</var> is <var>null</var>.
   */
  public function getNames($themes, $matchAll);

  /**
   * Returns the topics in the topic map used in the scope property of 
   * {@link Name}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getNameThemes();

  /**
   * Returns the {@link Occurrence}s in the topic map whose scope property 
   * equals one of those <var>themes</var> at least. If themes' length = 1,
   * <var>matchAll</var> is interpreted <var>true</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array Scope of the {@link Occurrence}s to be returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length = 1, matchAll 
   *        is interpreted true.
   * @return array An array containing {@link Occurrence}s.
   * @throws InvalidArgumentException If <var>themes</var> is <var>null</var>.
   */
  public function getOccurrences($themes, $matchAll);

  /**
   * Returns the topics in the topic map used in the scope property of 
   * {@link Occurrence}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getOccurrenceThemes();

  /**
   * Returns the {@link Variant}s in the topic map whose scope property 
   * equals one of those <var>themes</var> at least. If themes' length = 1,
   * <var>matchAll</var> is interpreted <var>true</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array Scope of the {@link Variant}s to be returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length = 1, matchAll 
   *        is interpreted true.
   * @return array An array containing {@link Variant}s.
   * @throws InvalidArgumentException If <var>themes</var> is <var>null</var>.
   */
  public function getVariants($themes, $matchAll);

  /**
   * Returns the topics in the topic map used in the scope property of 
   * {@link Variant}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getVariantThemes();
}
?>