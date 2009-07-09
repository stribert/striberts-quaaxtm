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
 * Indicates that a statement (Topic Maps construct) has a scope.
 * 
 * {@link Association}s, {@link Occurrence}s, {@link Name}s, and 
 * {@link IVariant}s are scoped.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Scoped.interface.php 47 2009-07-05 21:01:26Z joschmidt $
 */
interface Scoped extends Construct {

  /**
   * Returns the {@link Topic}s which define the scope.
   * An empty array represents the unconstrained scope.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing a set of {@link Topic}s which define the scope.
   */
  public function getScope();

  /**
   * Adds a {@link Topic} to the scope.
   *
   * @param Topic The topic which should be added to the scope.
   * @return void
   */
  public function addTheme(Topic $theme);

  /**
   * Removes a {@link Topic} from the scope.
   *
   * @param Topic The topic which should be removed from the scope.
   * @return void
   */
  public function removeTheme(Topic $theme);
}
?>