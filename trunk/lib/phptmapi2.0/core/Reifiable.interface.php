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
 * Indicates that a {@link Construct} is reifiable.
 * 
 * Every Topic Maps construct that is not a {@link Topic} is reifiable.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Reifiable.interface.php 88 2011-09-14 12:13:11Z joschmidt $
 */
interface Reifiable extends Construct
{
  /**
   * Returns the reifier of this {@link Construct}.
   * 
   * @return Topic|null The topic that reifies this construct or
   *        <var>null</var> if this construct is not reified.
   */
  public function getReifier();

  /**
   * Sets the reifier of this {@link Construct}.
   * The specified <var>reifier</var> MUST NOT reify another information item.
   *
   * @param Topic The topic that should reify this construct or <var>null</var>
   *        if an existing reifier should be removed. Default <var>null</var>.
   * @return void
   * @throws {@link ModelConstraintException} If the specified <var>reifier</var> 
   *        reifies another construct or if <var>$reifier</var> does not belong to
   *        the parent topic map.
   */
  public function setReifier(Topic $reifier=null);
}
?>