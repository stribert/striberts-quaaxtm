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
 
/**
 * Instances of this exception class should be thrown in cases where there 
 * is an error in the underlying topic map processing system or when integrity 
 * constraints are violated.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: PHPTMAPIRuntimeException.class.php 9 2008-11-03 20:55:37Z joschmidt $
 */
class PHPTMAPIRuntimeException extends RuntimeException {}
?>