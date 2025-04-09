-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 09-04-2025 a las 09:29:23
-- Versión del servidor: 8.0.41
-- Versión de PHP: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mess_control_vehicular`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cambio_llantas`
--

CREATE TABLE `cambio_llantas` (
  `id_servicio` int NOT NULL,
  `id_coche` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha` date NOT NULL,
  `cant_llantas` int NOT NULL,
  `kilometraje_actual` int NOT NULL,
  `kilometraje_ult_cambio` int NOT NULL,
  `medida_llanta` int NOT NULL,
  `estatus` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist`
--

CREATE TABLE `checklist` (
  `id_checklist` int NOT NULL,
  `id_coche` int NOT NULL,
  `fecha` date NOT NULL,
  `id_usuario` int NOT NULL,
  `id_revisor` int NOT NULL,
  `motivo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_asientos`
--

CREATE TABLE `checklist_asientos` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_documentacion`
--

CREATE TABLE `checklist_documentacion` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `id_doc` int NOT NULL,
  `t_documento` varchar(20) NOT NULL,
  `entregado` varchar(20) NOT NULL,
  `vencimiento` date NOT NULL,
  `no_tarjeta` int NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` varchar(900) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_espejos_ventanas`
--

CREATE TABLE `checklist_espejos_ventanas` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_estereos_aire`
--

CREATE TABLE `checklist_estereos_aire` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `cd_estereo` varchar(100) NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_faros`
--

CREATE TABLE `checklist_faros` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_golpes_exterior`
--

CREATE TABLE `checklist_golpes_exterior` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_limpiaParabrisas`
--

CREATE TABLE `checklist_limpiaParabrisas` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_limpieza`
--

CREATE TABLE `checklist_limpieza` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_llantas`
--

CREATE TABLE `checklist_llantas` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `no_rin` int NOT NULL,
  `medidas` int NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_placas`
--

CREATE TABLE `checklist_placas` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_puertas_llave`
--

CREATE TABLE `checklist_puertas_llave` (
  `id` int NOT NULL,
  `id_checklist` int NOT NULL,
  `si_no` varchar(2) NOT NULL,
  `duplicado_llaves` varchar(10) NOT NULL,
  `observaciones` varchar(100) NOT NULL,
  `foto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentacion`
--

CREATE TABLE `documentacion` (
  `id` int NOT NULL,
  `id_coche` int NOT NULL,
  `tarjeta_circulacion` int NOT NULL,
  `refrendo_actual` int NOT NULL,
  `seguro_auto` int NOT NULL,
  `verificacion_vigente` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos`
--

CREATE TABLE `fotos` (
  `id_foto` int NOT NULL,
  `tipo_foto` varchar(20) NOT NULL,
  `id_formato` int NOT NULL,
  `id_coche` int NOT NULL,
  `fecha` date NOT NULL,
  `imagen` varchar(900) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hojalateria`
--

CREATE TABLE `hojalateria` (
  `id_servicio` int NOT NULL,
  `id_coche` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha_solicitud` date NOT NULL,
  `estatus` varchar(100) NOT NULL,
  `kilometraje_inicio` int NOT NULL,
  `kilometraje_fin` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id` int NOT NULL,
  `id_coche` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `usuario` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci NOT NULL,
  `area` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci NOT NULL,
  `placa` varchar(9) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci DEFAULT NULL,
  `modelo` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci DEFAULT NULL,
  `tipo` varchar(50) NOT NULL,
  `color` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci DEFAULT NULL,
  `anio` int DEFAULT NULL,
  `foto_general` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci DEFAULT NULL,
  `estatus` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci NOT NULL DEFAULT 'Activo',
  `fecha_registro` date NOT NULL,
  `km_mantenimiento` int NOT NULL,
  `referencia` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id`, `id_coche`, `id_usuario`, `usuario`, `area`, `placa`, `modelo`, `tipo`, `color`, `anio`, `foto_general`, `estatus`, `fecha_registro`, `km_mantenimiento`, `referencia`) VALUES
(1, 0, 73, 'Abelardo Luna', 'Norte', 'ULF974J', 'Virtus ', '', 'Gris Oscuro', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(2, 0, 0, 'Ivan Rosales', 'Norte', 'ULV992J', 'Virtus', '', 'Azul', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(3, 0, 289, 'Hector Paz', 'Norte', 'UNF755E', 'Vento       ', '', 'Plata', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(4, 0, 368, 'Jose Reynoso', 'Norte', 'UMY322F', 'Vento ', '', 'Plata', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(5, 0, 350, 'Jesus Sanchez', 'SFG', 'UMZ104E', 'Vento ', '', 'Plata', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(6, 0, 415, 'Jesus Antonio Ruiz', 'Norte', 'UNB562D', 'Vento ', '', 'Plata', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(7, 0, 193, 'Rolando Carreon', 'Norte', 'UNF458E', 'Vento ', '', 'Plata', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(8, 0, 30, 'Jorge Mancilla', 'Norte', 'SV9452D', 'Caddy Van ', '', 'Blanco', 2017, NULL, 'Activo ', '2025-04-07', 0, ''),
(9, 0, 30, 'Jorge Mancilla', 'Norte', 'UNF459E', 'Jetta ', '', 'Blanco Mate', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(10, 0, 404, 'Christian Recendiz', 'Ventas Norte', 'RSX532A', 'Gol', '', 'Plata', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(11, 0, 0, 'COMODIN', 'Ventas Norte', 'RSX526A', 'Gol', '', 'Plata', 2016, NULL, 'Activo ', '2025-04-07', 0, ''),
(12, 0, 0, 'FIXTURES', 'Ventas Norte', 'UMG587H', 'Vento ', '', 'Plata', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(13, 0, 0, 'ESTAQUITAS', 'Norte', 'PH5707A', '0', '', 'Blanco', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(14, 0, 292, 'Martin Becerra', 'Ventas', 'UNF765E', 'Gol ', '', 'Plata', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(15, 0, 292, 'Martin Becerra', 'Ventas', 'UNF451E', 'Gol ', '', 'Plata', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(16, 0, 151, 'Jose Lara', 'Ventas', 'UPT177J', 'Virtus ', '', 'Plata', 2024, NULL, 'Activo ', '2025-04-07', 0, ''),
(17, 0, 292, 'Martin Becerra', 'Ventas', 'UNG890H', 'Virtus ', '', 'Blanco', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(18, 0, 292, 'Martin Becerra', 'Ventas', 'UNF452E', 'Gol ', '', 'Plata', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(19, 0, 319, 'Florangeli de Jesus Vazquez', 'Ventas', 'ULF753G', 'Virtus ', '', 'Blanco', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(20, 0, 292, 'Martin Becerra', 'Ventas', 'UNF477E', 'Gol ', '', 'Plata', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(21, 0, 163, 'Luis Estrada ', 'Aplicaciones', 'UNF776E', 'Gol ', '', 'Plata', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(22, 0, 0, 'Fernando Gonzalez ', 'Aplicaciones', 'UMZ131E', 'Jetta ', '', 'Blanco', 2015, NULL, 'Activo ', '2025-04-07', 0, ''),
(23, 0, 420, 'Jorge Aaron', 'ZEISS', 'UNF756E', 'Vento ', '', 'Plata', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(24, 0, 244, 'Ulises Alanis', 'ZEISS', 'UNF768E', 'Vento ', '', 'Plata', 2021, NULL, 'Activo ', '2025-04-07', 0, ''),
(25, 0, 245, 'Julio Armadillo ', 'ZEISS', 'UPT176J', 'Virtus ', '', 'Plata', 2024, NULL, 'Activo ', '2025-04-07', 0, ''),
(26, 0, 459, 'Ossiel Gallardo ', 'ZEISS', 'UNB306D', 'Vento ', '', 'Plata', 2021, NULL, 'Activo ', '2025-04-07', 0, ''),
(27, 0, 218, 'Adolfo Reyes ', 'ZEISS', 'UPT175J', 'Virtus ', '', 'Plata', 2024, NULL, 'Activo ', '2025-04-07', 0, ''),
(28, 0, 478, 'Cesar Ramirez ', 'ZEISS', 'ST6905A', 'PEUGEOT', '', '0', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(29, 0, 47, 'Ricardo Teutli ', 'SPECC', '0', 'Gol ', '', 'Blanco', 2016, NULL, 'Activo ', '2025-04-07', 0, ''),
(30, 0, 47, 'Ricardo Teutli', 'SPECC', '0', 'Golf ', '', 'Rojo', 0, NULL, 'Activo ', '2025-04-07', 0, ''),
(31, 0, 304, 'Jesus Muñoz ', 'MT', 'UMK662J', 'Virtus ', '', 'Plata', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(32, 0, 232, 'Daniel  Cuecuecha', 'MT', 'UMZ118E', 'Vento ', '', 'Plata', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(33, 0, 110, 'Fernando San Juan ', 'MT', 'ULK218H', 'Virtus ', '', 'Azul', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(34, 0, 58, 'Sergio Cota ', 'SFG', 'UMW235E', 'Vento Confort', '', 'Blanco', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(35, 0, 262, 'Eduardo Trejo ', 'SFG', 'UNF455E', 'Jetta ', '', 'Blanco Mate', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(36, 0, 58, 'Sergio Cota ', 'SFG', 'UKM661J', 'Virtus ', '', 'Plata', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(37, 0, 58, 'Sergio Cota ', 'SFG', 'UNF456E', 'Jetta ', '', 'Blanco Mate', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(38, 0, 58, 'Sergio Cota ', 'SFG', 'UNU509G', 'Vento ', '', 'Rojo', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(39, 0, 350, 'Alejandro Sanchez', 'SFG', 'UNG891H', 'Virtus ', '', 'Plata', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(40, 0, 0, 'David jimenez', 'SFG', 'UNG889H', 'Virtus ', '', 'Plata', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(41, 0, 21, 'Miguel Martínez  ', 'SFG', 'UNF453E', 'Jetta MK ', '', 'Blanco', 2016, NULL, 'Activo ', '2025-04-07', 0, ''),
(42, 0, 53, 'Fabian Meléndez', 'Fuerza', 'UNF770E', 'Jetta ', '', 'Blanco Mate', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(43, 0, 53, 'Fabian Meléndez', 'Fuerza', 'SJK584A', 'Jetta  ', '', 'Blanco', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(44, 0, 53, 'Fabian Meléndez', 'Fuerza', 'UKM342J', 'Gol ', '', 'Plata', 2015, NULL, 'Activo ', '2025-04-07', 0, ''),
(45, 0, 53, 'Fabian Meléndez', 'Fuerza', 'UKM665J', 'Virtus ', '', 'Plata', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(46, 0, 156, 'Juan Harrel ', 'Fuerza', 'UNF454E', 'Vento ', '', 'Rojo', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(47, 0, 54, 'Jose Garduño ', 'Fuerza', 'UNA040D', 'Vento ', '', 'Rojo', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(48, 0, 53, 'Fabian Meléndez ', 'Fuerza', 'UNF758E', 'Jetta MK ', '', 'Rojo', 2017, NULL, 'Activo ', '2025-04-07', 0, ''),
(49, 0, 183, 'Adrian Castruita', 'Electrica', 'ULF983J', 'VIRTUS ', '', '0', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(50, 0, 183, 'Adrian Castruita', 'Electrica', 'ULV-991-J', 'VIRTUS ', '', '0', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(51, 0, 183, 'Adrian Castruita', 'Electrica', 'UNF-757-E', 'Gol', '', '0', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(52, 0, 183, 'Adrian Castruita', 'Electrica', 'UNF457E', 'Jetta MK ', '', 'Blanco', 2017, NULL, 'Activo ', '2025-04-07', 0, ''),
(53, 0, 93, 'Alberto Morales Jr. ', 'MTS', 'ULV993J', 'Virtus ', '', 'Azul', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(54, 0, 93, 'Alberto Morales Jr. ', 'MTS', 'UMZ179D', 'Vento ', '', 'Plata', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(55, 0, 93, 'Alberto Morales Jr. ', 'MTS', 'SU2914C', 'Caddy ', '', 'Blanco', 2024, NULL, 'Activo ', '2025-04-07', 0, ''),
(56, 0, 93, 'Alberto Morales Jr. ', 'MTS', 'UNC298C', 'Vento ', '', 'Plata', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(57, 0, 121, 'Brenda Morales', 'MESS', '0', 'Polo ', '', 'Azul Noche', 0, NULL, 'Activo ', '2025-04-07', 0, ''),
(58, 0, 112, 'Edgar  ', 'Logistica', 'SV6353B', 'Caddy ', '', 'Blanco', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(59, 0, 31, 'Vicky Rico ', 'Logistica', 'SV6343B', 'Trasporter ', '', 'Blanco', 2021, NULL, 'Activo ', '2025-04-07', 0, ''),
(60, 0, 0, 'Ivan Mendez', 'MESS', 'UMX491E', 'Vento ', '', 'Rojo', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(61, 0, 23, 'Carlos Guzmán ', 'MESS', 'UMZ117E', 'Jetta GL ', '', 'Arena', 2013, NULL, 'Activo ', '2025-04-07', 0, ''),
(62, 0, 130, 'Fernanda Espino', 'MESS', 'UMZ119E', 'Jetta ', '', 'Rojo', 2013, NULL, 'Activo ', '2025-04-07', 0, ''),
(63, 0, 130, 'Fernanda Espino ', 'MESS', 'UNU508G', 'Vento ', '', 'Rojo', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(64, 0, 116, 'Rafael', 'MESS', 'UNF497E', 'Gol ', '', 'Plata', 2015, NULL, 'Activo ', '2025-04-07', 0, ''),
(65, 0, 116, 'Rafael ', 'MESS', 'UNF498E', 'Gol', '', 'Plata', 2016, NULL, 'Activo ', '2025-04-07', 0, ''),
(66, 0, 116, 'Rafael', 'MESS', 'UNF470E', 'Jetta', '', 'Rojo', 2017, NULL, 'Activo ', '2025-04-07', 0, ''),
(67, 0, 0, 'Tacoma', 'MESS', 'SX4270B', 'Tacoma ', '', 'Plata', 2008, NULL, 'Activo ', '2025-04-07', 0, ''),
(68, 0, 0, 'Nissan', 'MESS', 'SX2202B', 'Nissan ', '', 'Blanco', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(69, 0, 123, 'Maury / Comodin', 'MESS', 'UNF767E', 'Gol', '', 'Blanco', 2017, NULL, 'Activo ', '2025-04-07', 0, ''),
(70, 0, 0, 'Camion MESS', 'MESS', 'SX2236B', 'Costellium', '', '0', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(71, 0, 260, 'Daniel Guevara ', 'AGS', 'UNU207G', 'Vento ', '', 'Plata', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(72, 0, 442, 'Melani ', 'AGS', 'UMZ113E', 'Gol ', '', 'Blanco', 2016, NULL, 'Activo ', '2025-04-07', 0, ''),
(73, 0, 390, 'Eduardo corral ', 'AGS', 'UMZ121E', 'Vento ', '', 'Plata', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(74, 0, 191, 'Pablo Reyes', 'AGS', 'ULF756G', 'Virtus ', '', 'Rojo', 2023, NULL, 'Activo ', '2025-04-07', 0, ''),
(75, 0, 241, 'Karla Uribe ', 'AGS', 'UMX 481E ', 'Jetta MK ', '', 'Blanco', 2017, NULL, 'Activo ', '2025-04-07', 0, ''),
(76, 0, 44, 'Omar Lopez ', 'AGS', 'UNF769E', 'T-CROSS', '', '0', 2021, NULL, 'Activo ', '2025-04-07', 0, ''),
(77, 0, 241, 'Karla Uribe ', 'AGS', 'UNF497E', 'Gol ', '', 'Plata', 2015, NULL, 'Activo ', '2025-04-07', 0, ''),
(78, 0, 44, 'Omar Lopez', 'AGS', 'UNF461E', 'Vento ', '', 'Plata', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(79, 0, 352, 'Francisco Martinez', 'MESS SLP', 'UMX494E', 'Gol ', '', 'Plata', 2018, NULL, 'Activo ', '2025-04-07', 0, ''),
(80, 0, 322, 'Eder Cazares', 'MESS SLP', 'SX2244B', 'Transporter ', '', 'Blanco', 2022, NULL, 'Activo ', '2025-04-07', 0, ''),
(81, 0, 63, 'Arnoldo Lara', 'MESS SLP', 'UNF766E', 'ONIX ', '', '0', 2021, NULL, 'Activo ', '2025-04-07', 0, ''),
(82, 0, 329, 'David Prado', 'MESS SLP', 'UNF759E', 'Gol ', '', 'Plata', 2019, NULL, 'Activo ', '2025-04-07', 0, ''),
(83, 0, 313, 'Osvaldo Lopez', 'MESS SLP', 'UNF774E', 'Jetta ', '', 'Blanco', 2015, NULL, 'Activo ', '2025-04-07', 0, ''),
(84, 0, 0, 'Comodin', 'MESS SLP', 'UNF462E', 'Vento ', '', 'Plata', 2020, NULL, 'Activo ', '2025-04-07', 0, ''),
(85, 0, 44, 'Omar Corro', 'MESS SLP', 'UNF492E', 'Jetta ', '', 'Rojo', 2017, NULL, 'Activo ', '2025-04-07', 0, ''),
(86, 0, 256, 'Omar Corro ', 'MESS SLP', 'UPT174J', 'Virtus ', '', '0', 2024, NULL, 'Activo ', '2025-04-07', 0, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kilometrajes`
--

CREATE TABLE `kilometrajes` (
  `id` int NOT NULL,
  `id_coche` int NOT NULL,
  `km` int NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id_manetenimiento` int NOT NULL,
  `id_coche` int NOT NULL,
  `fecha` date NOT NULL,
  `kilometraje` int NOT NULL,
  `gasolina` int NOT NULL,
  `tipo_mantenimiento` varchar(50) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `solicitante` varchar(50) NOT NULL,
  `VoBo_jefe` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos`
--

CREATE TABLE `prestamos` (
  `id_prestamo` int NOT NULL,
  `id_coche` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_checklist` int NOT NULL,
  `fecha` date NOT NULL,
  `estatus` varchar(50) NOT NULL,
  `kilometraje_inicio` int NOT NULL,
  `kilometraje_fin` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revisiones`
--

CREATE TABLE `revisiones` (
  `id_revision` int NOT NULL,
  `id_coche` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha` date NOT NULL,
  `aceite` varchar(20) NOT NULL,
  `anticongelante` varchar(20) NOT NULL,
  `liquido_frenos` varchar(20) NOT NULL,
  `limpia_parabrisas` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `siniestros`
--

CREATE TABLE `siniestros` (
  `id_siniestro` int NOT NULL,
  `id_coche` int NOT NULL,
  `fecha` date NOT NULL,
  `hora` int NOT NULL,
  `kilometraje` int NOT NULL,
  `gasolina` int NOT NULL,
  `origen` int NOT NULL,
  `destino` varchar(50) NOT NULL,
  `lugar` varchar(50) NOT NULL,
  `empresa` varchar(50) NOT NULL,
  `servicio` varchar(50) NOT NULL,
  `coordenadas` int NOT NULL,
  `descripcion` varchar(50) NOT NULL,
  `partes_dañadas` varchar(50) NOT NULL,
  `ubicacion_vehiculo` varchar(50) NOT NULL,
  `contacto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_fotos`
--

CREATE TABLE `tipos_fotos` (
  `id` int NOT NULL,
  `solicitud_mantenimiento` varchar(200) NOT NULL,
  `reporte_siniestro` varchar(200) NOT NULL,
  `otro` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `id_usuario` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `region` varchar(50) NOT NULL,
  `area` varchar(50) NOT NULL,
  `id_jefe` int NOT NULL,
  `estatus` varchar(50) NOT NULL,
  `correo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Índices para tablas volcadas
--
INSERT INTO `usuarios` (`id`, `usuario`, `password`, `noEmpleado`, `nombre`, `correo`, `puesto`, `region`, `departamento`, `fechaIngreso`, `estatus`, `rol`, `jefe`, `diasdisponibles`, `tipoContrato`, `sexo`, `nss`, `rfc`, `curp`, `tipoSangre`) VALUES
(199, 'sebastian.gutierrez@mess.com.mx', 'sebas123', '523', 'Sebastian Gutierrez Rodriguez', 'sebastian.gutierrez@mess.com.mx', 61, 4, 1, '2025-02-05', 1, 1, '183', '0', 'TEMPORAL', 'M', '27150153347', 'GURS0106173A3', 'GURS010617HQTTDDA0', 'A+'),
(2, 'silvia@mess.com.mx', 'silvia', '2', 'Avendaño Govea Patricia Silvia', 'silvia@mess.com.mx', 23, 4, 1, '2009-10-01', 1, 3, '19', '0', 'PLANTA', 'F', '1479600448-0', 'AEGP600921KR7', 'AEGP600921MQTVVT01', 'ORH+'),
(3, 'irma.botello@mess.com.mx', 'irma.botello', '3', 'Botello Pérez Irma ', 'irma.botello@mess.com.mx', 65, 4, 1, '2012-06-11', 1, 1, '2', '19', 'PLANTA', 'F', '1489690890-3', 'BOPI690924EY6', 'BOPI690924MQTTRR04', 'ORH+'),
(4, 'marypaz.cruz@mess.com.mx', 'marypaz.cruz', '5', 'Cruz Cruz María de la Paz', 'marypaz.cruz@mess.com.mx', 45, 4, 4, '2013-01-07', 1, 3, '19', '20', 'PLANTA', 'F', '1397781278-6', 'CUCP78012493A', 'CUCP780124MHGRRZ05', 'BRH+'),
(5, 'erik@mess.com.mx', 'erikgallardo010', '7', 'Gallardo Calderón Erik ', 'erik@mess.com.mx', 64, 4, 19, '2013-04-01', 1, 1, '33', '23', 'PLANTA', 'M', '1406898157-1', 'GACE890209CX6', 'GACE890209HQTLLR00', 'ORH+'),
(6, 'lucrecia@mess.com.mx', 'llino2308', '10', 'Lino Salinas Lucrecia ', 'lucrecia@mess.com.mx', 46, 4, 7, '2012-01-30', 1, 3, '2', '14', 'PLANTA', 'F', '1288732606-9', 'LISL730911V60', 'LISL730911MQTNLC02', 'ORH+'),
(7, 'jorge@mess.com.mx', 'JorgeMess9', '11', 'Mancilla Silva Jorge Luis', 'jorge@mess.com.mx', 51, 8, 5, '2010-02-01', 1, 3, '19', '24', 'PLANTA', 'M', '1408862122-3', 'MASJ860709L52', 'MASJ860709HQTNLR09', 'ORH+'),
(8, 'miguel@mess.com.mx', 'miguel', '13', 'Martínez Cruz José Miguel', 'miguel@mess.com.mx', 52, 4, 36, '2012-08-14', 1, 1, '45', '1', 'PLANTA', 'M', '6892741767-2', 'MACM740315TQ5', 'MACM740315HOCRRG01', 'ORH+'),
(9, 'lab.fuerza@mess.com.mx', 'Morpheus21', '14', 'Meléndez Acevedo Fabián ', 'lab.fuerza@mess.com.mx', 55, 4, 21, '2012-06-06', 1, 3, '521', '24', 'PLANTA', 'M', '1407881930-8', 'MEAF8810083C2', 'MEAF881008HGTLCB07', 'ARH+'),
(10, 'ivan@mess.com.mx', 'ivan', '15', 'Méndez García Iván Alejandro', 'ivan@mess.com.mx', 23, 4, 30, '2009-10-01', 1, 3, '19', '80', 'PLANTA', 'M', '3700760530-8', 'MEGI7608017X5', 'MEGI760801HGTNRV06', ''),
(11, 'gerardo@mess.com.mx', 'gerardo', '16', 'Moctezuma Flores Gerardo ', 'gerardo@mess.com.mx', 43, 4, 19, '2011-11-14', 1, 1, '33', '13', 'PLANTA', 'M', '1311881086-3', 'MOFG880707ID2', 'MOFG880707HHGCLR04', 'ARH+'),
(12, 'alberto@mess.com.mx', 'alberto', '18', 'Morales García Jorge Alberto', 'alberto@mess.com.mx', 23, 4, 21, '2009-10-01', 1, 1, '19', '80', 'PLANTA', 'M', '1489703763-7', 'MOGJ700609AV7', 'MOGJ700609HDFRRR00', ''),
(13, 'oscar@mess.com.mx', 'oscar', '19', 'Morales García José Oscar Tomás', 'oscar@mess.com.mx', 24, 4, 10, '2009-10-01', 1, 3, '71', '80', 'PLANTA', 'M', '6285681603-8', 'MOGO6812167P3', 'MOGO681216HDFRRS06', ''),
(14, 'omar@mess.com.mx', 'omar', '20', 'Morales García Omar Israel', 'omar@mess.com.mx', 23, 4, 36, '2009-10-01', 1, 3, '19', '80', 'PLANTA', 'M', '1404770043-1', 'MOGO770528317', 'MOGO770528HGTRRM06', ''),
(15, 'cesar.ramirez@mess.com.mx', 'cesar.ramirez', '25', 'Ramírez García César ', 'cesar.ramirez@mess.com.mx', 38, 4, 2, '2015-01-16', 1, 3, '19', '15', 'PLANTA', 'M', '1410860118-9', 'RAGC860916DK5', 'RAGC860916HQTMRS11', ''),
(16, 'esmeralda@mess.com.mx', 'esmeralda', '26', 'Ramírez López Esmeralda Patricia', 'esmeralda@mess.com.mx', 58, 6, 40, '2010-10-05', 1, 1, '15', '24', 'PLANTA', 'F', '8885640235-1', 'RALE640612AW3', 'RALE640612MMNMPS01', 'N/D'),
(17, 'dulcer@mess.com.mx', 'Santi1405', '27', 'Reyes Hernández Dulce María', 'dulcer@mess.com.mx', 62, 4, 1, '2012-05-28', 1, 1, '2', '13', 'PLANTA', 'F', '1408841075-9', 'REHD840912JY8', 'REHD840912MQTYRL05', ''),
(18, 'adm_mess@mess.com.mx', 'adm_mess', '28', 'Rodríguez Barba Nora Liliana', 'adm_mess@mess.com.mx', 58, 4, 1, '2013-01-07', 1, 1, '2', '14', 'PLANTA', 'F', '1497740198-4', 'ROBN740225TB1', 'ROBN740225MVZDRR06', ''),
(19, 'carlos.guzman@mess.com.mx', 'carlos.guzman', '33', 'Guzmán Reyes Carlos Fernando', 'carlos.guzman@mess.com.mx', 47, 4, 19, '2013-04-08', 1, 3, '521', '21', 'PLANTA', 'M', '3705720003-4', 'GURC7202218V9', 'GURC720221HDFZYR19', 'N/D'),
(20, 'omaral@mess.com.mx', 'omaral', '37', 'López Corral Omar Alejandro', 'omaral@mess.com.mx', 29, 1, 40, '2013-06-10', 1, 3, '19', '24', 'PLANTA', 'M', '2413860598-5', 'LOCO860819HKA', 'LOCO860819HSRPRM03', 'ORH+'),
(21, 'fernando@mess.com.mx', 'fernando', '39', 'Romero Espinosa Fernando ', 'fernando@mess.com.mx', 58, 4, 40, '2013-06-10', 1, 1, '15', '-2', 'PLANTA', 'M', '1311860860-6', 'ROEF860220MR0', 'ROEF860220HHGMSR09', 'ORH+'),
(22, 'fernanda@mess.com.mx', 'fernanda', '41', 'Ríos Nieto María Fernanda', 'fernanda@mess.com.mx', 17, 4, 24, '2013-06-17', 1, 1, '19', '12', 'PLANTA', 'F', '1406917353-3', 'RINF9103276P1', 'RINF910327MQTSTR08', 'ARH+'),
(23, 'vrico@mess.com.mx', 'vrico', '42', 'Rico Corona María Virginia', 'vrico@mess.com.mx', 48, 4, 25, '2013-07-16', 1, 3, '521', '24', 'PLANTA', 'F', '1409903547-0', 'RICV900813SS8', 'RICV900813MQTCRR06', 'ARH+'),
(24, 'sergio@mess.com.mx', 'sergio', '45', 'Cota Luque Sergio Adán', 'sergio@mess.com.mx', 54, 4, 36, '2013-10-07', 1, 3, '20', '3', 'PLANTA', 'M', '2310882067-3', 'COLS880330A87', 'COLS880330HSLTQR02', 'ARH+'),
(25, 'ivan.rosales@mess.com.mx', 'ivan.rosales', '46', 'Rosales Pérez Iván ', 'ivan.rosales@mess.com.mx', 38, 8, 5, '2013-10-28', 1, 1, '11', '16', 'PLANTA', 'M', '3213890252-6', 'ROPI890720GK5', 'ROPI890720HCLSRV00', ''),
(26, 'david.jimenez@mess.com.mx', 'david.jimenez', '47', 'Jiménez Pérez César David', 'david.jimenez@mess.com.mx', 38, 4, 36, '2013-10-28', 1, 1, '45', '13', 'PLANTA', 'M', '1310850190-2', 'JIPC8512017N7', 'JIPC851201HHGMRS06', 'ORH+'),
(27, 'hilda.estrada@mess.com.mx', 'hilda.estrada', '56', 'Estrada Rodríguez María Hilda', 'hilda.estrada@mess.com.mx', 65, 4, 1, '2014-04-23', 1, 1, '2', '6', 'PLANTA', 'F', '1401740105-1', 'EARH7401024M1', 'EARH740102MQTSDL01', ''),
(28, 'lab.par@mess.com.mx', 'lab.par', '61', 'Garduño Cortés José Antonio', 'lab.par@mess.com.mx', 38, 4, 21, '2014-05-29', 1, 1, '14', '21', 'PLANTA', 'M', '3814928653-6', 'GACA920504IE7', 'GACA920504HHGRRN05', 'ORH+'),
(29, 'abelardo@mess.com.mx', 'abelardo', '68', 'Luna Álvarez Abelardo ', 'abelardo@mess.com.mx', 38, 8, 5, '2014-09-01', 1, 1, '11', '17', 'PLANTA', 'M', '4303841831-5', 'LUAA8411031C1', 'LUAA841103HNLNLB06', 'ORH+'),
(30, 'nayeli.trejo@mess.com.mx', 'nayeli.trejo', '71', 'Trejo Hernández Nayeli Alejandra', 'nayeli.trejo@mess.com.mx', 7, 4, 10, '2014-09-01', 1, 3, '19', '6', 'PLANTA', 'F', '1413900387-6', 'TEHN901211TX1', 'TEHN901211MQTRRY04', 'BRH+'),
(31, 'arnoldo@mess.com.mx', 'arnoldo', '81', 'Lara Reyes Arnoldo ', 'arnoldo@mess.com.mx', 51, 10, 5, '2015-02-16', 1, 3, '288', '14', 'PLANTA', 'M', '1014912760-6', 'LARA9105015R6', 'LARA910501HSLRYR02', 'ORH+'),
(32, 'cinthia.garcia@mess.com.mx', 'LULIPAMPIN', '85', 'García Meza Cinthia ', 'cinthia.garcia@mess.com.mx', 18, 10, 40, '2015-04-20', 1, 3, '288', '11', 'PLANTA', 'F', '1408907609-6', 'GAMC9003213K6', 'GAMC900321MMCRZN15', 'ARH+'),
(33, 'maury@mess.com.mx', 'maury', '90', 'Maury Toledo Augusto ', 'maury@mess.com.mx', 27, 4, 23, '2015-09-16', 1, 1, '19', '17', 'PLANTA', 'M', '0215738819-6', 'MATA731227G30', 'MATA731227HNERLG07', ''),
(34, 'marilyn@mess.com.mx', 'marilyn', '95', 'López Pino Marilyn Eneida', 'marilyn@mess.com.mx', 31, 4, 4, '2015-09-16', 1, 1, '15', '0', 'PLANTA', 'F', '0215665911-8', 'LOPM660620NF5', 'LOPM660620MNEPNR06', 'ORH+'),
(35, 'rafael@mess.com.mx', 'rafael', '100', 'Arroyo González Rafael ', 'rafael@mess.com.mx', 2, 4, 7, '2015-11-09', 1, 1, '10', '12', 'PLANTA', 'M', '2199809141-3', 'AOGR800613LB4', 'AOGR800613HGTRNF09', 'ARH+'),
(36, 'manuel.mendoza@mess.com.mx', 'manuel.mendoza1414', '101', 'Mendoza Pantoja José Manuel', 'manuel.mendoza@mess.com.mx', 43, 4, 19, '2015-12-16', 1, 1, '33', '4', 'PLANTA', 'M', '148667115-5', 'MEMP670525', 'MEMP670525HQTNNN04', 'ARH+'),
(37, 'ricardo.basilio@mess.com.mx', 'ricardo.basilio', '104', 'Basilio Torres Ricardo ', 'ricardo.basilio@mess.com.mx', 43, 4, 21, '2016-01-01', 1, 1, '14', '19', 'PLANTA', 'M', '5714956064-1', 'BATR9508022X9', 'BATR950802HQTSRC07', 'ORH+'),
(38, 'brenda@mess.com.mx', 'JAMM88', '107', 'Morales García  Brenda Elizabeth', 'brenda@mess.com.mx', 47, 4, 17, '2016-01-11', 1, 1, '123', '17', 'PLANTA', 'F', '1410880507-9', 'MOGB880311PK2', 'MOGB880311MGTRRR06', 'ORH+'),
(39, 'fernando.sanjuan@mess.com.mx', 'fernando.sanjuan', '110', 'San Juan Del Prado Ángel Fernando', 'fernando.sanjuan@mess.com.mx', 49, 4, 27, '2016-01-11', 1, 3, '15', '17', 'PLANTA', 'M', '9605871589-5', 'SAPA8710014M0', 'SAPA871001HDFNRN05', 'ORH+'),
(40, 'alberto.mg@mess.com.mx', 'Jabeto', '123', 'Morales Gallegos Jorge Alberto', 'alberto.mg@mess.com.mx', 53, 4, 28, '2015-03-05', 1, 3, '19', '18', 'PLANTA', 'M', '1409902519-0', 'MOGJ900821KRA', 'MOGJ900821HGTRLR09', 'BRH+'),
(41, 'ramon.almanza@mess.com.mx', 'ramon.almanza', '136', 'Almanza Sancen Jesús Ramón', 'ramon.almanza@mess.com.mx', 38, 4, 36, '2016-08-16', 1, 1, '45', '10', 'PLANTA', 'M', '1409900242-1', 'AASJ900721EP0', 'AASJ900721HGTLNS07', 'ORH+'),
(42, 'manuelr@mess.com.mx', 'manuelr', '142', 'Rosas García Luna José Manuel', 'manuelr@mess.com.mx', 56, 4, 38, '2016-11-08', 1, 3, '19', '12', 'PLANTA', 'M', '1607800023-5', 'ROGM800714TF5', 'ROGM800714HDFSRN07', 'ORH+'),
(43, 'jose.ruiz@mess.com.mx', 'jose.ruiz', '143', 'Ruiz Chavez José Apolinar', 'jose.ruiz@mess.com.mx', 1, 4, 7, '2016-12-01', 1, 1, '10', '-3', 'PLANTA', 'M', '1400802487-0', 'RUCA800428PTA', 'RUCA800428HTCZHP05', 'N/D'),
(44, 'alejandra.andrade@mess.com.mx', '19OCT1985', '147', 'Andrade Coronado Alejandra ', 'alejandra.andrade@mess.com.mx', 8, 4, 27, '2017-01-03', 1, 1, '110', '10', 'PLANTA', 'F', '1405852403-5', 'AACA851019HJ6', 'AACA851019MQTNRL03', 'ORH+'),
(45, 'luis.arias@mess.com.mx', 'luis.arias', '155', 'Arias Aguirre José Luis', 'luis.arias@mess.com.mx', 38, 4, 36, '2017-02-07', 1, 1, '45', '14', 'PLANTA', 'M', '6316934837-0', 'AIAL931015TU2', 'AIAL931015HSLRGS01', 'ORH+'),
(46, 'contabilidad@mess.com.mx', 'elConta21', '156', 'Santana Mejía Julio Cesar', 'contabilidad@mess.com.mx', 14, 4, 1, '2017-02-07', 1, 1, '2', '0', 'PLANTA', 'M', '0215852557-2', 'SAMJ850118UK4', 'SAMJ850118HDFNJL00', ''),
(47, 'fernanda.espino@mess.com.mx', 'fernanda.espino', '161', 'Espino Torres María Fernanda', 'fernanda.espino@mess.com.mx', 47, 4, 18, '2017-03-01', 1, 3, '521', '14', 'PLANTA', 'F', '1411935984-3', 'EITF931116UH3', 'EITF931116MQTSRR09', 'ORH+'),
(48, 'trans.fza@mess.com.mx', 'MESS2025', '162', 'Harrell Gonzalez Juan José', 'trans.fza@mess.com.mx', 38, 4, 21, '2017-03-15', 1, 1, '14', '10', 'PLANTA', 'M', '4113945880-0', 'HAGJ940130CB3', 'HAGJ940130HSPRNN08', 'ORH+'),
(49, 'evita.glz.u@gmail.com', '1981', '164', 'Eva Gonzalez Uribe', 'evita.glz.u@gmail.com', 65, 4, 1, '2017-04-03', 1, 1, '2', '13', 'PLANTA', 'F', '1401810331-8', 'GOUE810818DI3', 'GOUE810818MQTNRV08', ''),
(50, 'gisela.batule@mess.com.mx', 'gisela.batule', '167', 'Batule Naranjo Gisela ', 'gisela.batule@mess.com.mx', 60, 4, 1, '2017-04-10', 1, 1, '2', '16', 'PLANTA', 'F', '1410850732-9', 'BANG851112MP3', 'BANG851112MNETRS06', ''),
(51, 'jose.lara@mess.com.mx', '12927408075', '174', 'Lara de la Torre José Trinidad', 'jose.lara@mess.com.mx', 6, 3, 40, '2017-06-01', 1, 1, '324', '18', 'PLANTA', 'M', '1212947772-0', 'LATT7402175Q0', 'LATT740217HGTRRR03', 'ARH+'),
(52, 'atc.sfg@mess.com.mx', 'atc.sfg', '177', 'Servín Barrera Ana Zayi', 'atc.sfg@mess.com.mx', 37, 4, 36, '2017-06-01', 1, 1, '45', '21', 'PLANTA', 'F', '1212947772-0', 'SEBA9410047G2', 'SEBA941004MGTRRN09', 'ORH+'),
(53, 'victor.acosta@mess.com.mx', 'victor.acosta', '178', 'Acosta Castillo Victor Manuel', 'victor.acosta@mess.com.mx', 6, 4, 40, '2017-06-16', 1, 1, '324', '21', 'PLANTA', 'M', '4614958836-3', 'AOCV9511234UA', 'AOCV951123HGRCSC04', 'ORH+'),
(54, 'amram@mess.com.mx', 'Amry04lop', '183', 'López Ochoa Amram ', 'amram@mess.com.mx', 50, 4, 32, '2017-07-17', 1, 3, '19', '4', 'PLANTA', 'M', '1406910828-1', 'LOOA910401P79', 'LOOA910401HMNPCM03', 'ORH-'),
(55, 'luis.estrada@mess.com.mx', 'mess-app', '189', 'Estrada Paredes Luis Fidel', 'luis.estrada@mess.com.mx', 44, 4, 3, '2017-09-01', 1, 3, '15', '0', 'PLANTA', 'M', '0315897174-1', 'EAPL890627PX4', 'EAPL890627HGTSRS07', 'ORH+'),
(56, 'rogelio@mess.com.mx', 'Andrea14', '191', 'Gutiérrez Cazares Rogelio ', 'rogelio@mess.com.mx', 19, 4, 1, '2017-09-01', 1, 1, '19', '-7', 'PLANTA', 'M', '1402720163-2', 'GUCR720915EA9', 'GUCR720915HDFTZG06', ''),
(57, 'fernando.gonzalez@mess.com.mx', 'Appfer192*', '192', 'González Aguado Fernando de Jesús', 'fernando.gonzalez@mess.com.mx', 35, 4, 3, '2017-09-01', 1, 1, '189', '0', 'PLANTA', 'M', '1486860014-0', 'GOAF8605237D3', 'GOAF860523HQTNGR02', 'ORH+'),
(58, 'jazmin.bautista@mess.com.mx', 'jazmin.bautista', '199', 'Bautista Nava Jazmin ', 'jazmin.bautista@mess.com.mx', 22, 4, 1, '2017-12-04', 1, 1, '2', '10', 'PLANTA', 'F', '7207881911-6', 'BANJ881007T36', 'BANJ881007MGRTVZ06', ''),
(59, 'rolando.carreon@mess.com.mx', 'Mess2021', '203', 'Carreón Castañeda Rolando ', 'rolando.carreon@mess.com.mx', 38, 8, 5, '2020-09-21', 1, 1, '11', '1', 'PLANTA', 'M', '4305850841-7', 'CACR851209FZ7', 'CACR851209HNLRSL08', 'ORH+'),
(60, 'orana.salcedo@mess.com.mx', 'orana03', '204', 'Salcedo Enriquez Orana Maheva', 'orana.salcedo@mess.com.mx', 8, 4, 29, '2017-12-11', 1, 1, '123', '17', 'PLANTA', 'F', '9002810905-7', 'SAEO810303P86', 'SAEO810303MDFLNR06', 'ORH+'),
(61, 'pablo.reyes@mess.com.mx', 'pablo.reyes', '206', 'Reyes Terán Juan Pablo', 'pablo.reyes@mess.com.mx', 38, 1, 36, '2017-12-11', 1, 3, '37', '15', 'PLANTA', 'M', '5108860958-4', 'RETJ8607305C8', 'RETJ860730HASYRN00', ''),
(62, 'adrian.castruita@mess.com.mx', 'adrian.castruita', '212', 'Castruita Romero Adrián de Jesús', 'adrian.castruita@mess.com.mx', 47, 4, 20, '2018-01-29', 1, 3, '521', '22', 'PLANTA', 'M', '0218861566-6', 'CARA8609137A8', 'CARA860913HDGSMD05', 'BRH+'),
(63, 'ariadna@mess.com.mx', 'ariadna', '213', 'Figueroa Nieto Guadalupe Alejandra', 'ariadna@mess.com.mx', 8, 6, 5, '2018-02-01', 1, 1, '15', '14', 'PLANTA', 'F', '1404842997-2', 'FING8407117Y8', 'FING840711MQTGTD02', 'ORH+'),
(64, 'ariadna@mess.com.mx', 'ariadna.mendoza', '214', 'Mendoza Ibañez Ariadna Lizbeth', 'ariadna@mess.com.mx', 8, 4, 40, '2018-02-01', 1, 1, '19', '18', 'PLANTA', 'F', '2514956981-3', 'MEIA950423TR2', 'MEIA950423MGTNBR05', 'ORH+'),
(65, 'jlhernandez@mess.com.mx', 'jlhernandez', '215', 'Hernández Méndez Jorge Luis', 'jlhernandez@mess.com.mx', 59, 4, 24, '2018-03-05', 1, 1, '19', '22', 'PLANTA', 'M', '4103760221-0', 'HEMJ761111SN2', 'HEMJ761111HSPRNR00', ''),
(66, 'daniel.gonzalez@mess.com.mx', 'daniel.gonzalez', '222', 'Cuecuecha Gonzalez Daniel Aldair', 'daniel.gonzalez@mess.com.mx', 38, 4, 27, '2018-04-02', 1, 1, '110', '15', 'PLANTA', 'M', '6713941926-0', 'CUGD941019RK3', 'CUGD941019HVZCNN08', 'ORH+'),
(67, 'carmen.ls@mess.com.mx', 'carmen.ls', '225', 'López Sánchez María del Carmen', 'carmen.ls@mess.com.mx', 58, 4, 40, '2018-06-19', 1, 1, '71', '22', 'PLANTA', 'F', '2216944883-8', 'LOSC940715I91', 'LOSC940715MVZPNR01', 'ORH+'),
(68, 'adolfo.reyes@mess.com.mx', 'adolfo.reyes', '226', 'Reyes Durán Adolfo Isaí', 'adolfo.reyes@mess.com.mx', 38, 4, 5, '2018-07-16', 1, 3, '15', '0', 'PLANTA', 'M', '1412945011-1', 'REDA940501QP1', 'REDA940501HVZYRD06', 'ORH+'),
(69, 'ramon.jauregui@mess.com.mx', 'ramon.jauregui', '236', 'Jauregui Hernández Ramón ', 'ramon.jauregui@mess.com.mx', 6, 8, 40, '2018-10-01', 1, 1, '11', '20', 'PLANTA', 'M', '4300740202-7', 'JAHR740513BR2', 'JAHR740513HNLRRM03', 'ORH+'),
(70, 'misael.gutierrez@mess.com.mx', 'misael.gutierrez', '238', 'Gutiérrez Pacheco Misael ', 'misael.gutierrez@mess.com.mx', 43, 4, 18, '2018-10-16', 1, 1, '161', '11', 'PLANTA', 'M', '1412960612-6', 'GUPM960212ET0', 'GUPM960212HQTTCS05', 'ORH-'),
(71, 'teresa.iniguez@mess.com.mx', 'teresa.iniguez', '240', 'Iñiguez Alonzo Teresa de Jesús', 'teresa.iniguez@mess.com.mx', 6, 1, 40, '2018-10-22', 1, 1, '5555', '-2', 'PLANTA', 'F', '1207917049-9', 'IIAT911206831', 'IIAT911206MGTXLR07', 'ORH-'),
(72, 'oswaldo.lopez@mess.com.mx', 'oswaldo.lopez', '247', 'López Cruz Martín Osvaldo', 'oswaldo.lopez@mess.com.mx', 38, 10, 5, '2018-12-05', 1, 1, '81', '12', 'PLANTA', 'M', '4111935355-9', 'LOCM930223V22', 'LOCM930223HSPPRR00', 'ORH+'),
(73, 'ulises.alanis@mess.com.mx', 'ulises-mess', '260', 'Alanis Montes de Oca Ulises ', 'ulises.alanis@mess.com.mx', 38, 4, 5, '2019-02-18', 1, 1, '15', '19', 'PLANTA', 'M', '6316948215-3', 'AAMU941121KU3', 'AAMU941121HMNLNL05', 'ARH+'),
(74, 'julio.armadillo@mess.com.mx', 'armadillo.julio', '261', 'Armadillo Mercado Julio ', 'julio.armadillo@mess.com.mx', 38, 4, 5, '2019-02-18', 1, 1, '226', '7', 'PLANTA', 'M', '1409933678-7', 'AAMJ930515ET2', 'AAMJ930515HQTRRL08', 'ORH+'),
(75, 'aftermarket@mess.com.mx', 'Pleslie93!', '262', 'González López Pamela Leslie', 'aftermarket@mess.com.mx', 8, 4, 40, '2019-02-18', 1, 1, '263', '11', 'PLANTA', 'F', '1410931671-2', 'GOLP930408RC8', 'GOLP930408MDFNPM08', 'ARH+'),
(76, 'karen.hernandez@mess.com.mx', 'karen.hernandez', '263', 'Hernández Ruiz Ana Karen', 'karen.hernandez@mess.com.mx', 8, 4, 40, '2019-03-01', 1, 3, '19', '20', 'PLANTA', 'F', '4614961376-5', 'HERA960526DH4', 'HERA960526MQTRZN07', 'N/D'),
(77, 'sergio.martinez@mess.com.mx', 'Av210825', '264', 'Martínez Rojas Sergio Jeronimo', 'sergio.martinez@mess.com.mx', 58, 4, 40, '2019-03-01', 0, 1, '19', '18', 'PLANTA', 'M', '5414944184-9', 'MARS940830UJ9', 'MARS940830HHGRJR04', 'ORH+'),
(78, 'atc_fza@mess.com.mx', 'atc_fza', '267', 'Herrera Nuñez María Jessica', 'atc_fza@mess.com.mx', 9, 4, 21, '2019-03-06', 1, 1, '14', '13', 'PLANTA', 'F', '1404800152-4', 'HENJ801218DK6', 'HENJ801218MQTRXS07', 'ORH+'),
(79, 'alfonso.camacho@mess.com.mx', 'alfonso.camacho', '275', 'Camacho Alzaga Javier Alfonso', 'alfonso.camacho@mess.com.mx', 6, 4, 40, '2019-05-06', 1, 1, '324', '19', 'PLANTA', 'M', '1499801756-1', 'CAAJ800115RH6', 'CAAJ800115HDFMLV04', 'ABRH+'),
(80, 'pedro.martinez@mess.com.mx', '2806koruz', '276', 'Martínez Piña Pedro ', 'pedro.martinez@mess.com.mx', 61, 4, 32, '2019-05-02', 1, 1, '183', '12', 'PLANTA', 'M', '0319901354-5', 'MAPP901231SY4', 'MAPP901231HQTRXD09', 'ORH+'),
(81, 'j.arredondo@mess.com.mx', 'j.arredondo', '278', 'Arredondo Zuno Mayra Janneth', 'j.arredondo@mess.com.mx', 8, 4, 5, '2019-05-06', 1, 1, '11', '20', 'PLANTA', 'F', '5310851875-3', 'AEZM8508303Z7', 'AEZM850830MMNRNY06', ''),
(82, 'iztel.uribe@mess.com.mx', 'iztel.uribe', '279', 'Uribe Pacheco Karla Itzel', 'iztel.uribe@mess.com.mx', 6, 1, 40, '2019-05-05', 1, 1, '37', '20', 'PLANTA', 'F', '1213921001-2', 'UIPK920604TB9', 'UIPK920604MGRRCR08', ''),
(83, 'eduardo.trejo@mess.com.mx', 'Coster123', '283', 'Trejo Hernández Eduardo ', 'eduardo.trejo@mess.com.mx', 39, 8, 36, '2019-06-17', 1, 1, '45', '18', 'PLANTA', 'M', '2011952626-5', 'TEHE951210VA9', 'TEHE951210HDFRRD07', 'ORH+'),
(84, 'omar.corro@mess.com.mx', 'omar.corro', '288', 'Corro Fuentes Omar ', 'omar.corro@mess.com.mx', 30, 10, 40, '2019-08-19', 1, 3, '19', '12', 'PLANTA', 'M', '1406740205-8', 'COFO740204FR5', 'COFO740204HHGRNM07', 'ARH+'),
(85, 'arturo.coronel@mess.com.mx', 'cinout157', '290', 'Coronel Naranjo Arturo ', 'arturo.coronel@mess.com.mx', 15, 4, 25, '2019-08-20', 1, 1, '42', '8', 'PLANTA', 'M', '1413890530-3', 'CONA8906085QA', 'CONA890608HQTRRR03', 'BRH+'),
(86, 'daniel.guevara@mess.com.mx', 'daniel.guevara', '293', 'Guevara Calvillo Juan Daniel', 'daniel.guevara@mess.com.mx', 38, 1, 5, '2019-08-26', 1, 1, '206', '4', 'PLANTA', 'M', '2616949747-1', 'GUCJ940813RM2', 'GUCJ940813HASVLN02', ''),
(87, 'j.galeano@mess.com.mx', 'process.fza', '295', 'Galeano González Jazmín Montserrat', 'j.galeano@mess.com.mx', 10, 4, 21, '2019-09-02', 1, 1, '14', '11', 'PLANTA', 'F', '4915941138-5', 'GAGJ9405258J6', 'GAGJ940525MQTLNZ03', 'ORH+'),
(88, 'gerardo.delgado@mess.com.mx', 'gerardo.delgado', '298', 'Delgado Mendez Juan Gerardo', 'gerardo.delgado@mess.com.mx', 43, 10, 19, '2019-09-30', 1, 3, '288', '9', 'PLANTA', 'M', '4108904557-3', 'DEMJ900324RK5', 'DEMJ900324HSPLNN05', 'ORH+'),
(89, 'juan.guzman@mess.com.mx', 'juan.guzman', '306', 'Guzmán Ruiz Juan Antonio ', 'juan.guzman@mess.com.mx', 26, 8, 11, '2022-12-13', 1, 1, '15', '1', 'PLANTA', 'M', '516943255-5', 'GURJ940720PT5', 'GURJ940720HNLZZN01', ''),
(90, 'ingeniero.movil3@mess.com.mx', 'ingeniero.movil3', '307', 'López Perales María Isabel', 'ingeniero.movil3@mess.com.mx', 8, 1, 40, '2019-12-09', 0, 1, '1111', '11', 'PLANTA', 'F', '1817934520-4', 'LOPI93102857A', 'LOPI931028MASPRS03', 'ARH+'),
(91, 'adan@mess.com.mx', 'Compaq18', '308', 'Enríquez Hernández Adán Alejandro', 'adan@mess.com.mx', 38, 4, 36, '2020-01-06', 1, 1, '45', '11', 'PLANTA', 'M', '4210940967-4', 'EIHA940813QZ7', 'EIHA940813HDFNRD04', 'ORH+'),
(92, 'ingeniero.movil5@mess.com.mx', 'ingeniero.movil5', '310', 'Arvizu Perrusquia Carlos Francisco', 'ingeniero.movil5@mess.com.mx', 38, 4, 29, '2020-02-05', 1, 1, '266', '14', 'PLANTA', 'M', '1412974721-9', 'AIPC970310F86', 'AIPC970310HQTRRR07', 'ARH+'),
(93, 'alfredo.paz@mess.com.mx', 'alfredo.paz', '311', 'Paz Mendoza Héctor Alfredo', 'alfredo.paz@mess.com.mx', 38, 8, 5, '2020-02-11', 1, 1, '11', '18', 'PLANTA', 'M', '3207908464-9', 'PAMH901106DG8', 'PAMH901106HCLZNC08', 'ORH+'),
(94, 'enrique.gonzalez@mess.com.mx', 'enrique.gonzalez', '317', 'González Galicia Enrique ', 'enrique.gonzalez@mess.com.mx', 6, 10, 40, '2020-03-19', 0, 1, '85', '15', 'PLANTA', 'M', '4186661078-9', 'GOGE660723NF0', 'GOGE660723HSPNLN02', 'ORH+'),
(95, 'guadalupe.suarez@mess.com.mx', 'maria.suarez', '318', 'Suárez Palomino María Guadalupe', 'guadalupe.suarez@mess.com.mx', 38, 4, 14, '2020-07-09', 1, 1, '161', '0', 'PLANTA', 'F', '1410940782-6', 'SUPG940102378', 'SUPG940102MGTRLD07', 'ORH+'),
(96, 'jhony.hernandez@mess.com.mx', 'jhony.hernandez', '321', 'Hernández Mendoza Jhony Raymundo', 'jhony.hernandez@mess.com.mx', 38, 4, 21, '2020-09-01', 1, 1, '14', '1', 'PLANTA', 'M', '3815971764-4', 'HEMJ970719BH3', 'HEMJ970719HHGRNH00', 'ORH+'),
(97, 'martin.becerra@mess.com.mx', 'M@rio805', '324', 'Becerra Medina Martín ', 'martin.becerra@mess.com.mx', 57, 4, 40, '2020-10-12', 1, 3, '19', '12', 'PLANTA', 'M', '1498820282-7', 'BEMM820328EK9', 'BEMM820328HQTCDR08', 'ARH+'),
(98, 'alberto.olguin@mess.com.mx', 'alberto.olguin', '327', 'Olguin Barrera Luis Alberto', 'alberto.olguin@mess.com.mx', 43, 4, 18, '2020-12-08', 1, 1, '161', '11', 'PLANTA', 'M', '1616953550-3', 'OUBL950813RX9', 'OUBL950813HHGLRS01', 'ORH+'),
(99, 'arely.montoya@mess.com.mx', 'arely.montoya', '328', 'Montoya Casas Arely ', 'arely.montoya@mess.com.mx', 28, 4, 1, '2021-02-08', 1, 1, '2', '7', 'PLANTA', 'F', '1408894295-9', 'MOCA8901101C1', 'MOCA890110MQTNSR07', 'ARH+'),
(100, 'cesar.hernandez@mess.com.mx', 'cesar.hernandez', '329', 'Hernández Vite Cesar Armando', 'cesar.hernandez@mess.com.mx', 38, 4, 29, '2021-06-16', 1, 1, '123', '16', 'PLANTA', 'M', '2016926638-2', 'HEVC920716J97', 'HEVC920716HHGRTS03', 'ORH+'),
(101, 'hector.losoyo@mess.com.mx', 'hector.losoyo', '333', 'Losoyo Vega Héctor Julián', 'hector.losoyo@mess.com.mx', 38, 4, 0, '2021-01-05', 1, 3, '9999', '8', 'PLANTA', 'M', '1404851563-0', 'LOVH8501234k0', 'LOVH850123HDFSGC03', ''),
(102, 'jose.munoz@mess.com.mx', 'Yisus1998.', '335', 'Muñoz Ugalde José de Jesús', 'jose.munoz@mess.com.mx', 38, 4, 27, '2021-01-11', 1, 1, '110', '14', 'PLANTA', 'M', '1413986279-2', 'MUUJ980121CA9', 'MUUJ980121HQTXGS00', 'ARH+'),
(103, 'florangeli@mess.com.mx', 'florangeli', '337', 'Vazquez Baltazar Florangeli de Jesus', 'florangeli@mess.com.mx', 6, 7, 40, '2021-09-06', 1, 1, '324', '6', 'PLANTA', 'F', '5508891124-7', 'VABF891225UD3', 'VABF891225MNTZLL01', 'ORH+'),
(104, 'alfredo.robles@mess.com.mx', 'alfredo.robles', '341', 'Robles Silva Luis Alfredo', 'alfredo.robles@mess.com.mx', 38, 4, 35, '2021-10-04', 1, 1, '14', '11', 'PLANTA', 'M', '1410947636-7', 'ROSL941227UT2', 'ROSL941227HQTBLS06', 'ORH+'),
(105, 'logistica@mess.com.mx', 'grupomess', '342', 'Galvan Alvarez Edgar', 'logistica@mess.com.mx', 63, 4, 25, '2021-10-04', 1, 1, '42', '7', 'PLANTA', 'M', '2894740480-3', 'GAAE741010S92', 'GAAE741010HMCLLD00', 'ORH+'),
(106, 'logistica.slp@mess.com.mx', 'sx2244b.', '344', 'Cazares Zapata Eder Geovanni ', 'logistica.slp@mess.com.mx', 63, 10, 25, '2021-10-18', 1, 1, '288', '8', 'PLANTA', 'M', '6416930975-0', 'CAZE931205CT4', 'CAZE931205HSPZPD06', 'ORH+'),
(107, '0', 'JuanOA44531', '347', 'Olvera Alegría Juan Adrián', '0', 25, 4, 37, '2021-12-01', 1, 1, '5555', '16', 'PLANTA', 'M', '1411941363-2', 'OEAJ941220LU7', 'OEAJ941220HQTLLN02', 'ORH+'),
(108, 'facturacion@mess.com.mx', 'facturacion', '348', 'Meza Hernández Teresa', 'facturacion@mess.com.mx', 28, 4, 1, '2021-12-01', 1, 1, '2', '3', 'PLANTA', 'F', '7205861754-8', 'MEHT860312MZ2', 'MEHT860312MGRZRR01', 'ORH+'),
(109, 'eduardo.prado@mess.com.mx', 'eduardo.prado', '350', 'Prado de Leon David Eduardo', 'eduardo.prado@mess.com.mx', 43, 10, 5, '2021-12-20', 1, 1, '81', '0', 'PLANTA', 'M', '4112924443-4', 'PALD920918MJ0', 'PALD920918HSPRNV05', 'ARH+'),
(110, 'guillermo.monsivais@mess.com.mx', 'guillermo.monsivais', '356', 'Monsiváis Cepeda Guillermo Alonso', 'guillermo.monsivais@mess.com.mx', 6, 8, 40, '2022-01-17', 1, 1, '5555', '14', 'PLANTA', 'M', '4302841374-8', 'MOCG840913R15', 'MOCG840913HNLNPL02', ''),
(111, 'karen.canul@mess.com.mx', 'karen.canul', '357', 'Canul Camacho Karen Militza', 'karen.canul@mess.com.mx', 8, 4, 36, '2022-01-19', 0, 1, '45', '8', 'PLANTA', 'F', '7416990688-5', 'CACK990610TI7', 'CACK990610MTCNMR08', 'ORH+'),
(112, 'edgar.vargas@mess.com.mx', 'edgar.vargas', '359', 'Vargas Hernández Edgar', 'edgar.vargas@mess.com.mx', 43, 10, 19, '2022-01-24', 1, 1, '298', '7', 'BAJA', 'M', '0516965009-9', 'VAHE960212HC8', 'VAHE960212HSPRRD02', ''),
(113, 'pedro.velazquez@mess.com.mx', 'pedro.velazquez', '360', 'Velázquez Vidal Pedro Luis ', 'pedro.velazquez@mess.com.mx', 38, 4, 20, '2022-01-24', 1, 1, '212', '7', 'PLANTA', 'M', '1313996577-9', 'VEVP9904141S8', 'VEVP990414HMNLDD07', 'ORH+'),
(114, 'Lorena.lopez@mess.com.mx', 'Lorena.lopez', '361', 'López Vergara Lorena Guadalupe', 'Lorena.lopez@mess.com.mx', 43, 10, 39, '2022-02-01', 1, 1, '5555', '12', 'PLANTA', 'F', '0316889972-6', 'LOVL880824598', 'LOVL880824MNTPRR07', ''),
(115, 'carlos.martinez@mess.com.mx', 'carlos.martinez', '366', 'Martínez Tinoco Carlos Uriel', 'carlos.martinez@mess.com.mx', 38, 4, 36, '2022-02-14', 1, 1, '45', '12', 'PLANTA', 'M', '3416981203-8', 'MATC980907B65', 'MATC980907HVZRNR00', ''),
(116, 'osiel.pardo@mess.com.mx', 'osiel.pardo', '374', 'Pardo Reyes Osiel Amador', 'osiel.pardo@mess.com.mx', 43, 10, 19, '2022-03-16', 0, 1, '7', '14', 'PLANTA', 'M', '4113932243-6', 'PARO931205278', 'PARO931205HSPRYS03', 'ORH-'),
(117, 'luz.sanchez@mess.com.mx', 'luz.sanchez', '376', 'Sánchez Rubio María de la Luz', 'luz.sanchez@mess.com.mx', 8, 4, 40, '2022-03-22', 1, 1, '324', '9', 'PLANTA', 'F', '1406911428-9', 'SARL910227AZ9', 'SARL910227MQTNBZ09', 'BRH+'),
(118, 'alejandro.sanchez@mess.com.mx', 'alejandro.sanchez', '377', 'Sanchez Lopez Jesús Alejandro ', 'alejandro.sanchez@mess.com.mx', 38, 4, 36, '2022-03-23', 1, 1, '45', '7', 'PLANTA', 'M', '5116990035-7', 'SALJ990805F39', 'SALJ990805HCSNPS01', 'ORH+'),
(119, 'alba@mess.com.mx', 'alba', '378', 'Hernández Trinidad Alba Lizeth', 'alba@mess.com.mx', 42, 4, 38, '2022-04-04', 1, 1, '142', '9', 'PLANTA', 'F', '3216999238-8', 'HETA990219467', 'HETA990219MVZRRL09', 'ORH+'),
(120, 'francisco.martinez@mess.com.mx', 'Olaf1974', '380', 'Martinez Sanchez Juan Francisco ', 'francisco.martinez@mess.com.mx', 6, 10, 40, '2022-07-11', 1, 1, '85', '-4', 'BAJA', 'M', '4190743492-6', 'MASJ7403246S3', 'MASJ740324HNLRNN02', ''),
(121, 'oscar3.parra@mess.com.mx', 'oscar3.parra', '383', 'Parra Flores Oscar III', 'oscar3.parra@mess.com.mx', 6, 9, 40, '2022-05-04', 1, 1, '5555', '14', 'PLANTA', 'M', '3295754196-7', 'PAFO751202S42', 'PAFO751202HCLRLS05', ''),
(122, 'josefina@mess.com.mx', 'josefina', '385', 'Sampogna Valdez Josefina', 'josefina@mess.com.mx', 8, 8, 40, '2022-06-06', 1, 1, '11', '14', 'PLANTA', 'F', '4398780660-1', 'SAVJ780421KK5', 'SAVJ780421MNLMLS02', 'ARH+'),
(123, 'cuentasdegastos@mess.com.mx', 'cuentasdegastos2025.', '386', 'Torres Lezama Ana Victoria', 'cuentasdegastos@mess.com.mx', 20, 4, 1, '2022-07-04', 1, 1, '2', '-1', 'PLANTA', 'F', '2805860693-1', 'TOLA861202DU7', 'TOLA861202MDFRZN07', 'BRH+'),
(124, 'maria.ayala@mess.com.mx', 'maria.ayala', '390', 'Ayala Cerecedo Ana María', 'maria.ayala@mess.com.mx', 43, 4, 19, '2022-08-16', 1, 1, '33', '-3', 'PLANTA', 'F', '7216003237-0', 'AACA001013QB7', 'AACA001013MMCYRNA1', 'ORH+'),
(125, 'ociel@mess.com.mx', 'ociel', '397', 'Rodriguez Vega Ociel Alejandro', 'ociel@mess.com.mx', 43, 4, 29, '2022-09-26', 1, 1, '5555', '4', 'PLANTA', 'M', '0314986586-1', 'ROVO9805066D9', 'ROVO980506HQTDGC05', 'ARH+'),
(126, 'jose.reynoso@mess.com.mx', 'jose.reynoso', '399', 'Reynoso Noguez Jose Maria', 'jose.reynoso@mess.com.mx', 40, 8, 5, '2022-10-11', 1, 1, '11', '2', 'TEMPORAL', 'M', '3700821909-1', 'RENM820113PK9', 'RENM820113HDFYGR04', 'ORH+'),
(127, 'fernanda.hernandez@mess.com.mx', 'rrhh2025', '403', 'Hernández Manrique María Fernanda', 'fernanda.hernandez@mess.com.mx', 5, 4, 34, '2022-11-16', 1, 2, '19', '10', 'PLANTA', 'F', '1408890684-8', 'HEMF891029QJ3', 'HEMF891029MDFRNR04', 'ORH+'),
(128, 'emmanuel.vizcaya@mess.com.mx', 'emmanuel.vizcaya', '404', 'Vizcaya Hernández Emmanuel ', 'emmanuel.vizcaya@mess.com.mx', 43, 4, 19, '2022-11-24', 1, 1, '33', '2', 'PLANTA', 'M', '315003179-1', 'VHE000401PM2', 'VHE000401HQTZRMA0', 'ORH+'),
(129, 'guillermo.cruz@mess.com.mx', 'guillermo.cruz', '406', 'Cruz Dominguez Guillermo ', 'guillermo.cruz@mess.com.mx', 43, 4, 21, '2023-01-02', 1, 1, '14', '3', 'PLANTA', 'M', '7816990647-2', 'CUDG990705G49', 'CUDG990705HVZRML07', 'ORH+'),
(130, 'maria.luna@mess.com.mx', 'maria.luna', '407', 'De Jesús Luna María José', 'maria.luna@mess.com.mx', 43, 4, 18, '2023-01-16', 1, 1, '5555', '4', 'PLANTA', 'F', '1411952534-4', 'JELJ950516QP6', 'JELJ950516MQRSNS00', 'ORH+'),
(131, 'julio.saldivar@mess.com.mx', 'julio.saldivar', '410', 'Saldívar Acuña Julio Cesar', 'julio.saldivar@mess.com.mx', 6, 8, 0, '2023-01-23', 1, 1, '5555', '2', 'PLANTA', 'M', '519977898-5', 'SAAJ971218BQ1', 'SAAJ971218HNLLCL01', ''),
(132, 'omar.aviles@mess.com.mx', 'omar.aviles', '411', 'Avilés Rodríguez Omar Yoseph ', 'omar.aviles@mess.com.mx', 43, 10, 39, '2023-01-23', 1, 1, '81', '12', 'PLANTA', 'M', '2413985105-9', 'AIRO981012NA8', 'AIRO981012HSPVDM08', ''),
(133, 'perla.rocha@mess.com.mx', 'perla.rocha', '412', 'Rocha Juárez Perla Sarahí ', 'perla.rocha@mess.com.mx', 8, 10, 0, '2023-01-24', 1, 1, '5555', '7', 'PLANTA', 'F', '3517973628-1', 'ROJP9709253G2', 'ROJP970925MSPCRR05', 'ORH+'),
(134, 'paola.martinez@mess.com.mx', 'paola.martinez', '413', 'Martínez Gutiérrez Paola de Jesús ', 'paola.martinez@mess.com.mx', 8, 10, 40, '2023-02-01', 1, 1, '5555', '8', 'PLANTA', 'F', '4113950808-3', 'MAGP9504064D7', 'MAGP950406MSPRTL01', ''),
(135, 'joyce@mess.com.mx', 'joyce', '414', 'Cabello Corona Joyce ', 'joyce@mess.com.mx', 43, 4, 29, '2023-02-01', 1, 1, '123', '9', 'PLANTA', 'F', '5815954623-1', 'CACJ950224U83', 'CACJ950224MQTBRY01', 'ARH+'),
(136, 'javier.castillo@mess.com.mx', 'javier.castillo', '415', 'Castillo Marquez Javier Alberto ', 'javier.castillo@mess.com.mx', 43, 4, 29, '2023-02-07', 1, 1, '329', '0', 'PLANTA', 'M', '1216952584-2', 'CAMJ9511178DA', 'CAMJ951117HMSSRV03', 'ORH+'),
(137, 'cuentasporcobrar@mess.com.mx', 'cuentasporcobrar', '416', 'Trejo Sánchez María Guadalupe ', 'cuentasporcobrar@mess.com.mx', 21, 4, 1, '2023-02-07', 1, 1, '2', '0', 'PLANTA', 'F', '1497790951-5', 'TESG7910139C7', 'TESG791013MQTRND08', 'ORH+'),
(138, 'silvia.figueroa@mess.com.mx', 'silvia.figueroa', '420', 'Figueroa Camacho Silvia Cristina ', 'silvia.figueroa@mess.com.mx', 8, 8, 0, '2023-03-01', 1, 1, '11', '0', 'PLANTA', 'F', '3292717584-7', 'FICS710502D68', 'FICS710502MCLGML06', ''),
(139, 'jose.morales@mess.com.mx', 'jose.morales', '424', 'Morales Gómez José Yoyarib ', 'jose.morales@mess.com.mx', 38, 4, 29, '2023-03-16', 1, 1, '123', '0', 'PLANTA', 'M', '2516984080-5', 'MOGY980103TX8', 'MOGY980103HQTRNY02', 'ARH-'),
(140, 'hernan.lopez@mess.com.mx', 'hernan.lopez', '426', 'López Hernández Hernán ', 'hernan.lopez@mess.com.mx', 11, 4, 7, '2023-03-27', 1, 1, '10', '0', 'PLANTA', 'M', '1411966378-0', 'LOHH960122BT1', 'LOHH960122HQTPRR06', ''),
(141, 'eduardo.corral@mess.com.mx', 'eduardo.corral', '427', 'Corral Olvera Eduardo Alejandro ', 'eduardo.corral@mess.com.mx', 38, 1, 36, '2023-04-03', 1, 1, '206', '0', 'PLANTA', 'M', '5115000227-0', 'COOE000409UQ0', 'COOE000409HASRLDA1', 'ARH+'),
(142, 'ivan.martinez@mess.com.mx', 'ivan.martinez', '428', 'Martínez Arias Ivan ', 'ivan.martinez@mess.com.mx', 43, 4, 18, '2023-04-03', 1, 3, '161', '10', 'PLANTA', 'M', '314986573-9', 'MAAI980909HK1', 'MAAI980909HMNRRV08', 'ARH+'),
(143, 'itan.nava@mess.com.mx', 'itan.nava', '432', 'Nava Díaz Itan Uriel ', 'itan.nava@mess.com.mx', 38, 4, 29, '2023-05-08', 1, 1, '123', '0', 'PLANTA', 'M', '913018696-9', 'NADI0110032S3', 'NADI11003HDFVZTA5', ''),
(144, 'lilibeth.herrera@mess.com.mx', 'lilibeth.herrera', '435', 'Herrera Carreto Lilibeth Miren ', 'lilibeth.herrera@mess.com.mx', 4, 4, 21, '2023-05-16', 1, 1, '14', '0', 'PLANTA', 'F', '322976976-9', 'HECL970828MD6', 'HECL970828MGRRRL01', ''),
(145, 'juana.cruz@mess.com.mx', 'juana.cruz', '437', 'Cruz Estrella María Juana', 'juana.cruz@mess.com.mx', 8, 4, 0, '2023-05-16', 1, 1, '14', '0', 'PLANTA', 'F', '1413976383-4', 'CUEJ971008QF7', 'CUEJ971008MQTRSN07', ''),
(146, 'yair.gomez@mess.com.mx', 'yair.gomez', '438', 'Gomez Carranza Jasiel Yair', 'yair.gomez@mess.com.mx', 38, 4, 3, '2023-05-16', 1, 1, '189', '0', 'PLANTA', 'M', '215922978-6', 'GOCJ920723K99', 'GOCJ920723HHGMRS04', ''),
(147, 'christian.resendiz@mess.com.mx', 'christian.resendiz', '439', 'Resendiz Valadez Christian Erick', 'christian.resendiz@mess.com.mx', 6, 8, 40, '2023-06-05', 1, 1, '11', '0', 'PLANTA', 'M', '4303852713-1', 'REVC850629PT6', 'REVC850629HNLSLH09', ''),
(148, 'armando.nolasco@mess.com.mx', 'armando.nolasco', '440', 'Nolasco Rodríguez Armando  ', 'armando.nolasco@mess.com.mx', 41, 4, 29, '2023-06-12', 1, 1, '414', '0', 'PLANTA', 'M', '3291739634-6', 'NORA730619K79', 'NORA730619HCLLDR02', ''),
(149, 'marco.mandujano@mess.com.mx', 'marco.mandujano', '442', 'Mandujano Gómez Marco Antonio ', 'marco.mandujano@mess.com.mx', 43, 4, 29, '2023-06-26', 1, 1, '123', '0', 'PLANTA', 'M', '1412934765-5', 'MAGM931006LY7', 'MAGM931006HQTNMR05', ''),
(150, 'jesus.ruiz@mess.com.mx', 'jesus.ruiz', '446', 'Ruiz Samano Jesús Antonio ', 'jesus.ruiz@mess.com.mx', 38, 4, 36, '2023-07-06', 1, 1, '11', '0', 'PLANTA', 'M', '2516999715-9', 'RUSJ991121CH1', 'RUSJ991121HTSZMS00', ''),
(151, 'jesus.sanchez@mess.com.mx', 'jesus.sanchez', '447', 'Sánchez Chávez Jesús', 'jesus.sanchez@mess.com.mx', 38, 4, 36, '2023-07-06', 1, 1, '11', '0', 'PLANTA', 'M', '7216000346-2', 'SACJ000104375', 'SACJ000104HTSNHSA4', 'ARH+'),
(152, 'jaime.salinas@mess.com.mx', 'jaime.salinas', '448', 'Salinas Gómez Jaime', 'jaime.salinas@mess.com.mx', 38, 4, 3, '2023-07-06', 1, 1, '189', '0', 'PLANTA', 'M', '613003345-6', 'SAGJ000603NI7', 'SAGJ000603HCSLMMA1', ''),
(153, 'aaron.sanchez@mess.com.mx', 'aaron.sanchez', '451', 'Sánchez Nataren Jorge Aaron', 'aaron.sanchez@mess.com.mx', 38, 4, 5, '2023-07-18', 1, 1, '226', '0', 'PLANTA', 'M', '613004788-6', 'SAGJ000603NI7', 'SAGJ000603HCSLMMA1', ''),
(154, 'elena.mendez@mess.com.mx', '2milhoras', '452', 'Méndez Salazar María Elena', 'elena.mendez@mess.com.mx', 8, 4, 0, '2023-07-24', 1, 1, '264', '0', 'PLANTA', 'F', '5308873382-8', 'MESE870221JW9', 'MESE870221MDFNLL09', ''),
(155, 'beatriz.Portilla@mess.com.mx', 'beatriz.Portilla', '453', 'Portilla Mena Beatriz Gabriela', 'beatriz.Portilla@mess.com.mx', 8, 5, 0, '2023-08-01', 1, 1, '324', '0', 'PLANTA', 'F', '1212925404-6', 'POMB9206264F0', 'POMB920626MPLRNT02', ''),
(156, 'leticia.vazquez@mess.com.mx', 'leticia.vazquez', '455', 'Vazquez Granados Leticia', 'leticia.vazquez@mess.com.mx', 6, 4, 40, '2023-08-09', 1, 1, '324', '0', 'PLANTA', 'F', '3903820897-9', 'VAGL820904GH4', 'VAGL820904MMCZRT06', ''),
(157, 'facturacion2@mess.com.mx', 'Mess2025H', '456', 'Arroyo Hernández Hector', 'facturacion2@mess.com.mx', 3, 4, 1, '2023-09-04', 1, 1, '2', '0', 'PLANTA', 'M', '1511901464-3', 'AOHH900422194', 'AOHH900422HMSRRC08', ''),
(158, 'adal.beltran@mess.com.mx', 'adal.beltran', '457', 'Beltrán Montaño Cesar Adal', 'adal.beltran@mess.com.mx', 43, 4, 21, '2023-09-04', 1, 1, '14', '0', 'PLANTA', 'M', '1413985617-4', 'BEMC9804023GO', 'BEMC980402HQTLNS01', ''),
(159, 'mayan.nunez@mess.com.mx', 'mayan.nunez', '458', 'Nuñez Rubert Mayan Arianna ', 'mayan.nunez@mess.com.mx', 8, 10, 0, '2023-10-02', 1, 1, '85', '0', 'PLANTA', 'F', '4111933754-5', 'NURM930829IV2', 'NURM930829MSPXBY03', ''),
(160, 'bryan.landeros@mess.com.mx', 'bryan.landeros', '459', 'Landeros Alamilla Bryan Modesto ', 'bryan.landeros@mess.com.mx', 38, 4, 36, '2023-10-10', 1, 1, '45', '0', 'PLANTA', 'M', '1413986036-6', 'LAAB981010LB3', 'LAAB981010HDFNLR05', ''),
(161, 'maricarmen.cordova@mess.com.mx', 'Maryka07.', '460', 'Cordova Gamboa Maricarmen ', 'maricarmen.cordova@mess.com.mx', 8, 10, 0, '2023-10-25', 1, 1, '85', '0', 'PLANTA', 'F', '4112934410-1', 'COGM930207SF8', 'COGM930207MSPRMR06', ''),
(162, '0', 'CUVL970304AH6', '464', 'Cruz Ventura Luzmayereth ', '0', 16, 4, 29, '2023-11-16', 1, 1, '0', '0', 'PLANTA', 'F', '1412975057-7', 'CUVL970304AH6', 'CUVL970304MQTRNZ09', ''),
(163, 'eber.sarabia@mess.com.mx', 'SAVE020921TM3', '466', 'Sarabia Valdez Eber Emiliano ', 'eber.sarabia@mess.com.mx', 13, 8, 12, '2023-12-01', 1, 1, '11', '0', 'PLANTA', 'M', '28170289350', 'SAVE020921TM3', 'SAVE020921HNLRLBA2', 'ORH+'),
(164, 'hector.ortiz@mess.com.mx', 'hector.ortiz', '467', 'Ortiz Santiago Hector Uriel ', 'hector.ortiz@mess.com.mx', 38, 4, 22, '2023-12-04', 1, 1, '212', '0', 'PLANTA', 'M', '41129441477', 'OISH9407119F3', 'OISH940711HSPRNC08', 'ORH+'),
(165, 'gregorio.garcia@mess.com.mx', 'gregorio.garcia', '468', 'Garcia Baeza Juan Gregorio ', 'gregorio.garcia@mess.com.mx', 38, 4, 13, '2023-12-04', 1, 1, '45', '0', 'PLANTA', 'M', '35189949627', 'GABJ9911097M7', 'GABJ991109HGTRZN03', ''),
(166, 'carlos.robledo@mess.com.mx', 'carlos.robledo', '469', 'Robledo Calvillo  Carlos Manuel Efraín ', 'carlos.robledo@mess.com.mx', 6, 8, 40, '2023-12-12', 1, 1, '0', '0', 'PLANTA', 'M', '32119236159', 'ROCC920705LY2', 'ROCC920705HCLBLR06', 'ARH+'),
(167, 'alejandro.palomino@mess.com.mx', 'alejandro.palomino', '470', 'Palomino García Alejandro Ulises ', 'alejandro.palomino@mess.com.mx', 43, 10, 9, '2023-12-18', 1, 1, '298', '0', 'PLANTA', 'M', '24139812812', 'PAGA980216UK3', 'PAGA980216HSPLRL05', 'ORH-'),
(168, 'zaira.balderrama@mess.com.mx', 'zaira.balderrama', '472', 'Balderrama Ramirez Zaira Jimena ', 'zaira.balderrama@mess.com.mx', 12, 4, 29, '2024-01-11', 1, 1, '123', '0', 'PLANTA', 'F', '5220183635', 'BARZ010108B37', 'BARZO10108MQTLMRA6', ''),
(169, 'beatriz.martinez@mess.com.mx', 'beatriz.martinez', '474', 'Martínez Beltrán Beatriz ', 'beatriz.martinez@mess.com.mx', 8, 4, 33, '2024-02-01', 1, 1, '71', '0', 'PLANTA', 'F', '38169503026', 'MABB951207HQ3', 'MABB951207MGRRLT05', 'ORH-'),
(170, 'melani.hernandez@mess.com.mx', 'melani.hernandez', '475', 'Hernández Gutiérrez Melani Nicte ', 'melani.hernandez@mess.com.mx', 6, 2, 40, '2024-02-01', 1, 1, '37', '0', 'PLANTA', 'F', '45119434079', 'HEGM9401022V8', 'HEGM940102MDFRTL11', 'ORH+'),
(171, 'ossiel.gallardo@mess.com.mx', 'ossiel.gallardo', '478', 'Gallardo Cruz Ossiel de Jesús ', 'ossiel.gallardo@mess.com.mx', 38, 4, 5, '2024-02-01', 1, 1, '226', '0', 'PLANTA', 'M', '78169958325', 'GACO990522CBA', 'GACO990522HVZLRS09', 'ORH+'),
(172, 'maricruz.hernandez@mess.com.mx', 'maricruz.hernandez', '479', 'Hernández Muñoz Maricruz de Jesús ', 'maricruz.hernandez@mess.com.mx', 8, 1, 0, '2024-02-12', 1, 1, '0', '0', 'PLANTA', 'F', '51159720633', 'HEMM9708184D0', 'HEMM970818MASRXR07', 'ORH+'),
(173, 'cotizaciones1@mess.com.mx', 'cotizaciones1', '480', 'Canizales Torres Abigail ', 'abigail@mess.com.mx', 8, 4, 40, '2024-02-12', 1, 1, '324', '0', 'PLANTA', 'F', '38190133702', 'CATA0101144J1', 'CATA010114MVZNRBA8', 'ORH+'),
(174, 'Braulio.jaime@mess.com.mx', 'Braulio.jaime', '483', 'Jaime Sánchez  Braulio', 'Braulio.jaime@mess.com.mx', 38, 4, 18, '2024-03-16', 1, 1, '0', '0', 'TEMPORAL', 'M', '26190209903', 'JASB0202204B1', 'JASB020220HQTMNRA0', ''),
(175, 'monserrat.lopez@mess.com.mx', 'monserrat.lopez', '484', 'Dulce Monserrat López Elizondo ', 'monserrat.lopez@mess.com.mx', 8, 4, 40, '2024-03-19', 1, 1, '263', '0', 'PLANTA', 'F', '73150090501', 'LOED000912S52', 'LOED000912MQTPLLA9', 'ORH+'),
(176, 'felipe.martinez@mess.com.mx', 'felipe.martinez', '485', 'Martínez Moriel Felipe ', 'felipe.martinez@mess.com.mx', 38, 4, 28, '2024-05-20', 1, 1, '123', '0', 'TEMPORAL', 'M', '1408891936-1', 'MAMF890327D83', 'MAMF890327HSLRRL02', ''),
(177, 'Gerardo.lopez@mess.com.mx', 'Gerardo.lopez', '486', 'López Morales Gerardo Francisco ', 'Gerardo.lopez@mess.com.mx', 38, 4, 22, '2024-04-17', 1, 1, '0', '0', 'TEMPORAL', 'M', '1817998359-0', 'LOMG990829HL5', 'LOMG990829HCSPRR03', 'ARH+'),
(178, 'angeles.pacheco@mess.com.mx', 'angeles.pacheco', '487', 'Pacheco Pacheco María de los Angeles ', 'angeles.pacheco@mess.com.mx', 38, 4, 31, '2024-05-02', 1, 1, '212', '0', 'TEMPORAL', 'F', '27170242807', 'PAPA020107DP5', 'PAPA020107MQTCCNA4', ''),
(179, 'ramiro.garcia@mess.com.mx', 'ramiro.garcia', '488', 'Ramiro García Noguez ', 'ramiro.garcia@mess.com.mx', 6, 4, 0, '2024-05-06', 1, 1, '324', '0', 'TEMPORAL', 'M', '14078306041', 'GANR830627U41', 'GANR830627HQTRGM09', 'ORH+'),
(180, 'atencion.sfg@mess.com.mx', '2024mayo', '489', 'Maria Fernanda Hurtado García ', 'atencion.sfg@mess.com.mx', 8, 4, 36, '2024-05-13', 1, 1, '45', '0', 'TEMPORAL', 'F', '14129209285', 'HUGF920813IP1', 'HUGF920813MQTRRR08', ''),
(181, 'gamaliel@mess.com.mx', 'gamaliel', '490', 'Lugo Rojas Gamaliel', 'gamaliel@mess.com.mx', 38, 4, 28, '2024-05-20', 1, 1, '329', '0', 'TEMPORAL', 'M', '64170240143', 'LURG020727K66', 'LURG020727HHGGJMA8', ''),
(182, 'angel.bonilla@mess.com.mx', 'angel.bonilla', '491', 'Angel Adolfo Bonilla Meza ', 'angel.bonilla@mess.com.mx', 38, 4, 21, '2024-06-10', 1, 1, '14', '0', 'TEMPORAL', 'M', '75170251302', 'BOMA020530GC6', 'BOMA020530HTLNZNA4', 'ARH+'),
(183, 'logistica2@mess.com.mx', 'logistica2', '492', 'Edgar Jonathan Maya Sosa', 'logistica2@mess.com.mx', 33, 4, 25, '2024-06-17', 1, 1, '42', '0', 'PLANTA', 'M', '14008363906', 'MASE831217T83', 'MASE831217HQTYSD15', ''),
(184, 'tomas@mess.com.mx', 'Gudi2312', '494', 'Pérez Gudiño Tomás ', 'tomas@mess.com.mx', 34, 4, 21, '2024-07-03', 1, 1, '14', '0', '0', 'M', '25170170788', 'GUPT011223CV5', 'GUPT011223HQTDRMA7', ''),
(185, '0', 'SAGF0110116F7', '495', 'Sánchez García María Fernanda ', '0', 43, 4, 15, '2024-07-03', 1, 1, '212', '0', '0', 'F', '19170170385', 'SAGF0110116F7', 'SAGF011011MQTNRRA7', 'ORH+'),
(186, 'susana.lopez@mess.com.mx', '5u54n4.2205', '496', 'López Ramírez Susana', 'susana.lopez@mess.com.mx', 8, 1, 40, '2024-07-16', 1, 1, '37', '0', '0', 'F', '', '', '', ''),
(187, 'andres.mateos@mess.com.mx', 'andres.mateos', '497', 'Luis Andres Mateo Santiago.', 'andres.mateos@mess.com.mx', 38, 4, 27, '2024-07-22', 1, 1, '0', '0', '0', 'M', '', '', '', ''),
(188, 'sebastian.angulo@mess.com.mx', 'sebastianmess2024', '498', 'Sebastián Angulo González', 'sebastian.angulo@mess.com.mx', 38, 4, 16, '2024-07-24', 1, 1, '341', '0', '0', 'M', '', '', '', ''),
(189, 'joseluis.tejeda@mess.com.mx', 'joseluis.tejeda', '499', 'José Luis Tejeda Brito', 'joseluis.tejeda@mess.com.mx', 38, 4, 18, '2024-08-01', 1, 1, '161', '0', '0', 'F', '13109401177', 'TEBL9403316MA', 'TEMBL940331HHGJRS06', ''),
(190, 'ariadna.galindo@mess.com.mx', 'ariadna.galindo', '500', 'Ariadna Adinai Galindo Gaytan', 'ariadna.galindo@mess.com.mx', 32, 4, 25, '2024-08-01', 1, 1, '42', '0', '0', 'F', '', '', '', ''),
(191, 'yessica.hernandez@mess.com.mx', 'yessica.hernandez', '501', 'Yessica Hernández Esparza ', 'yessica.hernandez@mess.com.mx', 6, 1, 40, '2024-01-01', 1, 1, '37', '0', '0', 'M', '', '', '', ''),
(192, 'edgar.alvarez@mess.com.mx', 'edgar.alvarez', '502', 'Edgar Emmanuel Alvarez Gómez ', 'edgar.alvarez@mess.com.mx', 6, 10, 0, '2024-09-04', 1, 1, '0', '0', '0', 'M', '2169901044', 'AAGE991217DX2', 'AAGE991217HSPLMD07', ''),
(193, 'jose.porras@mess.com.mx', 'jose.porras', '503', 'José María Porras Contreras', 'jose.porras@mess.com.mx', 38, 4, 15, '2024-09-09', 1, 1, '212', '0', '0', 'M', '5199831933', 'POCM98022LX4', 'POCM980227HVZRNR08', 'ARH+'),
(194, 'socorro.gutierrez@mess.com.mx', 'socorro.gutierrez', '504', 'María del Socorro Gutierrez Pérez ', 'socorro.gutierrez@mess.com.mx', 8, 10, 0, '2024-09-09', 1, 1, '0', '0', '0', 'M', '41058408026', 'GUPS840627QX2', 'GUPS840627MSPTRC08', ''),
(195, 'fernanda.rodriguez@mess.com.mx', 'fernanda.rodriguez', '505', 'Sonia Fernanda Rodríguez Calvillo', 'fernanda.rodriguez@mess.com.mx', 8, 1, 40, '2024-01-01', 1, 1, '37', '0', '0', 'M', '', '', '', ''),
(196, 'dafne@mess.com.mx', 'dafne', '506', 'Dafne Areli Sil Heredia ', 'dafne@mess.com.mx', 38, 2, 8, '2024-01-01', 1, 1, '0', '0', '0', 'M', '', '', '', ''),
(197, '0', '0', '507', 'Francisco Javier Velazquez Bocanegra ', '0', 38, 8, 6, '2024-01-01', 1, 1, '11', '0', '0', 'M', '', '', '', ''),
(198, 'calidad.aux@mess.com.mx', 'calidad.aux', '508', 'Daniel Aaron Olvera Guerrero', 'calidad.aux@mess.com.mx', 36, 4, 4, '2024-10-21', 1, 1, '5', '0', '0', 'M', '35190029104', 'OEGD0006217G0', 'OEGD000621HQTLRNA5', 'ORH+'),
(200, 'ControlyMejoraMTS@mess.com.mx', 'PEFV810205CJ0', '516', 'Verónica Pérez Fuentes', 'ControlyMejoraMTS@mess.com.mx', 8, 4, 29, '2025-01-20', 1, 1, '414', '0', 'PLANTA', 'F', '14068110072', 'PEFV810205CJ0', 'PEFV810205MQTRNR01', 'ORH+'),
(201, 'hugo.soria@mess.com.mx', 'hugo.soria', '521', 'Hugo Ernesto Soria Mosqueda', 'hugo.soria@mess.com.mx', 66, 4, 41, '2025-02-04', 1, 3, '19', '0', 'PLANTA', 'M', '', '', '', '');

--
-- Indices de la tabla `cambio_llantas`
--
ALTER TABLE `cambio_llantas`
  ADD PRIMARY KEY (`id_servicio`);

--
-- Indices de la tabla `checklist`
--
ALTER TABLE `checklist`
  ADD PRIMARY KEY (`id_checklist`);

--
-- Indices de la tabla `checklist_asientos`
--
ALTER TABLE `checklist_asientos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_documentacion`
--
ALTER TABLE `checklist_documentacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_espejos_ventanas`
--
ALTER TABLE `checklist_espejos_ventanas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_estereos_aire`
--
ALTER TABLE `checklist_estereos_aire`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_faros`
--
ALTER TABLE `checklist_faros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_golpes_exterior`
--
ALTER TABLE `checklist_golpes_exterior`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_limpiaParabrisas`
--
ALTER TABLE `checklist_limpiaParabrisas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_limpieza`
--
ALTER TABLE `checklist_limpieza`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_llantas`
--
ALTER TABLE `checklist_llantas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_placas`
--
ALTER TABLE `checklist_placas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist_puertas_llave`
--
ALTER TABLE `checklist_puertas_llave`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `documentacion`
--
ALTER TABLE `documentacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD PRIMARY KEY (`id_foto`);

--
-- Indices de la tabla `hojalateria`
--
ALTER TABLE `hojalateria`
  ADD PRIMARY KEY (`id_servicio`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `kilometrajes`
--
ALTER TABLE `kilometrajes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id_manetenimiento`);

--
-- Indices de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD PRIMARY KEY (`id_prestamo`);

--
-- Indices de la tabla `siniestros`
--
ALTER TABLE `siniestros`
  ADD PRIMARY KEY (`id_siniestro`);

--
-- Indices de la tabla `tipos_fotos`
--
ALTER TABLE `tipos_fotos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cambio_llantas`
--
ALTER TABLE `cambio_llantas`
  MODIFY `id_servicio` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist`
--
ALTER TABLE `checklist`
  MODIFY `id_checklist` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_asientos`
--
ALTER TABLE `checklist_asientos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_documentacion`
--
ALTER TABLE `checklist_documentacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_espejos_ventanas`
--
ALTER TABLE `checklist_espejos_ventanas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_estereos_aire`
--
ALTER TABLE `checklist_estereos_aire`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_faros`
--
ALTER TABLE `checklist_faros`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_golpes_exterior`
--
ALTER TABLE `checklist_golpes_exterior`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_limpiaParabrisas`
--
ALTER TABLE `checklist_limpiaParabrisas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_limpieza`
--
ALTER TABLE `checklist_limpieza`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_llantas`
--
ALTER TABLE `checklist_llantas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_placas`
--
ALTER TABLE `checklist_placas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_puertas_llave`
--
ALTER TABLE `checklist_puertas_llave`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentacion`
--
ALTER TABLE `documentacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fotos`
--
ALTER TABLE `fotos`
  MODIFY `id_foto` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `hojalateria`
--
ALTER TABLE `hojalateria`
  MODIFY `id_servicio` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT de la tabla `kilometrajes`
--
ALTER TABLE `kilometrajes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id_manetenimiento` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id_prestamo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `siniestros`
--
ALTER TABLE `siniestros`
  MODIFY `id_siniestro` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_fotos`
--
ALTER TABLE `tipos_fotos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
