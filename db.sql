CREATE TABLE IF NOT EXISTS `agencies` (
		  `agcid` bigint(16) NOT NULL,
		  `agcname` varchar(255) DEFAULT NULL,
		  `mcpzdn` tinyint(1) DEFAULT NULL,
		  `bdtsmt` tinyint(1) DEFAULT NULL,
		  `fhd` tinyint(1) DEFAULT NULL,
		  `clvsrdst` tinyint(1) DEFAULT NULL,
		  PRIMARY KEY (`agcid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `agencies` (`agcid`, `agcname`, `mcpzdn`, `bdtsmt`, `fhd`, `clvsrdst`) VALUES
(1, 'Тестовое учреждение', 0, 0, 0, 0),
(6951, 'муниципальное казённое дошкольное образовательное учреждение "Детский сад комбинированного вида № 56 "Лесная сказка"', 1, 1, 0, 0),
(12813, 'муниципальное казённое дошкольное образовательное учреждение "Детский сад комбинированного вида № 44 "Светлячок"', 1, 1, 0, 0),
(24296, 'муниципальное бюджетное учреждение физической культуры и спорта "Исток"', 1, 0, 1, 1);


CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL,
  `agcid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `text` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(20) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;