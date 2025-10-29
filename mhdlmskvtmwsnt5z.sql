-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： mhdlmskvtmwsnt5z.mysql.db
-- 生成日期： 2025-10-29 01:12:06
-- 服务器版本： 8.4.6-6
-- PHP 版本： 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `mhdlmskvtmwsnt5z`
--
CREATE DATABASE IF NOT EXISTS `mhdlmskvtmwsnt5z` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `mhdlmskvtmwsnt5z`;

-- --------------------------------------------------------

--
-- 表的结构 `cpsys_roles`
--

DROP TABLE IF EXISTS `cpsys_roles`;
CREATE TABLE `cpsys_roles` (
  `id` int UNSIGNED NOT NULL,
  `role_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名称 (e.g., Super Admin)',
  `role_description` text COLLATE utf8mb4_unicode_ci COMMENT '角色描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台系统角色表';

--
-- 转存表中的数据 `cpsys_roles`
--

INSERT INTO `cpsys_roles` (`id`, `role_name`, `role_description`, `created_at`) VALUES
(1, '超级管理员', '系统的最高权限拥有者', '2025-10-22 19:43:58'),
(2, '产品经理', 'KDS 系统的核心内容管理者', '2025-10-22 19:43:58'),
(3, '门店经理', '拥有对 KDS 所有业务数据的只读权限', '2025-10-22 19:43:58');

-- --------------------------------------------------------

--
-- 表的结构 `cpsys_users`
--

DROP TABLE IF EXISTS `cpsys_users`;
CREATE TABLE `cpsys_users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '显示名称',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '账户是否激活',
  `role_id` int UNSIGNED NOT NULL COMMENT '外键, 关联 cpsys_roles 表',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除标记时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台系统用户表';

--
-- 转存表中的数据 `cpsys_users`
--

INSERT INTO `cpsys_users` (`id`, `username`, `password_hash`, `email`, `display_name`, `is_active`, `role_id`, `last_login_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'toptea_admin', '9b70757328316b184abb8e7ecffad4e3be9b6ba0bb2fb16890edebc2c50ebd1a', 'admin2@toptea.es', 'Toptea Admin2', 1, 1, '2025-10-28 18:37:42', '2025-10-22 19:43:58', '2025-10-28 18:37:42', NULL),
(2, 'product_manager', '9b70757328316b184abb8e7ecffad4e3be9b6ba0bb2fb16890edebc2c50ebd1a', 'admin@toptea.es', '产品经理 A2', 1, 2, '2025-10-23 13:19:05', '2025-10-23 12:51:19', '2025-10-23 16:24:52', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `expsys_store_stock`
--

DROP TABLE IF EXISTS `expsys_store_stock`;
CREATE TABLE `expsys_store_stock` (
  `id` int UNSIGNED NOT NULL,
  `store_id` int UNSIGNED NOT NULL,
  `material_id` int UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `expsys_store_stock`
--

INSERT INTO `expsys_store_stock` (`id`, `store_id`, `material_id`, `quantity`, `updated_at`) VALUES
(1, 1, 6, 10.00, '2025-10-25 21:19:41');

-- --------------------------------------------------------

--
-- 表的结构 `expsys_warehouse_stock`
--

DROP TABLE IF EXISTS `expsys_warehouse_stock`;
CREATE TABLE `expsys_warehouse_stock` (
  `id` int UNSIGNED NOT NULL,
  `material_id` int UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `expsys_warehouse_stock`
--

INSERT INTO `expsys_warehouse_stock` (`id`, `material_id`, `quantity`, `updated_at`) VALUES
(1, 6, 14.00, '2025-10-25 21:19:41');

-- --------------------------------------------------------

--
-- 表的结构 `kds_cups`
--

DROP TABLE IF EXISTS `kds_cups`;
CREATE TABLE `kds_cups` (
  `id` int UNSIGNED NOT NULL,
  `cup_code` smallint UNSIGNED NOT NULL COMMENT '杯型自定义编号 (1-2位)',
  `cup_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '杯型名称 (e.g., 中杯)',
  `sop_description_zh` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sop_description_es` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除标记时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 杯型管理';

--
-- 转存表中的数据 `kds_cups`
--

INSERT INTO `kds_cups` (`id`, `cup_code`, `cup_name`, `sop_description_zh`, `sop_description_es`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 10, '中杯 (M)', '透明500杯', '500ml paso', '2025-10-22 22:55:39', '2025-10-25 00:07:49', NULL),
(2, 11, '大杯 (L)', NULL, NULL, '2025-10-22 22:55:39', '2025-10-22 22:55:39', NULL),
(3, 12, 'LL-750ml', NULL, NULL, '2025-10-23 01:27:55', '2025-10-23 01:28:33', '2025-10-23 01:28:33'),
(4, 1, '22', NULL, NULL, '2025-10-24 19:33:37', '2025-10-24 19:33:41', '2025-10-24 19:33:41');

-- --------------------------------------------------------

--
-- 表的结构 `kds_ice_options`
--

DROP TABLE IF EXISTS `kds_ice_options`;
CREATE TABLE `kds_ice_options` (
  `id` int UNSIGNED NOT NULL,
  `ice_code` smallint UNSIGNED NOT NULL COMMENT '冰量自定义编号 (1-2位)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除标记时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 冰量选项管理';

--
-- 转存表中的数据 `kds_ice_options`
--

INSERT INTO `kds_ice_options` (`id`, `ice_code`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, '2025-10-23 10:34:25', '2025-10-23 10:34:25', NULL),
(2, 2, '2025-10-23 10:34:48', '2025-10-24 22:11:12', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `kds_ice_option_translations`
--

DROP TABLE IF EXISTS `kds_ice_option_translations`;
CREATE TABLE `kds_ice_option_translations` (
  `id` int UNSIGNED NOT NULL,
  `ice_option_id` int UNSIGNED NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码 (zh-CN, es-ES)',
  `ice_option_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '该语言下的选项名称',
  `sop_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 冰量选项翻译表';

--
-- 转存表中的数据 `kds_ice_option_translations`
--

INSERT INTO `kds_ice_option_translations` (`id`, `ice_option_id`, `language_code`, `ice_option_name`, `sop_description`) VALUES
(1, 1, 'zh-CN', '少冰', '加冰至500ml线'),
(2, 1, 'es-ES', 'LESS ICE 70%', 'HASTA 500ML'),
(3, 2, 'zh-CN', '少少冰', '加冰至500ml线'),
(4, 2, 'es-ES', 'LESS LESS ICE 30%', 'HASTA 500ML');

-- --------------------------------------------------------

--
-- 表的结构 `kds_materials`
--

DROP TABLE IF EXISTS `kds_materials`;
CREATE TABLE `kds_materials` (
  `id` int UNSIGNED NOT NULL,
  `material_code` smallint UNSIGNED NOT NULL COMMENT '物料自定义编号 (1-2位)',
  `material_type` enum('RAW','SEMI_FINISHED','PRODUCT','CONSUMABLE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SEMI_FINISHED',
  `base_unit_id` int UNSIGNED NOT NULL,
  `large_unit_id` int UNSIGNED DEFAULT NULL,
  `conversion_rate` decimal(10,2) DEFAULT NULL,
  `expiry_rule_type` enum('HOURS','DAYS','END_OF_DAY') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_duration` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除标记时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 物料字典主表';

--
-- 转存表中的数据 `kds_materials`
--

INSERT INTO `kds_materials` (`id`, `material_code`, `material_type`, `base_unit_id`, `large_unit_id`, `conversion_rate`, `expiry_rule_type`, `expiry_duration`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'RAW', 1, NULL, NULL, 'HOURS', 4, '2025-10-22 22:55:39', '2025-10-25 21:27:36', NULL),
(2, 2, 'SEMI_FINISHED', 0, NULL, NULL, NULL, NULL, '2025-10-22 22:55:39', '2025-10-22 22:55:39', NULL),
(3, 3, 'SEMI_FINISHED', 0, NULL, NULL, NULL, NULL, '2025-10-22 22:55:39', '2025-10-22 22:55:39', NULL),
(4, 4, 'SEMI_FINISHED', 0, NULL, NULL, NULL, NULL, '2025-10-22 22:55:39', '2025-10-22 22:55:39', NULL),
(5, 6, 'PRODUCT', 4, 5, 10.00, 'DAYS', 4, '2025-10-23 09:51:38', '2025-10-26 00:42:20', NULL),
(6, 5, 'PRODUCT', 4, 5, 12.00, 'DAYS', 5, '2025-10-25 16:29:15', '2025-10-25 21:45:35', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `kds_material_expiries`
--

DROP TABLE IF EXISTS `kds_material_expiries`;
CREATE TABLE `kds_material_expiries` (
  `id` int UNSIGNED NOT NULL,
  `store_id` int UNSIGNED NOT NULL,
  `material_id` int UNSIGNED NOT NULL,
  `batch_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opened_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('ACTIVE','USED','DISCARDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `handler_id` int UNSIGNED DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `kds_material_expiries`
--

INSERT INTO `kds_material_expiries` (`id`, `store_id`, `material_id`, `batch_code`, `opened_at`, `expires_at`, `status`, `handler_id`, `handled_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, '2025-10-25 23:48:45', '2025-10-26 02:48:45', 'ACTIVE', NULL, NULL, NULL, '2025-10-25 21:48:45', '2025-10-25 21:48:45'),
(2, 1, 6, NULL, '2025-10-25 23:48:49', '2025-10-30 23:48:49', 'ACTIVE', NULL, NULL, NULL, '2025-10-25 21:48:49', '2025-10-25 21:48:49'),
(3, 1, 1, NULL, '2025-10-26 00:10:09', '2025-10-26 03:10:09', 'ACTIVE', NULL, NULL, NULL, '2025-10-25 22:10:09', '2025-10-25 22:10:09');

-- --------------------------------------------------------

--
-- 表的结构 `kds_material_translations`
--

DROP TABLE IF EXISTS `kds_material_translations`;
CREATE TABLE `kds_material_translations` (
  `id` int UNSIGNED NOT NULL,
  `material_id` int UNSIGNED NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码 (zh-CN, es-ES)',
  `material_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '物料名称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 物料翻译表';

--
-- 转存表中的数据 `kds_material_translations`
--

INSERT INTO `kds_material_translations` (`id`, `material_id`, `language_code`, `material_name`) VALUES
(1, 1, 'zh-CN', '芒果果肉'),
(2, 1, 'es-ES', 'Pulpa de mango'),
(3, 2, 'zh-CN', '西米'),
(4, 2, 'es-ES', 'Sago'),
(5, 3, 'zh-CN', '椰奶'),
(6, 3, 'es-ES', 'Leche de coco'),
(7, 4, 'zh-CN', '蔗糖'),
(8, 4, 'es-ES', 'Azúcar de caña'),
(9, 5, 'zh-CN', '芒果酱'),
(10, 5, 'es-ES', 'pulpa de mango'),
(11, 6, 'zh-CN', '草莓酱'),
(12, 6, 'es-ES', 'mermelada de fresa');

-- --------------------------------------------------------

--
-- 表的结构 `kds_products`
--

DROP TABLE IF EXISTS `kds_products`;
CREATE TABLE `kds_products` (
  `id` int UNSIGNED NOT NULL,
  `product_sku` int UNSIGNED NOT NULL COMMENT '饮品编码 (3-4位自定义数字)',
  `cup_id` int UNSIGNED NOT NULL,
  `status_id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `product_qr_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '预留给二维码数据',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否上架',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除标记时间',
  `is_deleted_flag` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '用于软删除唯一约束的辅助列'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 产品主表';

--
-- 转存表中的数据 `kds_products`
--

INSERT INTO `kds_products` (`id`, `product_sku`, `cup_id`, `status_id`, `category_id`, `product_qr_code`, `is_active`, `created_at`, `updated_at`, `deleted_at`, `is_deleted_flag`) VALUES
(1, 101, 1, 1, NULL, NULL, 1, '2025-10-22 23:38:17', '2025-10-23 00:40:58', '2025-10-23 00:00:55', 1),
(14, 101, 1, 3, NULL, NULL, 1, '2025-10-23 01:03:30', '2025-10-26 15:17:55', NULL, 0),
(16, 102, 2, 2, NULL, NULL, 1, '2025-10-23 01:10:30', '2025-10-23 01:10:30', NULL, 0);

--
-- 触发器 `kds_products`
--
DROP TRIGGER IF EXISTS `before_product_soft_delete`;
DELIMITER $$
CREATE TRIGGER `before_product_soft_delete` BEFORE UPDATE ON `kds_products` FOR EACH ROW BEGIN
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        SET NEW.is_deleted_flag = OLD.id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- 表的结构 `kds_product_adjustments`
--

DROP TABLE IF EXISTS `kds_product_adjustments`;
CREATE TABLE `kds_product_adjustments` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL COMMENT '关联的产品ID',
  `option_type` enum('sweetness','ice') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '选项类型 (甜度或冰度)',
  `option_id` int UNSIGNED NOT NULL COMMENT '关联的选项ID (来自 kds_sweetness_options 或 kds_ice_options)',
  `material_id` int UNSIGNED NOT NULL COMMENT '需要调整用量的物料ID (如糖浆, 冰块)',
  `quantity` decimal(10,2) NOT NULL COMMENT '该选项下的物料用量',
  `unit_id` int UNSIGNED NOT NULL COMMENT '用量的单位ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 产品动态用量调整表';

-- --------------------------------------------------------

--
-- 表的结构 `kds_product_categories`
--

DROP TABLE IF EXISTS `kds_product_categories`;
CREATE TABLE `kds_product_categories` (
  `id` int UNSIGNED NOT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL COMMENT '父分类ID, NULL表示顶级分类',
  `sort_order` int DEFAULT '0' COMMENT '排序字段',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 产品分类主表';

-- --------------------------------------------------------

--
-- 表的结构 `kds_product_category_translations`
--

DROP TABLE IF EXISTS `kds_product_category_translations`;
CREATE TABLE `kds_product_category_translations` (
  `id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码 (zh-CN, es-ES)',
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类名称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 产品分类翻译表';

-- --------------------------------------------------------

--
-- 表的结构 `kds_product_ice_options`
--

DROP TABLE IF EXISTS `kds_product_ice_options`;
CREATE TABLE `kds_product_ice_options` (
  `product_id` int UNSIGNED NOT NULL,
  `ice_option_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='产品与冰量选项的关联表';

-- --------------------------------------------------------

--
-- 表的结构 `kds_product_recipes`
--

DROP TABLE IF EXISTS `kds_product_recipes`;
CREATE TABLE `kds_product_recipes` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `material_id` int UNSIGNED NOT NULL,
  `unit_id` int UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL COMMENT '数量',
  `step_category` enum('base','mixing','topping') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '步骤分类: 底料, 调杯, 顶料',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '同一分类内的排序',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 产品结构化制作步骤';

--
-- 转存表中的数据 `kds_product_recipes`
--

INSERT INTO `kds_product_recipes` (`id`, `product_id`, `material_id`, `unit_id`, `quantity`, `step_category`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1.00, 'base', 0, '2025-10-22 23:38:17', '2025-10-22 23:38:17'),
(2, 1, 2, 2, 2.00, 'base', 1, '2025-10-22 23:38:17', '2025-10-22 23:38:17'),
(8, 16, 4, 2, 25.00, 'base', 0, '2025-10-23 01:10:30', '2025-10-23 01:10:30'),
(48, 14, 1, 1, 1.00, 'base', 0, '2025-10-26 15:17:55', '2025-10-26 15:17:55'),
(49, 14, 3, 1, 2.00, 'base', 1, '2025-10-26 15:17:55', '2025-10-26 15:17:55'),
(50, 14, 3, 2, 100.00, 'mixing', 0, '2025-10-26 15:17:55', '2025-10-26 15:17:55');

-- --------------------------------------------------------

--
-- 表的结构 `kds_product_statuses`
--

DROP TABLE IF EXISTS `kds_product_statuses`;
CREATE TABLE `kds_product_statuses` (
  `id` int UNSIGNED NOT NULL,
  `status_code` smallint UNSIGNED NOT NULL COMMENT '产品状态自定义编号 (1-2位)',
  `status_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '状态名称 (e.g., 冷饮)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 产品状态管理';

--
-- 转存表中的数据 `kds_product_statuses`
--

INSERT INTO `kds_product_statuses` (`id`, `status_code`, `status_name`, `created_at`, `updated_at`) VALUES
(1, 1, '冷饮', '2025-10-22 22:55:39', '2025-10-22 22:55:39'),
(2, 2, '热饮', '2025-10-22 22:55:39', '2025-10-22 22:55:39'),
(3, 3, '冰沙', '2025-10-22 22:55:39', '2025-10-22 22:55:39');

-- --------------------------------------------------------

--
-- 表的结构 `kds_product_sweetness_options`
--

DROP TABLE IF EXISTS `kds_product_sweetness_options`;
CREATE TABLE `kds_product_sweetness_options` (
  `product_id` int UNSIGNED NOT NULL,
  `sweetness_option_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='产品与甜度选项的关联表';

--
-- 转存表中的数据 `kds_product_sweetness_options`
--

INSERT INTO `kds_product_sweetness_options` (`product_id`, `sweetness_option_id`) VALUES
(14, 1),
(14, 2);

-- --------------------------------------------------------

--
-- 表的结构 `kds_product_translations`
--

DROP TABLE IF EXISTS `kds_product_translations`;
CREATE TABLE `kds_product_translations` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码 (zh-CN, es-ES)',
  `product_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 产品翻译表';

--
-- 转存表中的数据 `kds_product_translations`
--

INSERT INTO `kds_product_translations` (`id`, `product_id`, `language_code`, `product_name`) VALUES
(1, 1, 'zh-CN', '杨枝甘露'),
(2, 1, 'es-ES', 'Mango Sago'),
(5, 14, 'zh-CN', '杨枝甘露2'),
(6, 14, 'es-ES', 'Mango Sago'),
(7, 16, 'zh-CN', '珍珠奶茶'),
(8, 16, 'es-ES', 'naicha');

-- --------------------------------------------------------

--
-- 表的结构 `kds_stores`
--

DROP TABLE IF EXISTS `kds_stores`;
CREATE TABLE `kds_stores` (
  `id` int UNSIGNED NOT NULL,
  `store_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '门店码 (e.g., A1001)',
  `store_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '门店名称',
  `tax_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '门店税号 (NIF/CIF)，用于票据合规',
  `default_vat_rate` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '门店默认增值税率(%)',
  `invoice_number_offset` int UNSIGNED NOT NULL DEFAULT '10000' COMMENT '票号起始偏移量',
  `store_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '所在城市',
  `store_address` text COLLATE utf8mb4_unicode_ci COMMENT '详细地址',
  `store_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系电话',
  `store_cif` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CIF/税号',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `billing_system` enum('TICKETBAI','VERIFACTU') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '该门店使用的票据合规系统',
  `eod_cutoff_hour` int NOT NULL DEFAULT '3'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 门店表';

--
-- 转存表中的数据 `kds_stores`
--

INSERT INTO `kds_stores` (`id`, `store_code`, `store_name`, `tax_id`, `default_vat_rate`, `invoice_number_offset`, `store_city`, `store_address`, `store_phone`, `store_cif`, `is_active`, `created_at`, `updated_at`, `deleted_at`, `billing_system`, `eod_cutoff_hour`) VALUES
(1, 'A1001', '马德里USERA', 'B66666666', 10.00, 6118, 'MADRID', 'XX区XX路XX号', NULL, NULL, 1, '2025-10-23 22:00:09', '2025-10-28 19:01:59', NULL, 'VERIFACTU', 19);

-- --------------------------------------------------------

--
-- 表的结构 `kds_sweetness_options`
--

DROP TABLE IF EXISTS `kds_sweetness_options`;
CREATE TABLE `kds_sweetness_options` (
  `id` int UNSIGNED NOT NULL,
  `sweetness_code` smallint UNSIGNED NOT NULL COMMENT '甜度自定义编号 (1-2位)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除标记时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 甜度选项管理';

--
-- 转存表中的数据 `kds_sweetness_options`
--

INSERT INTO `kds_sweetness_options` (`id`, `sweetness_code`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, '2025-10-23 10:49:35', '2025-10-24 21:46:04', NULL),
(2, 2, '2025-10-23 10:49:53', '2025-10-23 10:49:53', NULL),
(8, 3, '2025-10-24 23:47:18', '2025-10-24 23:47:18', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `kds_sweetness_option_translations`
--

DROP TABLE IF EXISTS `kds_sweetness_option_translations`;
CREATE TABLE `kds_sweetness_option_translations` (
  `id` int UNSIGNED NOT NULL,
  `sweetness_option_id` int UNSIGNED NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sweetness_option_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sop_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 甜度选项翻译表';

--
-- 转存表中的数据 `kds_sweetness_option_translations`
--

INSERT INTO `kds_sweetness_option_translations` (`id`, `sweetness_option_id`, `language_code`, `sweetness_option_name`, `sop_description`) VALUES
(1, 1, 'zh-CN', '少甜', '15克'),
(2, 1, 'es-ES', 'less st', '15g'),
(3, 2, 'zh-CN', '少少甜', NULL),
(4, 2, 'es-ES', 'leless st', NULL),
(5, 8, 'zh-CN', '少甜', '15ml'),
(6, 8, 'es-ES', 'less st', '15ml');

-- --------------------------------------------------------

--
-- 表的结构 `kds_units`
--

DROP TABLE IF EXISTS `kds_units`;
CREATE TABLE `kds_units` (
  `id` int UNSIGNED NOT NULL,
  `unit_code` smallint UNSIGNED NOT NULL COMMENT '单位自定义编号 (1-2位)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除标记时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 单位字典主表';

--
-- 转存表中的数据 `kds_units`
--

INSERT INTO `kds_units` (`id`, `unit_code`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, '2025-10-22 22:55:39', '2025-10-22 22:55:39', NULL),
(2, 2, '2025-10-22 22:55:39', '2025-10-22 22:55:39', NULL),
(3, 3, '2025-10-22 22:55:39', '2025-10-22 22:55:39', NULL),
(4, 4, '2025-10-25 16:27:33', '2025-10-25 16:27:33', NULL),
(5, 5, '2025-10-25 16:28:34', '2025-10-25 16:28:34', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `kds_unit_translations`
--

DROP TABLE IF EXISTS `kds_unit_translations`;
CREATE TABLE `kds_unit_translations` (
  `id` int UNSIGNED NOT NULL,
  `unit_id` int UNSIGNED NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码 (zh-CN, es-ES)',
  `unit_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '单位名称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 单位翻译表';

--
-- 转存表中的数据 `kds_unit_translations`
--

INSERT INTO `kds_unit_translations` (`id`, `unit_id`, `language_code`, `unit_name`) VALUES
(1, 1, 'zh-CN', '克'),
(2, 1, 'es-ES', 'g'),
(3, 2, 'zh-CN', '毫升'),
(4, 2, 'es-ES', 'ml'),
(5, 3, 'zh-CN', '个'),
(6, 3, 'es-ES', 'unidad'),
(7, 4, 'zh-CN', '瓶'),
(8, 4, 'es-ES', 'botella'),
(9, 5, 'zh-CN', '箱'),
(10, 5, 'es-ES', 'Caja');

-- --------------------------------------------------------

--
-- 表的结构 `kds_users`
--

DROP TABLE IF EXISTS `kds_users`;
CREATE TABLE `kds_users` (
  `id` int UNSIGNED NOT NULL,
  `store_id` int UNSIGNED NOT NULL COMMENT '关联的门店ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '显示名称',
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'staff' COMMENT '角色 (e.g., staff, manager)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 用户表';

--
-- 转存表中的数据 `kds_users`
--

INSERT INTO `kds_users` (`id`, `store_id`, `username`, `password_hash`, `display_name`, `role`, `is_active`, `last_login_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'kds_user', '9b70757328316b184abb8e7ecffad4e3be9b6ba0bb2fb16890edebc2c50ebd1a', 'KDS Staff', 'staff', 1, '2025-10-26 01:44:26', '2025-10-23 22:00:09', '2025-10-26 00:44:26', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `pos_categories`
--

DROP TABLE IF EXISTS `pos_categories`;
CREATE TABLE `pos_categories` (
  `id` int NOT NULL,
  `category_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name_zh` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `name_es` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '99',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `pos_categories`
--

INSERT INTO `pos_categories` (`id`, `category_code`, `name_zh`, `name_es`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'bubble_tea', '经典珍珠奶茶', 'Té de burbujas clásico', 50, '2025-10-26 15:17:42', '2025-10-26 15:17:42', NULL),
(2, 'fruit_tea', '果茶', 'Té de frutas', 60, '2025-10-26 15:18:53', '2025-10-26 15:18:53', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `pos_coupons`
--

DROP TABLE IF EXISTS `pos_coupons`;
CREATE TABLE `pos_coupons` (
  `id` int UNSIGNED NOT NULL,
  `promotion_id` int UNSIGNED NOT NULL COMMENT '外键, 关联 pos_promotions.id',
  `coupon_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '优惠码 (大小写不敏感)',
  `coupon_usage_limit` int UNSIGNED NOT NULL DEFAULT '1' COMMENT '每个码可使用的总次数',
  `coupon_usage_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '当前已使用次数',
  `coupon_is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='POS优惠券码表';

-- --------------------------------------------------------

--
-- 表的结构 `pos_eod_reports`
--

DROP TABLE IF EXISTS `pos_eod_reports`;
CREATE TABLE `pos_eod_reports` (
  `id` int UNSIGNED NOT NULL,
  `store_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT '执行日结的用户ID (cpsys_users or kds_users)',
  `report_date` date NOT NULL COMMENT '报告所属日期',
  `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '日结执行时间',
  `transactions_count` int NOT NULL DEFAULT '0',
  `system_gross_sales` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '系统计算-总销售额',
  `system_discounts` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '系统计算-总折扣',
  `system_net_sales` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '系统计算-净销售额',
  `system_tax` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '系统计算-总税额',
  `system_cash` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '系统计算-现金收款',
  `system_card` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '系统计算-刷卡收款',
  `system_platform` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '系统计算-平台收款',
  `counted_cash` decimal(10,2) NOT NULL COMMENT '清点的现金金额',
  `cash_discrepancy` decimal(10,2) NOT NULL COMMENT '现金差异 (counted - system)',
  `notes` text COLLATE utf8mb4_general_ci COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='POS每日日结报告存档表';

--
-- 转存表中的数据 `pos_eod_reports`
--

INSERT INTO `pos_eod_reports` (`id`, `store_id`, `user_id`, `report_date`, `executed_at`, `transactions_count`, `system_gross_sales`, `system_discounts`, `system_net_sales`, `system_tax`, `system_cash`, `system_card`, `system_platform`, `counted_cash`, `cash_discrepancy`, `notes`) VALUES
(2, 1, 1, '2025-10-28', '2025-10-28 20:36:49', 1, 5.00, 0.50, 4.50, 0.41, 4.50, 0.00, 0.00, 5.00, 0.50, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `pos_held_orders`
--

DROP TABLE IF EXISTS `pos_held_orders`;
CREATE TABLE `pos_held_orders` (
  `id` int NOT NULL,
  `store_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `cart_data` json NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用于存储POS端挂起的订单';

--
-- 转存表中的数据 `pos_held_orders`
--

INSERT INTO `pos_held_orders` (`id`, `store_id`, `user_id`, `note`, `cart_data`, `total_amount`, `created_at`) VALUES
(8, 1, 1, '111', '[{\"id\": \"item_1761600286306\", \"ice\": \"50\", \"qty\": 1, \"sugar\": \"50\", \"title\": \"珍珠奶茶\", \"addons\": [], \"remark\": \"\", \"product_id\": 1, \"variant_id\": 1, \"variant_name\": \"中杯\", \"unit_price_eur\": 5}, {\"id\": \"item_1761600515663\", \"ice\": \"50\", \"qty\": 1, \"sugar\": \"50\", \"title\": \"珍珠奶茶\", \"addons\": [], \"remark\": \"\", \"product_id\": 1, \"variant_id\": 1, \"variant_name\": \"中杯\", \"unit_price_eur\": 5}]', 10.00, '2025-10-28 12:02:37');

-- --------------------------------------------------------

--
-- 表的结构 `pos_invoices`
--

DROP TABLE IF EXISTS `pos_invoices`;
CREATE TABLE `pos_invoices` (
  `id` int UNSIGNED NOT NULL,
  `invoice_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内部全局唯一ID (UUID)',
  `store_id` int UNSIGNED NOT NULL COMMENT '外键, 关联 kds_stores.id',
  `user_id` int UNSIGNED NOT NULL COMMENT '外键, 关联 kds_users.id (收银员)',
  `issuer_nif` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(合规/快照) 开票方税号',
  `series` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(合规) 票据系列号',
  `number` bigint UNSIGNED NOT NULL COMMENT '(合规) 票据连续编号',
  `issued_at` timestamp(6) NOT NULL COMMENT '(合规) 票据开具精确时间',
  `invoice_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'F2' COMMENT '(合规) 票据类型, F2=简化发票, R5=简化更正票据等',
  `taxable_base` decimal(10,2) NOT NULL COMMENT '税前基数',
  `vat_amount` decimal(10,2) NOT NULL COMMENT '增值税总额',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '促销折扣总额',
  `final_total` decimal(10,2) NOT NULL COMMENT '最终含税总额',
  `status` enum('ISSUED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ISSUED' COMMENT '状态: ISSUED=已开具, CANCELLED=已作废',
  `cancellation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '作废原因 (用于RF-anulación)',
  `correction_type` enum('S','I') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(合规) 更正类型, S=替换, I=差额',
  `references_invoice_id` int UNSIGNED DEFAULT NULL COMMENT '外键, 指向被作废或被更正的原始票据ID',
  `compliance_system` enum('TICKETBAI','VERIFACTU') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '此票据遵循的合规系统',
  `compliance_data` json NOT NULL COMMENT '存储合规系统所需的所有凭证数据 (哈希, 签名, QR等)',
  `payment_summary` json DEFAULT NULL COMMENT '支付方式快照 (JSON)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='POS统一票据主表 (多系统合规-最终版)';

--
-- 转存表中的数据 `pos_invoices`
--

INSERT INTO `pos_invoices` (`id`, `invoice_uuid`, `store_id`, `user_id`, `issuer_nif`, `series`, `number`, `issued_at`, `invoice_type`, `taxable_base`, `vat_amount`, `discount_amount`, `final_total`, `status`, `cancellation_reason`, `correction_type`, `references_invoice_id`, `compliance_system`, `compliance_data`, `payment_summary`, `created_at`, `updated_at`) VALUES
(27, '5bfdf4cf8a3b78a2edef35c225171f34', 1, 1, 'B66666666', 'A2025', 6119, '2025-10-27 18:39:24.000000', 'F2', 4.09, 0.41, 0.50, 4.50, 'ISSUED', NULL, NULL, NULL, 'VERIFACTU', '{\"hash\": \"e2aca30d6992e10c99a831fc27892afb2f861c514f6c8e9a9d493e69ef530b1c\", \"qr_content\": \"URL:https://www.agenciatributaria.gob.es/verifactu?s=A2025&n=6119&i=2025-10-27 19:39:24.323896&h=e2aca30d\", \"previous_hash\": null, \"system_version\": \"TopTeaPOS v1.0-VERIFACTU\"}', '{\"paid\": 4.5, \"total\": 4.5, \"change\": 0, \"summary\": [{\"amount\": 2, \"method\": \"Cash\"}, {\"amount\": 2.5, \"method\": \"Card\"}]}', '2025-10-27 18:39:24', '2025-10-27 18:39:24'),
(28, '0abd5b1f70efec4e7de31da82c97eab0', 1, 1, 'B66666666', 'A2025', 6120, '2025-10-28 00:30:51.000000', 'F2', 4.09, 0.41, 5.50, 4.50, 'ISSUED', NULL, NULL, NULL, 'VERIFACTU', '{\"hash\": \"51569d1592b46c4304985399bf82c83ffcceb863f35abd5d001a7bf7da06e04b\", \"qr_content\": \"URL:https://www.agenciatributaria.gob.es/verifactu?s=A2025&n=6120&i=2025-10-28 01:30:51.905634&h=51569d15\", \"previous_hash\": \"e2aca30d6992e10c99a831fc27892afb2f861c514f6c8e9a9d493e69ef530b1c\", \"system_version\": \"TopTeaPOS v1.0-VERIFACTU\"}', '{\"paid\": 4.5, \"total\": 4.5, \"change\": 0, \"summary\": [{\"amount\": 2, \"method\": \"Cash\"}, {\"amount\": 2.5, \"method\": \"Card\"}]}', '2025-10-28 00:30:51', '2025-10-28 00:30:51'),
(29, 'd817bc85c1d49b58b932fdf46b023ea4', 1, 1, 'B66666666', 'A2025', 6121, '2025-10-28 18:39:33.000000', 'F2', 4.09, 0.41, 0.50, 4.50, 'ISSUED', NULL, NULL, NULL, 'VERIFACTU', '{\"hash\": \"d91b77d6cfeb7d1643fd4f31f0bcaa65ce6d86bae1c1d9ad4267419b64db5bee\", \"qr_content\": \"URL:https://www.agenciatributaria.gob.es/verifactu?s=A2025&n=6121&i=2025-10-28 19:39:33.588859&h=d91b77d6\", \"previous_hash\": \"51569d1592b46c4304985399bf82c83ffcceb863f35abd5d001a7bf7da06e04b\", \"system_version\": \"TopTeaPOS v1.0-VERIFACTU\"}', '{\"paid\": 4.5, \"total\": 4.5, \"change\": 0, \"summary\": [{\"amount\": 4.5, \"method\": \"Cash\"}]}', '2025-10-28 18:39:33', '2025-10-28 18:39:33');

-- --------------------------------------------------------

--
-- 表的结构 `pos_invoice_items`
--

DROP TABLE IF EXISTS `pos_invoice_items`;
CREATE TABLE `pos_invoice_items` (
  `id` int UNSIGNED NOT NULL,
  `invoice_id` int UNSIGNED NOT NULL COMMENT '外键, 关联 pos_invoices.id',
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(快照) 商品名称',
  `variant_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(快照) 规格名称',
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL COMMENT '(快照) 成交含税单价',
  `unit_taxable_base` decimal(10,2) NOT NULL COMMENT '(合规/快照) 税前单价',
  `vat_rate` decimal(5,2) NOT NULL COMMENT '(合规/快照) 增值税率',
  `vat_amount` decimal(10,2) NOT NULL COMMENT '(合规/快照) 此行项目总增值税额',
  `customizations` json DEFAULT NULL COMMENT '(快照) 个性化选项 (JSON)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='POS票据项目详情表';

--
-- 转存表中的数据 `pos_invoice_items`
--

INSERT INTO `pos_invoice_items` (`id`, `invoice_id`, `item_name`, `variant_name`, `quantity`, `unit_price`, `unit_taxable_base`, `vat_rate`, `vat_amount`, `customizations`) VALUES
(22, 27, '珍珠奶茶', '中杯', 1, 4.50, 4.09, 10.00, 0.41, '{\"ice\": \"50\", \"sugar\": \"50\", \"addons\": [], \"remark\": \"\"}'),
(23, 28, '珍珠奶茶', '中杯', 1, 4.50, 4.09, 10.00, 0.41, '{\"ice\": \"50\", \"sugar\": \"50\", \"addons\": [], \"remark\": \"\"}'),
(24, 28, '珍珠奶茶', '中杯', 1, 0.00, 0.00, 10.00, 0.00, '{\"ice\": \"50\", \"sugar\": \"50\", \"addons\": [], \"remark\": \"\"}'),
(25, 29, '珍珠奶茶', '中杯', 1, 4.50, 4.09, 10.00, 0.41, '{\"ice\": \"100\", \"sugar\": \"0\", \"addons\": [], \"remark\": \"\"}');

-- --------------------------------------------------------

--
-- 表的结构 `pos_item_variants`
--

DROP TABLE IF EXISTS `pos_item_variants`;
CREATE TABLE `pos_item_variants` (
  `id` int UNSIGNED NOT NULL,
  `menu_item_id` int UNSIGNED NOT NULL COMMENT '外键，关联 pos_menu_items.id',
  `product_id` int UNSIGNED NOT NULL COMMENT '外键，关联 kds_products.id (指向生产配方)',
  `variant_name_zh` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '规格名 (中文), 如: 中杯',
  `variant_name_es` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '规格名 (西语), 如: Mediano',
  `price_eur` decimal(10,2) NOT NULL COMMENT '该规格的最终售价',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为默认规格, 1=是, 0=否',
  `sort_order` int NOT NULL DEFAULT '99' COMMENT '规格的排序，越小越靠前',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='POS商品规格、价格与配方关联表';

--
-- 转存表中的数据 `pos_item_variants`
--

INSERT INTO `pos_item_variants` (`id`, `menu_item_id`, `product_id`, `variant_name_zh`, `variant_name_es`, `price_eur`, `is_default`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 16, '中杯', 'mediano', 5.00, 0, 99, '2025-10-26 16:52:56', '2025-10-26 16:52:56', NULL),
(2, 2, 14, '标准', 'Regular', 3.50, 1, 99, '2025-10-26 17:20:34', '2025-10-26 17:20:34', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `pos_members`
--

DROP TABLE IF EXISTS `pos_members`;
CREATE TABLE `pos_members` (
  `id` int UNSIGNED NOT NULL,
  `member_uuid` char(36) COLLATE utf8mb4_general_ci NOT NULL COMMENT '会员全局唯一ID',
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT '手机号 (主要查找依据)',
  `first_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '名字',
  `last_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '姓氏',
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '邮箱',
  `birthdate` date DEFAULT NULL COMMENT '会员生日',
  `points_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '当前积分余额',
  `member_level_id` int UNSIGNED DEFAULT NULL COMMENT '外键, 关联 pos_member_levels.id',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '会员状态 (1=激活, 0=禁用)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除标记'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='POS会员信息表';

--
-- 转存表中的数据 `pos_members`
--

INSERT INTO `pos_members` (`id`, `member_uuid`, `phone_number`, `first_name`, `last_name`, `email`, `birthdate`, `points_balance`, `member_level_id`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '8d6a0f7fa1fbb310d1a4395ba50d09f1', '12345', NULL, NULL, NULL, NULL, 0.00, NULL, 1, '2025-10-28 01:35:59', '2025-10-28 01:35:59', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `pos_member_issued_coupons`
--

DROP TABLE IF EXISTS `pos_member_issued_coupons`;
CREATE TABLE `pos_member_issued_coupons` (
  `id` int UNSIGNED NOT NULL,
  `member_id` int UNSIGNED NOT NULL COMMENT '外键, 关联 pos_members.id',
  `promotion_id` int UNSIGNED NOT NULL COMMENT '外键, 关联 pos_promotions.id (优惠活动定义)',
  `coupon_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '可选的唯一码 (若有)',
  `issued_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发放时间',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间 (NULL表示永不过期)',
  `status` enum('ACTIVE','USED','EXPIRED') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ACTIVE' COMMENT '券状态',
  `used_at` timestamp NULL DEFAULT NULL COMMENT '使用时间',
  `used_invoice_id` int UNSIGNED DEFAULT NULL COMMENT '外键, 关联 pos_invoices.id (在哪个订单使用)',
  `source` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '来源 (e.g., BIRTHDAY, LEVEL_UP, MANUAL)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='POS会员已发放优惠券实例表';

-- --------------------------------------------------------

--
-- 表的结构 `pos_member_levels`
--

DROP TABLE IF EXISTS `pos_member_levels`;
CREATE TABLE `pos_member_levels` (
  `id` int UNSIGNED NOT NULL,
  `level_name_zh` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '等级名称 (中文)',
  `level_name_es` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '等级名称 (西文)',
  `points_threshold` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '达到此等级所需最低积分 (或累计消费)',
  `sort_order` int NOT NULL DEFAULT '10' COMMENT '等级排序 (数字越小越高)',
  `level_up_promo_id` int UNSIGNED DEFAULT NULL COMMENT '外键, 关联 pos_promotions.id (升级时赠送的活动)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='POS会员等级定义表';

-- --------------------------------------------------------

--
-- 表的结构 `pos_member_points_log`
--

DROP TABLE IF EXISTS `pos_member_points_log`;
CREATE TABLE `pos_member_points_log` (
  `id` int UNSIGNED NOT NULL,
  `member_id` int UNSIGNED NOT NULL COMMENT '关联 pos_members.id',
  `invoice_id` int UNSIGNED DEFAULT NULL COMMENT '关联 pos_invoices.id (产生或消耗积分的订单)',
  `points_change` decimal(10,2) NOT NULL COMMENT '积分变动 (+表示获得, -表示消耗)',
  `reason_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '变动原因代码 (e.g., PURCHASE, REDEEM_DISCOUNT, MANUAL_ADJUST, BIRTHDAY)',
  `notes` text COLLATE utf8mb4_general_ci COMMENT '备注 (例如: 兑换XX商品, 管理员调整)',
  `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '流水记录时间',
  `user_id` int UNSIGNED DEFAULT NULL COMMENT '操作人ID (关联 cpsys_users.id 或 kds_users.id)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='POS会员积分流水记录表';

-- --------------------------------------------------------

--
-- 表的结构 `pos_menu_items`
--

DROP TABLE IF EXISTS `pos_menu_items`;
CREATE TABLE `pos_menu_items` (
  `id` int UNSIGNED NOT NULL,
  `pos_category_id` int UNSIGNED NOT NULL COMMENT '外键，关联 pos_categories.id',
  `name_zh` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品销售名 (中文)',
  `name_es` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品销售名 (西语)',
  `description_zh` text COLLATE utf8mb4_unicode_ci COMMENT '商品描述 (中文)',
  `description_es` text COLLATE utf8mb4_unicode_ci COMMENT '商品描述 (西语)',
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品图片URL',
  `sort_order` int NOT NULL DEFAULT '99' COMMENT '在分类中的排序，越小越靠前',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否在POS上架, 1=是, 0=否',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='POS销售商品主表';

--
-- 转存表中的数据 `pos_menu_items`
--

INSERT INTO `pos_menu_items` (`id`, `pos_category_id`, `name_zh`, `name_es`, `description_zh`, `description_es`, `image_url`, `sort_order`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, '珍珠奶茶', 'Té de burbujas', NULL, NULL, NULL, 99, 1, '2025-10-26 16:43:41', '2025-10-26 16:43:41', NULL),
(2, 1, '杨枝甘露2', 'Mango Sago', NULL, NULL, NULL, 99, 1, '2025-10-26 17:20:34', '2025-10-26 17:20:34', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `pos_point_redemption_rules`
--

DROP TABLE IF EXISTS `pos_point_redemption_rules`;
CREATE TABLE `pos_point_redemption_rules` (
  `id` int NOT NULL,
  `rule_name_zh` varchar(100) NOT NULL,
  `rule_name_es` varchar(100) NOT NULL,
  `points_required` int UNSIGNED NOT NULL DEFAULT '0',
  `reward_type` enum('DISCOUNT_AMOUNT','SPECIFIC_PROMOTION') NOT NULL DEFAULT 'DISCOUNT_AMOUNT',
  `reward_value_decimal` decimal(10,2) DEFAULT NULL COMMENT 'Discount amount if reward_type is DISCOUNT_AMOUNT',
  `reward_promo_id` int UNSIGNED DEFAULT NULL COMMENT 'pos_promotions.id if reward_type is SPECIFIC_PROMOTION',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `pos_point_redemption_rules`
--

INSERT INTO `pos_point_redemption_rules` (`id`, `rule_name_zh`, `rule_name_es`, `points_required`, `reward_type`, `reward_value_decimal`, `reward_promo_id`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '2欧优惠券', '2euro menos', 60, 'DISCOUNT_AMOUNT', 2.00, NULL, 1, '2025-10-28 20:14:45', '2025-10-28 20:14:45', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `pos_promotions`
--

DROP TABLE IF EXISTS `pos_promotions`;
CREATE TABLE `pos_promotions` (
  `id` int UNSIGNED NOT NULL,
  `promo_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '规则名称, e.g., 珍珠奶茶买一送一',
  `promo_priority` int NOT NULL DEFAULT '10' COMMENT '优先级, 数字越小越高',
  `promo_exclusive` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否排他, 1=是 (若应用此规则,则不再计算其他规则)',
  `promo_trigger_type` enum('AUTO_APPLY','COUPON_CODE') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '触发类型: AUTO_APPLY=自动应用, COUPON_CODE=需优惠码',
  `promo_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_conditions` json NOT NULL COMMENT '触发条件 (JSON)',
  `promo_actions` json NOT NULL COMMENT '执行动作 (JSON)',
  `promo_start_date` timestamp NULL DEFAULT NULL COMMENT '活动开始时间',
  `promo_end_date` timestamp NULL DEFAULT NULL COMMENT '活动结束时间',
  `promo_is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='POS促销规则表';

--
-- 转存表中的数据 `pos_promotions`
--

INSERT INTO `pos_promotions` (`id`, `promo_name`, `promo_priority`, `promo_exclusive`, `promo_trigger_type`, `promo_code`, `promo_conditions`, `promo_actions`, `promo_start_date`, `promo_end_date`, `promo_is_active`, `created_at`, `updated_at`) VALUES
(1, '珍珠奶茶买一送一', 10, 0, 'AUTO_APPLY', NULL, '[{\"type\": \"ITEM_QUANTITY\", \"item_ids\": [\"1\"], \"min_quantity\": \"2\"}]', '[{\"rule\": \"lowest_price\", \"type\": \"SET_PRICE_ZERO\", \"quantity\": \"1\", \"target_item_ids\": [\"1\"]}]', NULL, NULL, 1, '2025-10-27 01:44:02', '2025-10-27 01:44:02'),
(2, '奶茶九折优惠', 20, 0, 'AUTO_APPLY', NULL, '[]', '[{\"type\": \"PERCENTAGE_DISCOUNT\", \"item_ids\": [2, 1], \"percentage\": 10}]', NULL, NULL, 1, '2025-10-27 11:08:05', '2025-10-27 13:17:39'),
(3, '测试优惠码', 10, 0, 'COUPON_CODE', 'QQQ', '[]', '[{\"type\": \"PERCENTAGE_DISCOUNT\", \"item_ids\": [2, 1], \"percentage\": 20}]', '2025-10-27 11:45:00', '2025-10-27 02:50:00', 1, '2025-10-27 11:46:45', '2025-10-27 15:29:27');

-- --------------------------------------------------------

--
-- 表的结构 `pos_settings`
--

DROP TABLE IF EXISTS `pos_settings`;
CREATE TABLE `pos_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `pos_settings`
--

INSERT INTO `pos_settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES
('points_euros_per_point', '2.00', '每赚取1积分需要消费的欧元金额', '2025-10-28 20:05:34');

--
-- 转储表的索引
--

--
-- 表的索引 `cpsys_roles`
--
ALTER TABLE `cpsys_roles`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `cpsys_users`
--
ALTER TABLE `cpsys_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_cpsys_users_email` (`email`);

--
-- 表的索引 `expsys_store_stock`
--
ALTER TABLE `expsys_store_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `store_material` (`store_id`,`material_id`);

--
-- 表的索引 `expsys_warehouse_stock`
--
ALTER TABLE `expsys_warehouse_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `material_id` (`material_id`);

--
-- 表的索引 `kds_cups`
--
ALTER TABLE `kds_cups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cup_code` (`cup_code`);

--
-- 表的索引 `kds_ice_options`
--
ALTER TABLE `kds_ice_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ice_code` (`ice_code`);

--
-- 表的索引 `kds_ice_option_translations`
--
ALTER TABLE `kds_ice_option_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ice_option_language` (`ice_option_id`,`language_code`);

--
-- 表的索引 `kds_materials`
--
ALTER TABLE `kds_materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `material_code` (`material_code`);

--
-- 表的索引 `kds_material_expiries`
--
ALTER TABLE `kds_material_expiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `store_id` (`store_id`),
  ADD KEY `status` (`status`),
  ADD KEY `expires_at` (`expires_at`);

--
-- 表的索引 `kds_material_translations`
--
ALTER TABLE `kds_material_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `material_language_unique` (`material_id`,`language_code`);

--
-- 表的索引 `kds_products`
--
ALTER TABLE `kds_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_active_sku` (`product_sku`,`is_deleted_flag`),
  ADD KEY `cup_id` (`cup_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `category_id` (`category_id`);

--
-- 表的索引 `kds_product_adjustments`
--
ALTER TABLE `kds_product_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_product_option` (`product_id`,`option_type`,`option_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- 表的索引 `kds_product_categories`
--
ALTER TABLE `kds_product_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- 表的索引 `kds_product_category_translations`
--
ALTER TABLE `kds_product_category_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_language_unique` (`category_id`,`language_code`);

--
-- 表的索引 `kds_product_ice_options`
--
ALTER TABLE `kds_product_ice_options`
  ADD PRIMARY KEY (`product_id`,`ice_option_id`),
  ADD KEY `ice_option_id` (`ice_option_id`);

--
-- 表的索引 `kds_product_recipes`
--
ALTER TABLE `kds_product_recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- 表的索引 `kds_product_statuses`
--
ALTER TABLE `kds_product_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status_code` (`status_code`);

--
-- 表的索引 `kds_product_sweetness_options`
--
ALTER TABLE `kds_product_sweetness_options`
  ADD PRIMARY KEY (`product_id`,`sweetness_option_id`),
  ADD KEY `sweetness_option_id` (`sweetness_option_id`);

--
-- 表的索引 `kds_product_translations`
--
ALTER TABLE `kds_product_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_language_unique` (`product_id`,`language_code`);

--
-- 表的索引 `kds_stores`
--
ALTER TABLE `kds_stores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `store_code` (`store_code`);

--
-- 表的索引 `kds_sweetness_options`
--
ALTER TABLE `kds_sweetness_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sweetness_code` (`sweetness_code`);

--
-- 表的索引 `kds_sweetness_option_translations`
--
ALTER TABLE `kds_sweetness_option_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sweetness_option_language` (`sweetness_option_id`,`language_code`);

--
-- 表的索引 `kds_units`
--
ALTER TABLE `kds_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unit_code` (`unit_code`);

--
-- 表的索引 `kds_unit_translations`
--
ALTER TABLE `kds_unit_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unit_language_unique` (`unit_id`,`language_code`);

--
-- 表的索引 `kds_users`
--
ALTER TABLE `kds_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_store_username` (`store_id`,`username`,`deleted_at`);

--
-- 表的索引 `pos_categories`
--
ALTER TABLE `pos_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`);

--
-- 表的索引 `pos_coupons`
--
ALTER TABLE `pos_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_coupon_code` (`coupon_code`),
  ADD KEY `idx_promotion_id` (`promotion_id`);

--
-- 表的索引 `pos_eod_reports`
--
ALTER TABLE `pos_eod_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `store_date_unique` (`store_id`,`report_date`);

--
-- 表的索引 `pos_held_orders`
--
ALTER TABLE `pos_held_orders`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `pos_invoices`
--
ALTER TABLE `pos_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_issuer_series_number` (`issuer_nif`,`series`,`number`,`compliance_system`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_issued_at` (`issued_at`),
  ADD KEY `idx_references_invoice_id` (`references_invoice_id`);

--
-- 表的索引 `pos_invoice_items`
--
ALTER TABLE `pos_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_id` (`invoice_id`);

--
-- 表的索引 `pos_item_variants`
--
ALTER TABLE `pos_item_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menu_item_id` (`menu_item_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- 表的索引 `pos_members`
--
ALTER TABLE `pos_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_uuid_unique` (`member_uuid`),
  ADD UNIQUE KEY `phone_number_unique` (`phone_number`),
  ADD KEY `idx_phone_number` (`phone_number`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_birthdate` (`birthdate`),
  ADD KEY `idx_member_level_id` (`member_level_id`);

--
-- 表的索引 `pos_member_issued_coupons`
--
ALTER TABLE `pos_member_issued_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupon_code_unique` (`coupon_code`),
  ADD KEY `idx_member_id_status_expires` (`member_id`,`status`,`expires_at`),
  ADD KEY `idx_promotion_id` (`promotion_id`),
  ADD KEY `idx_used_invoice_id` (`used_invoice_id`);

--
-- 表的索引 `pos_member_levels`
--
ALTER TABLE `pos_member_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_points_threshold` (`points_threshold`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- 表的索引 `pos_member_points_log`
--
ALTER TABLE `pos_member_points_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_member_id` (`member_id`),
  ADD KEY `idx_invoice_id` (`invoice_id`);

--
-- 表的索引 `pos_menu_items`
--
ALTER TABLE `pos_menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pos_category_id` (`pos_category_id`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- 表的索引 `pos_point_redemption_rules`
--
ALTER TABLE `pos_point_redemption_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_points_required` (`points_required`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `reward_promo_id` (`reward_promo_id`);

--
-- 表的索引 `pos_promotions`
--
ALTER TABLE `pos_promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promo_code_unique` (`promo_code`),
  ADD KEY `idx_promo_active_dates` (`promo_is_active`,`promo_start_date`,`promo_end_date`);

--
-- 表的索引 `pos_settings`
--
ALTER TABLE `pos_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `cpsys_roles`
--
ALTER TABLE `cpsys_roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `cpsys_users`
--
ALTER TABLE `cpsys_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- 使用表AUTO_INCREMENT `expsys_store_stock`
--
ALTER TABLE `expsys_store_stock`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `expsys_warehouse_stock`
--
ALTER TABLE `expsys_warehouse_stock`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `kds_cups`
--
ALTER TABLE `kds_cups`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `kds_ice_options`
--
ALTER TABLE `kds_ice_options`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `kds_ice_option_translations`
--
ALTER TABLE `kds_ice_option_translations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `kds_materials`
--
ALTER TABLE `kds_materials`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `kds_material_expiries`
--
ALTER TABLE `kds_material_expiries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `kds_material_translations`
--
ALTER TABLE `kds_material_translations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用表AUTO_INCREMENT `kds_products`
--
ALTER TABLE `kds_products`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- 使用表AUTO_INCREMENT `kds_product_adjustments`
--
ALTER TABLE `kds_product_adjustments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `kds_product_categories`
--
ALTER TABLE `kds_product_categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `kds_product_category_translations`
--
ALTER TABLE `kds_product_category_translations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `kds_product_recipes`
--
ALTER TABLE `kds_product_recipes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- 使用表AUTO_INCREMENT `kds_product_statuses`
--
ALTER TABLE `kds_product_statuses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `kds_product_translations`
--
ALTER TABLE `kds_product_translations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用表AUTO_INCREMENT `kds_stores`
--
ALTER TABLE `kds_stores`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `kds_sweetness_options`
--
ALTER TABLE `kds_sweetness_options`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用表AUTO_INCREMENT `kds_sweetness_option_translations`
--
ALTER TABLE `kds_sweetness_option_translations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用表AUTO_INCREMENT `kds_units`
--
ALTER TABLE `kds_units`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `kds_unit_translations`
--
ALTER TABLE `kds_unit_translations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用表AUTO_INCREMENT `kds_users`
--
ALTER TABLE `kds_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `pos_categories`
--
ALTER TABLE `pos_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `pos_coupons`
--
ALTER TABLE `pos_coupons`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `pos_eod_reports`
--
ALTER TABLE `pos_eod_reports`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `pos_held_orders`
--
ALTER TABLE `pos_held_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用表AUTO_INCREMENT `pos_invoices`
--
ALTER TABLE `pos_invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- 使用表AUTO_INCREMENT `pos_invoice_items`
--
ALTER TABLE `pos_invoice_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- 使用表AUTO_INCREMENT `pos_item_variants`
--
ALTER TABLE `pos_item_variants`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `pos_members`
--
ALTER TABLE `pos_members`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `pos_member_issued_coupons`
--
ALTER TABLE `pos_member_issued_coupons`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `pos_member_levels`
--
ALTER TABLE `pos_member_levels`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `pos_member_points_log`
--
ALTER TABLE `pos_member_points_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `pos_menu_items`
--
ALTER TABLE `pos_menu_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `pos_point_redemption_rules`
--
ALTER TABLE `pos_point_redemption_rules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `pos_promotions`
--
ALTER TABLE `pos_promotions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 限制导出的表
--

--
-- 限制表 `cpsys_users`
--
ALTER TABLE `cpsys_users`
  ADD CONSTRAINT `cpsys_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `cpsys_roles` (`id`) ON DELETE RESTRICT;

--
-- 限制表 `kds_ice_option_translations`
--
ALTER TABLE `kds_ice_option_translations`
  ADD CONSTRAINT `kds_ice_option_translations_ibfk_1` FOREIGN KEY (`ice_option_id`) REFERENCES `kds_ice_options` (`id`) ON DELETE CASCADE;

--
-- 限制表 `kds_material_translations`
--
ALTER TABLE `kds_material_translations`
  ADD CONSTRAINT `kds_material_translations_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `kds_materials` (`id`) ON DELETE CASCADE;

--
-- 限制表 `kds_products`
--
ALTER TABLE `kds_products`
  ADD CONSTRAINT `kds_products_ibfk_1` FOREIGN KEY (`cup_id`) REFERENCES `kds_cups` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `kds_products_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `kds_product_statuses` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `kds_products_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `kds_product_categories` (`id`) ON DELETE SET NULL;

--
-- 限制表 `kds_product_adjustments`
--
ALTER TABLE `kds_product_adjustments`
  ADD CONSTRAINT `kds_product_adjustments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `kds_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kds_product_adjustments_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `kds_materials` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `kds_product_adjustments_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `kds_units` (`id`) ON DELETE RESTRICT;

--
-- 限制表 `kds_product_categories`
--
ALTER TABLE `kds_product_categories`
  ADD CONSTRAINT `kds_product_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `kds_product_categories` (`id`) ON DELETE SET NULL;

--
-- 限制表 `kds_product_category_translations`
--
ALTER TABLE `kds_product_category_translations`
  ADD CONSTRAINT `kds_product_category_translations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `kds_product_categories` (`id`) ON DELETE CASCADE;

--
-- 限制表 `kds_product_ice_options`
--
ALTER TABLE `kds_product_ice_options`
  ADD CONSTRAINT `kds_product_ice_options_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `kds_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kds_product_ice_options_ibfk_2` FOREIGN KEY (`ice_option_id`) REFERENCES `kds_ice_options` (`id`) ON DELETE CASCADE;

--
-- 限制表 `kds_product_recipes`
--
ALTER TABLE `kds_product_recipes`
  ADD CONSTRAINT `kds_product_recipes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `kds_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kds_product_recipes_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `kds_materials` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `kds_product_recipes_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `kds_units` (`id`) ON DELETE RESTRICT;

--
-- 限制表 `kds_product_sweetness_options`
--
ALTER TABLE `kds_product_sweetness_options`
  ADD CONSTRAINT `kds_product_sweetness_options_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `kds_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kds_product_sweetness_options_ibfk_2` FOREIGN KEY (`sweetness_option_id`) REFERENCES `kds_sweetness_options` (`id`) ON DELETE CASCADE;

--
-- 限制表 `kds_product_translations`
--
ALTER TABLE `kds_product_translations`
  ADD CONSTRAINT `kds_product_translations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `kds_products` (`id`) ON DELETE CASCADE;

--
-- 限制表 `kds_sweetness_option_translations`
--
ALTER TABLE `kds_sweetness_option_translations`
  ADD CONSTRAINT `kds_sweetness_option_translations_ibfk_1` FOREIGN KEY (`sweetness_option_id`) REFERENCES `kds_sweetness_options` (`id`) ON DELETE CASCADE;

--
-- 限制表 `kds_unit_translations`
--
ALTER TABLE `kds_unit_translations`
  ADD CONSTRAINT `kds_unit_translations_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `kds_units` (`id`) ON DELETE CASCADE;

--
-- 限制表 `kds_users`
--
ALTER TABLE `kds_users`
  ADD CONSTRAINT `kds_users_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `kds_stores` (`id`) ON DELETE CASCADE;

--
-- 限制表 `pos_members`
--
ALTER TABLE `pos_members`
  ADD CONSTRAINT `fk_member_level` FOREIGN KEY (`member_level_id`) REFERENCES `pos_member_levels` (`id`) ON DELETE SET NULL;

--
-- 限制表 `pos_member_issued_coupons`
--
ALTER TABLE `pos_member_issued_coupons`
  ADD CONSTRAINT `fk_issued_coupon_member` FOREIGN KEY (`member_id`) REFERENCES `pos_members` (`id`) ON DELETE CASCADE;

--
-- 限制表 `pos_member_points_log`
--
ALTER TABLE `pos_member_points_log`
  ADD CONSTRAINT `fk_member_points_member` FOREIGN KEY (`member_id`) REFERENCES `pos_members` (`id`) ON DELETE CASCADE;

--
-- 限制表 `pos_point_redemption_rules`
--
ALTER TABLE `pos_point_redemption_rules`
  ADD CONSTRAINT `pos_point_redemption_rules_ibfk_1` FOREIGN KEY (`reward_promo_id`) REFERENCES `pos_promotions` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
