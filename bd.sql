-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20241219.8e721911f0
-- https://www.phpmyadmin.net/
--
-- Хост: 192.168.30.23
-- Время создания: Мар 05 2026 г., 12:35
-- Версия сервера: 8.0.18
-- Версия PHP: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `maga`
--

-- --------------------------------------------------------

--
-- Структура таблицы `hr_requests`
--

CREATE TABLE `hr_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(20) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `position_title` varchar(100) NOT NULL,
  `store_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `hr_requests`
--

INSERT INTO `hr_requests` (`id`, `request_number`, `task_id`, `position_title`, `store_id`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, '#HR-12', NULL, 'Сантехник', 5, '2026-02-25', '2026-02-26', 'open', '2026-03-05 12:34:42'),
(2, '#HR-13', NULL, 'Электрик', 3, '2026-02-27', '2026-02-28', 'open', '2026-03-05 12:34:42');

-- --------------------------------------------------------

--
-- Структура таблицы `specialists`
--

CREATE TABLE `specialists` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `has_medical_book` tinyint(1) DEFAULT '0',
  `has_workwear` tinyint(1) DEFAULT '0',
  `has_car` tinyint(1) DEFAULT '0',
  `notes` text,
  `added_by` int(11) DEFAULT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `specialists`
--

INSERT INTO `specialists` (`id`, `full_name`, `specialization`, `phone`, `email`, `has_medical_book`, `has_workwear`, `has_car`, `notes`, `added_by`, `added_at`) VALUES
(1, 'Иванов Иван Иванович', 'Сантехник', '+7 (123) 456-78-90', 'ivanov@email.com', 1, 1, 0, NULL, 3, '2026-03-05 12:34:42');

-- --------------------------------------------------------

--
-- Структура таблицы `stores`
--

CREATE TABLE `stores` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `stores`
--

INSERT INTO `stores` (`id`, `name`) VALUES
(5, 'Восточный'),
(3, 'Западный'),
(1, 'Северный'),
(2, 'Центральный'),
(4, 'Южный');

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_number` varchar(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `category` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('draft','new','in_progress','awaiting_assignment','completed') DEFAULT 'draft',
  `description` text,
  `requirements` text,
  `store_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`id`, `task_number`, `title`, `category`, `start_date`, `end_date`, `status`, `description`, `requirements`, `store_id`, `created_by`, `created_at`) VALUES
(1, '#234', 'Уборка территории', 'Уборщик', '2026-02-20', '2026-02-22', 'in_progress', NULL, NULL, 1, 1, '2026-03-05 12:34:42'),
(2, '#235', 'Инвентаризация склада', 'Инвентаризатор', '2026-02-25', '2026-02-26', 'new', NULL, NULL, 2, 1, '2026-03-05 12:34:42'),
(3, '#236', 'Мелкий ремонт (сантехника)', 'Сантехник', '2026-02-27', '2026-02-28', 'new', NULL, NULL, 3, 1, '2026-03-05 12:34:42'),
(4, '#237', 'Клининг (генеральная уборка)', 'Клинер', '2026-02-23', '2026-02-24', 'awaiting_assignment', NULL, NULL, 4, 1, '2026-03-05 12:34:42'),
(5, '#238', 'Мелкий ремонт', 'Разнорабочий', '2026-02-27', '2026-02-28', 'draft', NULL, NULL, 1, 1, '2026-03-05 12:34:42');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('store_manager','office_manager','hr_specialist') NOT NULL,
  `store_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `store_id`) VALUES
(1, 'alex', 'pass', 'Александр', 'store_manager', 1),
(2, 'elena', 'pass', 'Елена', 'office_manager', NULL),
(3, 'maria', 'pass', 'Мария', 'hr_specialist', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `hr_requests`
--
ALTER TABLE `hr_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_number` (`request_number`),
  ADD UNIQUE KEY `task_id` (`task_id`),
  ADD KEY `store_id` (`store_id`);

--
-- Индексы таблицы `specialists`
--
ALTER TABLE `specialists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `added_by` (`added_by`);

--
-- Индексы таблицы `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_number` (`task_number`),
  ADD KEY `store_id` (`store_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `hr_requests`
--
ALTER TABLE `hr_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `specialists`
--
ALTER TABLE `specialists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `stores`
--
ALTER TABLE `stores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `hr_requests`
--
ALTER TABLE `hr_requests`
  ADD CONSTRAINT `hr_requests_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hr_requests_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `specialists`
--
ALTER TABLE `specialists`
  ADD CONSTRAINT `specialists_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
