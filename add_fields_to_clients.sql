ALTER TABLE `clients` 
ADD `aka` VARCHAR(255) NULL COMMENT 'Also known as', 
ADD `education_level` VARCHAR(255) NULL, 
ADD `id_type` VARCHAR(255) NULL, 
ADD `other_mobile_no` VARCHAR(255) NULL, 
ADD `kra_pin` VARCHAR(255) NULL, 
ADD `postal_code` VARCHAR(255) NULL, 
ADD `type_of_tech` VARCHAR(255) NULL, 
ADD `is_published` TINYINT(1) NOT NULL DEFAULT '0';
