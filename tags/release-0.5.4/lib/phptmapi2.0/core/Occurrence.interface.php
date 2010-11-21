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
 
require_once('DatatypeAware.interface.php');
require_once('Typed.interface.php');

/**
 * Represents an occurrence item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-occurrence}.
 * 
 * Inherited method <var>getParent()</var> from {@link Construct} returns the {@link Topic}
 * to which this occurrence belongs.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Occurrence.interface.php 23 2009-03-16 21:32:52Z joschmidt $
 */
interface Occurrence extends DatatypeAware, Typed {}
?>