CREATE TABLE `simplecart_product` (
  `product_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`product_id`),
  KEY `active_idx` (`active`)
) ENGINE=InnoDB;

CREATE TABLE `simplecart_order` (
  `order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `customer_info` text,
  `payment_reference` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`order_id`),
  KEY `status_idx` (`status`),
  KEY `timestamp_idx` (`timestamp`)
) ENGINE=InnoDB;

CREATE TABLE `simplecart_orderitem` (
  `orderitem_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` int(10) unsigned NOT NULL DEFAULT '1',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`orderitem_id`),
  KEY `order_idx` (`order_id`)
) ENGINE=InnoDB;
