-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19-Mar-2026 às 21:14
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ipca-pw2`
--
CREATE DATABASE IF NOT EXISTS `ipca-pw2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ipca-pw2`;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cursos`
--

CREATE TABLE `cursos` (
  `ID` int(11) NOT NULL,
  `Nome` varchar(200) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `cursos`
--

INSERT INTO `cursos` (`ID`, `Nome`, `ativo`) VALUES
(1, 'Desenvolvimento Web e Multimédia', 1),
(2, 'Comércio Eletrónico', 1),
(3, 'Redes de Computadores', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `disciplinas`
--

CREATE TABLE `disciplinas` (
  `ID` int(11) NOT NULL,
  `Nome_disc` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `disciplinas`
--

INSERT INTO `disciplinas` (`ID`, `Nome_disc`) VALUES
(1, 'Matemática'),
(2, 'Programação WEB I'),
(3, 'Linguagens de Programação'),
(4, 'Português'),
(5, 'Base de Dados'),
(6, 'Redes e Comunicações');

-- --------------------------------------------------------

--
-- Estrutura da tabela `fichas_aluno`
--

CREATE TABLE `fichas_aluno` (
  `ID` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `morada` varchar(255) DEFAULT NULL,
  `curso_id` int(11) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `estado` enum('rascunho','submetida','aprovada','rejeitada') NOT NULL DEFAULT 'rascunho',
  `observacoes` text DEFAULT NULL,
  `validado_por` int(11) DEFAULT NULL,
  `data_validacao` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `fichas_aluno`
--

INSERT INTO `fichas_aluno` (`ID`, `user_id`, `nome`, `email`, `telefone`, `data_nascimento`, `morada`, `curso_id`, `foto`, `estado`, `observacoes`, `validado_por`, `data_validacao`, `criado_em`, `atualizado_em`) VALUES
(1, 3, 'Arielly Rodrigues', 'ariellymel01@gmail.com', '900000000', '2007-01-02', 'Teste', 1, 'aluno_3_1773610909.jpg', 'aprovada', '', 1, '2026-03-15 21:43:13', '2026-03-15 21:41:49', '2026-03-15 21:43:13');

-- --------------------------------------------------------

--
-- Estrutura da tabela `grupos`
--

CREATE TABLE `grupos` (
  `ID` int(11) NOT NULL,
  `GRUPO` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `grupos`
--

INSERT INTO `grupos` (`ID`, `GRUPO`) VALUES
(1, 'GESTOR'),
(2, 'FUNCIONARIO'),
(3, 'ALUNO');

-- --------------------------------------------------------

--
-- Estrutura da tabela `matriculas`
--

CREATE TABLE `matriculas` (
  `ID` int(11) NOT NULL,
  `ficha_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `ano_letivo` varchar(10) NOT NULL,
  `estado` enum('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `aprovado_por` int(11) DEFAULT NULL,
  `data_decisao` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `matriculas`
--

INSERT INTO `matriculas` (`ID`, `ficha_id`, `curso_id`, `ano_letivo`, `estado`, `observacoes`, `aprovado_por`, `data_decisao`, `criado_em`) VALUES
(1, 1, 1, '2025/2026', 'aprovada', '', 2, '2026-03-15 22:07:15', '2026-03-15 22:00:32');

-- --------------------------------------------------------

--
-- Estrutura da tabela `notas`
--

CREATE TABLE `notas` (
  `ID` int(11) NOT NULL,
  `pauta_id` int(11) NOT NULL,
  `ficha_id` int(11) NOT NULL,
  `nota` decimal(4,1) DEFAULT NULL,
  `observacoes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pautas`
--

CREATE TABLE `pautas` (
  `ID` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `ano_letivo` varchar(10) NOT NULL,
  `epoca` enum('Normal','Recurso','Especial') NOT NULL DEFAULT 'Normal',
  `criado_por` int(11) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `plano_estudos`
--

CREATE TABLE `plano_estudos` (
  `ID` int(11) NOT NULL,
  `CURSOS` int(11) NOT NULL,
  `DISCIPLINA` int(11) NOT NULL,
  `ano` tinyint(1) NOT NULL DEFAULT 1,
  `semestre` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `plano_estudos`
--

INSERT INTO `plano_estudos` (`ID`, `CURSOS`, `DISCIPLINA`, `ano`, `semestre`) VALUES
(1, 1, 1, 1, 1),
(2, 1, 2, 1, 1),
(3, 1, 3, 1, 2),
(4, 1, 4, 1, 2),
(5, 2, 3, 1, 1),
(6, 2, 5, 1, 2),
(8, 3, 1, 1, 2),
(7, 3, 6, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `login` varchar(100) NOT NULL,
  `pwd` varchar(255) NOT NULL,
  `grupo` int(11) NOT NULL,
  `numero_aluno` varchar(20) DEFAULT NULL COMMENT 'Só para grupo=3; atribuído automaticamente a partir de 500'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`ID`, `login`, `pwd`, `grupo`, `numero_aluno`) VALUES
(1, 'gestor', '23acdad81256c84b4c7f938f66c3c3b4', 1, NULL),
(2, 'funcionario', 'dcfddd4b2b2180b1adde3e30a20f3e68', 2, NULL),
(3, 'aluno1', '180a26a9aef9ee13cc3d2361feb43c5a', 3, '500'),
(4, 'aluno2', '180a26a9aef9ee13cc3d2361feb43c5a', 3, '501'),
(5, 'aluno3', '180a26a9aef9ee13cc3d2361feb43c5a', 3, '502');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `fichas_aluno`
--
ALTER TABLE `fichas_aluno`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_user` (`user_id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `validado_por` (`validado_por`);

--
-- Índices para tabela `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ficha_id` (`ficha_id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `aprovado_por` (`aprovado_por`);

--
-- Índices para tabela `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uniq_pauta_aluno` (`pauta_id`,`ficha_id`),
  ADD KEY `ficha_id` (`ficha_id`);

--
-- Índices para tabela `pautas`
--
ALTER TABLE `pautas`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices para tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uniq_curso_disc_sem` (`CURSOS`,`DISCIPLINA`,`ano`,`semestre`),
  ADD KEY `DISCIPLINA` (`DISCIPLINA`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_login` (`login`),
  ADD UNIQUE KEY `uq_numero_aluno` (`numero_aluno`),
  ADD KEY `grupo` (`grupo`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cursos`
--
ALTER TABLE `cursos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `fichas_aluno`
--
ALTER TABLE `fichas_aluno`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `grupos`
--
ALTER TABLE `grupos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `notas`
--
ALTER TABLE `notas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pautas`
--
ALTER TABLE `pautas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `fichas_aluno`
--
ALTER TABLE `fichas_aluno`
  ADD CONSTRAINT `fichas_aluno_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`),
  ADD CONSTRAINT `fichas_aluno_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`ID`),
  ADD CONSTRAINT `fichas_aluno_ibfk_3` FOREIGN KEY (`validado_por`) REFERENCES `users` (`ID`);

--
-- Limitadores para a tabela `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `matriculas_ibfk_1` FOREIGN KEY (`ficha_id`) REFERENCES `fichas_aluno` (`ID`),
  ADD CONSTRAINT `matriculas_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`ID`),
  ADD CONSTRAINT `matriculas_ibfk_3` FOREIGN KEY (`aprovado_por`) REFERENCES `users` (`ID`);

--
-- Limitadores para a tabela `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`pauta_id`) REFERENCES `pautas` (`ID`),
  ADD CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`ficha_id`) REFERENCES `fichas_aluno` (`ID`);

--
-- Limitadores para a tabela `pautas`
--
ALTER TABLE `pautas`
  ADD CONSTRAINT `pautas_ibfk_1` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`ID`),
  ADD CONSTRAINT `pautas_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `users` (`ID`);

--
-- Limitadores para a tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  ADD CONSTRAINT `plano_estudos_ibfk_1` FOREIGN KEY (`CURSOS`) REFERENCES `cursos` (`ID`),
  ADD CONSTRAINT `plano_estudos_ibfk_2` FOREIGN KEY (`DISCIPLINA`) REFERENCES `disciplinas` (`ID`);

--
-- Limitadores para a tabela `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`grupo`) REFERENCES `grupos` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
