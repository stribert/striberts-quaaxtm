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
 * {@link Name}s, and {@link IVariant}s by their scope property and to
 * {@link Topic}s which are used as theme in a scope.
 *
 * @package index
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: ScopedIndex.interface.php 89 2011-09-15 15:37:45Z joschmidt $
 */
interface ScopedIndex extends Index
{
  /**
   * Returns the {@link Association}s in the topic map whose scope property 
   * equals one of those themes at least. 
   * If themes' length == 1, <var>$matchAll</var> is interpreted <var>true</var>. 
   * If themes' length == 0, <var>$themes</var> is interpreted as the unconstrained 
   * scope.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array The scope of the {@link Association}s to be returned. 
   * 				If <var>$themes</var> is an empty array all {@link Association}s in the 
   * 				unconstrained scope are returned.
   * @param boolean If true the scope property of an association must match all themes, 
   *        if false one theme must be matched at least. If themes' length == 1, 
   *        <var>$matchAll</var> is interpreted true.
   * @return array An array containing {@link Association}s.
   * @throws InvalidArgumentException If <var>$themes</var> does not exclusively contain 
   * 				{@link Topic}s.
   */
  public function getAssociations(array $themes, $matchAll);

  /**
   * Returns the {@link Topic}s in the topic map used in the scope property of 
   * {@link Association}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getAssociationThemes();

  /**
   * Returns the {@link Name}s in the topic map whose scope property 
   * equals one of those themes at least. 
   * If themes' length == 1, <var>$matchAll</var> is interpreted <var>true</var>. 
   * If themes' length == 0, <var>$themes</var> is interpreted as the unconstrained 
   * scope.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array The scope of the {@link Name}s to be returned.
   * 				If <var>$themes</var> is an empty array all {@link Name}s in the 
   * 				unconstrained scope are returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length == 1, 
   *        <var>$matchAll</var> is interpreted true.
   * @return array An array containing {@link Name}s.
   * @throws InvalidArgumentException If <var>$themes</var> does not exclusively contain 
   * 				{@link Topic}s.
   */
  public function getNames(array $themes, $matchAll);

  /**
   * Returns the {@link Topic}s in the topic map used in the scope property of 
   * {@link Name}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getNameThemes();

  /**
   * Returns the {@link Occurrence}s in the topic map whose scope property 
   * equals one of those themes at least. 
   * If themes' length == 1, <var>$matchAll</var> is interpreted <var>true</var>. 
   * If themes' length == 0, <var>$themes</var> is interpreted as the unconstrained 
   * scope.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array The scope of the {@link Occurrence}s to be returned.
   * 				If <var>$themes</var> is an empty array all {@link Occurrence}s in the 
   * 				unconstrained scope are returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length == 1, 
   *        <var>$matchAll</var> is interpreted true.
   * @return array An array containing {@link Occurrence}s.
   * @throws InvalidArgumentException If <var>$themes</var> does not exclusively contain 
   * 				{@link Topic}s.
   */
  public function getOccurrences(array $themes, $matchAll);

  /**
   * Returns the {@link Topic}s in the topic map used in the scope property of 
   * {@link Occurrence}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getOccurrenceThemes();

  /**
   * Returns the {@link IVariant}s in the topic map whose scope property 
   * equals one of those themes at least. 
   * If themes' length == 1, <var>$matchAll</var> is interpreted <var>true</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param array The scope of the {@link IVariant}s to be returned.
   * @param boolean If true the scope property of a name must match all themes, 
   *        if false one theme must be matched at least. If themes' length == 1, 
   *        <var>$matchAll</var> is interpreted true.
   * @return array An array containing {@link IVariant}s.
   * @throws InvalidArgumentException If <var>$themes</var> is an empty array, or if 
   * 				<var>$themes</var> does not exclusively contain {@link Topic}s.
   */
  public function getVariants(array $themes, $matchAll);

  /**
   * Returns the {@link Topic}s in the topic map used in the scope property of 
   * {@link IVariant}s.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @return array An array containing {@link Topic}s.
   */
  public function getVariantThemes();
}
?>