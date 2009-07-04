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

/**
 * Represents an association role item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-assoc-role}.
 * 
 * Inherited method <var>getParent()</var> from {@link Construct} returns the 
 * {@link Association} to which this role belongs.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Role.interface.php 26 2009-03-16 22:33:07Z joschmidt $
 */
interface Role extends Reifiable, Typed {

  /**
   * Returns the topic playing this role.
   *
   * @return Topic
   */
  public function getPlayer();

  /**
   * Sets the role player.
   * Any previous role player will be overridden by <var>player</var>.
   *
   * @param Topic The topic which should play this role.
   * @return void
   */
  public function setPlayer(Topic $player);
}
?>