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
 * @version svn:$Id: Association.interface.php 44 2009-07-05 20:58:46Z joschmidt $
 */
interface Association extends Reifiable, Typed, Scoped {

  /**
   * Returns the {@link Role}s participating in this association.
   * If <var>type</var> is not <var>null</var> all roles with the specified <var>type</var> 
   * are returned.
   * 
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param Topic The type of the {@link Role} instances to be returned. Default <var>null</var>.
   * @return array An array containing a set of {@link Role}s.
   */
  public function getRoles(Topic $type=null);

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
   * @return array An array containing a set of {@link Topic}s representing the role types.
   */
  public function getRoleTypes();
}
?>