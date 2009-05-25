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
 * Represents an association item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-association}.
 * 
 * Inherited method <var>getParent()</var> from {@link Construct} returns the {@link TopicMap}
 * to which this association belongs.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Association.interface.php 24 2009-03-16 21:36:58Z joschmidt $
 */
interface Association extends Reifiable, Typed, Scoped {

  /**
   * Returns the roles participating in this association.
   * The return value must never be <var>null</var>.
   * 
   * @return array An array containing {@link Role}s.
   */
  public function getRoles();

  /**
   * Returns all roles with the specified <var>type</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link Role} instances to be returned.
   * @return array An array (maybe empty) containing {@link Role}s with the specified
   *        <var>type</var> property.
   */
  public function getRolesByType(Topic $type);

  /**
   * Creates a new {@link Role} representing a role in this association. 
   * 
   * @param Topic The role type.
   * @param Topic The role player.
   * @return Role A newly created association role.
   */
  public function createRole(Topic $type, Topic $player);

  /**
   * Returns the role types participating in this association.
   * The return value may be an empty array but must never be <var>null</var>.
   *
   * @return array An array containing {@link Topic}s representing the role types.
   */
  public function getRoleTypes();
}
?>