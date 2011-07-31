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
 
require_once('Reifiable.interface.php');
require_once('Typed.interface.php');
require_once('Scoped.interface.php');

/**
 * Represents a topic name item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-topic-name}.
 *
 * Inherited method <var>getParent()</var> from {@link Construct} returns the {@link Topic}
 * to which this name belongs.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Name.interface.php 43 2009-06-28 20:09:57Z joschmidt $
 */
interface Name extends Reifiable, Typed, Scoped
{
  /**
   * Returns the value of this name.
   *
   * @return string
   */
  public function getValue();

  /**
   * Sets the value of this name.
   * The previous value is overridden.
   *
   * @param string The name string to be assigned to the name; must not be <var>null</var>.
   * @return void
   * @throws {@link ModelConstraintException} If the the <var>value</var> is <var>null</var>.
   */
  public function setValue($value);

  /**
   * Returns the {@link IVariant}s defined for this name.
   * The return array may be empty but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link IVariant}s.
   */
  public function getVariants();

  /**
   * Creates an {@link IVariant} of this topic name with the specified
   * <var>value</var>, <var>datatype</var>, and <var>scope</var>. 
   * The newly created {@link IVariant} will have the datatype specified by
   * <var>datatype</var>. 
   * The newly created {@link IVariant} will contain all themes from the parent name 
   * and the themes specified in <var>scope</var>.
   * 
   * @param string A string representation of the value.
   * @param string A URI indicating the datatype of the <var>value</var>. E.g.
   *        http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @param array An array (length >= 1) containing {@link Topic}s, each representing a theme.
   * @return IVariant
   * @throws {@link ModelConstraintException} If the <var>value</var> or <var>datatype</var>
   *        is <var>null</var>, or the scope of the variant would not be a 
   *        true superset of the name's scope.
   */
  public function createVariant($value, $datatype, array $scope);
}
?>