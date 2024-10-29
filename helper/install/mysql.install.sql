/**
 * AwoCoupon
 *
 * @package WordPress AwoCoupon
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 **/

CREATE TABLE IF NOT EXISTS #__awocoupon (
	`id` int(16) NOT NULL auto_increment,
	`estore` VARCHAR(255) NOT NULL,
	`coupon_code` varchar(255) BINARY NOT NULL default '',
	`passcode` varchar(10),
	`upc` varchar(255),
	`coupon_value_type` enum('percent','amount','amount_per') DEFAULT NULL,
	`coupon_value` decimal(20,5),
	`coupon_value_def` TEXT,
	`function_type` VARCHAR(255) NOT NULL DEFAULT 'coupon',
	`num_of_uses_total` INT,
	`num_of_uses_customer` INT,
	`min_value` decimal(20,5),
	`discount_type` enum('specific','overall'),
	`startdate` DATETIME,
	`expiration` DATETIME,
	`order_id` int(11),
	`template_id` int(11),
	`state` ENUM('published', 'unpublished', 'template') NOT NULL DEFAULT 'published',
	`note` TEXT,
	`params` TEXT,
	PRIMARY KEY  (`id`),
	KEY coupon_code (coupon_code)
);

CREATE TABLE `#__awocoupon_asset` (
	`id` int(16) NOT NULL AUTO_INCREMENT,
	`coupon_id` varchar(32) NOT NULL DEFAULT '',
	`asset_key` INT NOT NULL DEFAULT 0,
	`asset_type` enum('user','usergroup','coupon','product','category','manufacturer','vendor','shipping','country','countrystate','paymentmethod') NOT NULL,
	`asset_id` varchar(255) not null,
	`qty` int(11) DEFAULT NULL,
	`order_by` int(11) DEFAULT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS #__awocoupon_tag (
	`coupon_id` int(16) NOT NULL,
	`tag` VARCHAR(255) NOT NULL,
	PRIMARY KEY  (`coupon_id`,`tag`)
);


CREATE TABLE IF NOT EXISTS #__awocoupon_config (
	`id` int(16) NOT NULL auto_increment,
	`name` VARCHAR(255) NOT NULL,
	`is_json` TINYINT(1),
	`value` TEXT,
	PRIMARY KEY  (`id`),
	UNIQUE (`name`)
);
INSERT INTO #__awocoupon_config (name,value) VALUES ('enable_store_coupon', 1);


CREATE TABLE IF NOT EXISTS #__awocoupon_history (
	`id` INT NOT NULL auto_increment,
	`estore` VARCHAR(255) NOT NULL,
	`coupon_id` varchar(32) NOT NULL default '',
	`coupon_entered_id` varchar(32),
	`user_id` INT NOT NULL,
	`user_email` varchar(255),
	`coupon_discount` DECIMAL(20,5) DEFAULT 0 NOT NULL,
	`shipping_discount` DECIMAL(20,5) DEFAULT 0 NOT NULL,
	`order_id` INT,
	`session_id` VARCHAR(200),
	`productids` TEXT,
	`details` TEXT,
	`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY  (`id`),
	KEY coupon_id_user_id (coupon_id,user_email),
	KEY coupon_entered_id_user_id (coupon_entered_id,user_email),
	KEY order_id (order_id),
	KEY user_id (user_id),
	KEY session_id (session_id),
	KEY user_email (user_email)
);


CREATE TABLE IF NOT EXISTS #__awocoupon_auto (
	`id` int(16) NOT NULL auto_increment,
	`coupon_id` INT NOT NULL,
	`ordering` INT NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__awocoupon_lang_text (
	`id` int(16) NOT NULL auto_increment,
	`elem_id` int(16) NOT NULL,
	`lang` varchar(32) NOT NULL default '',
	`text` TEXT,
	PRIMARY KEY  (`id`),
	UNIQUE KEY (elem_id,lang)
);


CREATE TABLE IF NOT EXISTS #__awocoupon_cron (
	`id` INT NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`user_id` INT NOT NULL,
	`type` varchar(255),
	`status` VARCHAR(200),
	`notes` TEXT,
	`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY  (`id`)
);








			


