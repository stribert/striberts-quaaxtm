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

/**
 * Represents a variant item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-variant}.
 * 
 * Inherited method <var>getParent()</var> from {@link Construct} returns the {@link Name}
 * to which this variant belongs.
 * Inherited method <var>getScope()</var> from {@link Scoped} returns the union of its own 
 * scope and the parent's scope.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Variant.interface.php 24 2009-03-16 21:36:58Z joschmidt $
 */
interface IVariant extends DatatypeAware {}
?>