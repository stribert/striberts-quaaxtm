/*
QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as storage engine.

Copyright (C) 2009 Johannes Schmidt <joschmidt@users.sourceforge.net>

This library is free software; you can redistribute it and/or modify it under the terms 
of the GNU Lesser General Public License as published by the Free Software Foundation;
either version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License along with this library; 
if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
CREATE TABLE qtm_topicmap (
	id INT NOT NULL AUTO_INCREMENT,
 	locator VARCHAR(255) NOT NULL,
 	PRIMARY KEY(id),
	UNIQUE KEY `locator` (`locator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_topic (
	id INT NOT NULL AUTO_INCREMENT,
	topicmap_id INT NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topicmap_id) REFERENCES qtm_topicmap(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_subjectidentifier (
	id INT NOT NULL AUTO_INCREMENT,
	topic_id INT NOT NULL,
	locator VARCHAR(255) NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topic_id) REFERENCES qtm_topic(id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX (locator)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_subjectlocator (
	id INT NOT NULL AUTO_INCREMENT,
	topic_id INT NOT NULL,
	locator VARCHAR(255) NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topic_id) REFERENCES qtm_topic(id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX (locator)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_instanceof (
	id INT NOT NULL AUTO_INCREMENT,
	topic_id INT NOT NULL,
	type_id INT NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topic_id) REFERENCES qtm_topic(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (type_id) REFERENCES qtm_topic(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_topicname (
	id INT NOT NULL AUTO_INCREMENT,
	topic_id INT NOT NULL,
	type_id INT NOT NULL,
	value VARCHAR(50) NOT NULL,
	hash VARCHAR(32) NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topic_id) REFERENCES qtm_topic(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (type_id) REFERENCES qtm_topic(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	INDEX (value(50)),
	INDEX (hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE qtm_variant (
	id INT NOT NULL AUTO_INCREMENT,
	topicname_id INT NOT NULL,
	value TINYTEXT NOT NULL,
	datatype VARCHAR(255) NOT NULL,
	hash VARCHAR(32) NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topicname_id) REFERENCES qtm_topicname(id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX (value(50)),
	INDEX (datatype),
	INDEX (hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_occurrence (
	id INT NOT NULL AUTO_INCREMENT,
	topic_id INT NOT NULL,
	type_id INT NOT NULL,
	value TEXT NOT NULL,
	datatype VARCHAR(255) NOT NULL,
	hash VARCHAR(32) NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topic_id) REFERENCES qtm_topic(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (type_id) REFERENCES qtm_topic(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	INDEX (value(100)),
	INDEX (datatype),
	INDEX (hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_association (
	id INT NOT NULL AUTO_INCREMENT,
	type_id INT NOT NULL,
	topicmap_id INT NOT NULL,
	hash VARCHAR(32) NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topicmap_id) REFERENCES qtm_topicmap(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (type_id) REFERENCES qtm_topic(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	INDEX (hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_assocrole (
	id INT NOT NULL AUTO_INCREMENT,
	association_id INT NOT NULL,
	type_id INT NOT NULL,
	player_id INT NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (association_id) REFERENCES qtm_association(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (player_id) REFERENCES qtm_topic(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (type_id) REFERENCES qtm_topic(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_scope (
	id INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_theme (
	id INT NOT NULL AUTO_INCREMENT,
	scope_id INT NOT NULL,
	topic_id INT NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (scope_id) REFERENCES qtm_scope(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (topic_id) REFERENCES qtm_topic(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_topicname_scope (
	id INT NOT NULL AUTO_INCREMENT,	
	scope_id INT NOT NULL,
	topicname_id INT NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (scope_id) REFERENCES qtm_scope(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (topicname_id) REFERENCES qtm_topicname(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_occurrence_scope (
	id INT NOT NULL AUTO_INCREMENT,	
	scope_id INT NOT NULL,
	occurrence_id INT NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (scope_id) REFERENCES qtm_scope(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (occurrence_id) REFERENCES qtm_occurrence(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_association_scope (
	id INT NOT NULL AUTO_INCREMENT,	
	scope_id INT NOT NULL,
	association_id INT NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (scope_id) REFERENCES qtm_scope(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (association_id) REFERENCES qtm_association(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_variant_scope (
	id INT NOT NULL AUTO_INCREMENT,	
	scope_id INT NOT NULL,
	variant_id INT NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (scope_id) REFERENCES qtm_scope(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (variant_id) REFERENCES qtm_variant(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_topicmapconstruct (
	id INT NOT NULL AUTO_INCREMENT,
	association_id INT NULL,
	assocrole_id INT NULL,
	occurrence_id INT NULL,
	topic_id INT NULL,
	topicmap_id INT NULL,
	topicname_id INT NULL,
	variant_id INT NULL,
	reifier_id INT NULL,
	parent_id INT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (association_id) REFERENCES qtm_association(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (assocrole_id) REFERENCES qtm_assocrole(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (occurrence_id) REFERENCES qtm_occurrence(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (topic_id) REFERENCES qtm_topic(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (topicmap_id) REFERENCES qtm_topicmap(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (topicname_id) REFERENCES qtm_topicname(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (variant_id) REFERENCES qtm_variant(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (reifier_id) REFERENCES qtm_topic(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	INDEX (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE qtm_itemidentifier (
	id INT NOT NULL AUTO_INCREMENT,
	topicmapconstruct_id INT NOT NULL,
	locator VARCHAR(255) NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (topicmapconstruct_id) REFERENCES qtm_topicmapconstruct(id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX (locator)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
