-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 10:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cmrx`
--

-- --------------------------------------------------------

--
-- Table structure for table `diagnosis`
--

CREATE TABLE `diagnosis` (
  `MED_ID` int(11) DEFAULT NULL,
  `Report_Name` varchar(100) DEFAULT NULL,
  `Report_Date` date DEFAULT NULL,
  `Rep_File_Path` varchar(255) DEFAULT NULL,
  `Hospital_Serial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disease_history`
--

CREATE TABLE `disease_history` (
  `MED_ID` int(11) DEFAULT NULL,
  `Disease_Name` varchar(100) DEFAULT NULL,
  `Present_Condition` enum('Severe','Mild','Very Mild','Cured','Seasonal') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disease_history`
--

INSERT INTO `disease_history` (`MED_ID`, `Disease_Name`, `Present_Condition`) VALUES
(8, 'covid19', 'Seasonal'),
(8, 'hmpv', 'Very Mild'),
(8, 'sino_psychosis', 'Severe'),
(15, 'covid25', 'Cured'),
(8, 'humour', 'Severe'),
(8, 'Introvert', 'Mild'),
(17, 'Pa Bhanga', 'Very Mild'),
(17, 'Hath Bhanga', 'Severe'),
(19, 'Tuberculosis', 'Cured'),
(20, 'Fever', 'Cured'),
(23, 'Covid19', 'Cured'),
(8, 'Asthma', 'Seasonal'),
(24, 'Asthma', 'Cured'),
(25, 'Asthma', 'Mild'),
(8, 'corona 3.0', 'Severe'),
(27, 'Asthma', 'Seasonal'),
(23, 'Asthma', 'Very Mild'),
(23, 'humour', 'Cured'),
(23, 'Diabetes', 'Mild'),
(23, 'Diabetes', 'Mild'),
(23, 'Diabetes', 'Mild'),
(23, 'Diabetes', 'Mild'),
(23, 'Diabetes', 'Mild'),
(15, 'Anime Addiction', 'Severe'),
(15, 'Anime Addiction', 'Severe'),
(15, 'Mern Stack Addiction', 'Severe'),
(15, 'Mern Stack Addiction', 'Severe'),
(40, 'Asthma', 'Severe'),
(41, 'Asthma', 'Severe'),
(42, 'Asthma', 'Severe'),
(43, 'Asthma', 'Severe'),
(44, 'Asthma', 'Severe'),
(45, 'Asthma', 'Severe'),
(46, 'Asthma', 'Severe'),
(47, 'Asthma', 'Severe'),
(48, 'Asthma', 'Severe'),
(49, 'Asthma', 'Severe'),
(50, 'Asthma', 'Severe'),
(51, 'Asthma', 'Severe'),
(6, 'Asthma', 'Mild');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_details`
--

CREATE TABLE `doctor_details` (
  `DOC_ID` int(11) NOT NULL,
  `Hospital_Serial` int(11) DEFAULT NULL,
  `Department_ID` int(11) DEFAULT NULL,
  `Doc_Name` varchar(100) DEFAULT NULL,
  `Certification` varchar(255) DEFAULT NULL,
  `doc_pass` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_details`
--

INSERT INTO `doctor_details` (`DOC_ID`, `Hospital_Serial`, `Department_ID`, `Doc_Name`, `Certification`, `doc_pass`) VALUES
(0, NULL, NULL, '', '', '$2y$10$avo4S'),
(1, 1, 101, 'Dr. Anichur Rahman', 'FCPSINCSE', '123123'),
(2, 1, 105, 'H. M. Ahsan Habib Kushal', 'Msc in Bsc', '007007'),
(3, 1, 103, 'Bonna', 'FCPS in cse', '655655'),
(110, 1, 104, 'Juthi Roy', 'FCPS', '777777'),
(1001, 1, 104, 'Mehenaz Alam', 'FCPS in IIT', '888888'),
(1007, 1, 102, 'Aditya Rajbongshi', 'MBBS in IIT', '123456'),
(2007, 1, 101, 'Aysha akter', 'bsc in mbbs', '1111'),
(2008, 2, 105, 'Dr. Salimullah Khan', 'None', '12345678'),
(4555, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4556, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4589, 1, 102, 'AlexandraChen', 'Board Certified Internal Medicine', 'NeuroScience'),
(4590, 1, 102, 'AlexandraChen', 'Board Certified Internal Medicine', 'NeuroScience'),
(4591, 1, 102, 'Alexandra', 'Board Certified Internal Medicine', 'NeuroScience'),
(4592, 1, 102, 'Alexandra', 'Board Certified Internal Medicine', 'NeuroScience'),
(4593, 1, 102, 'Alexandra', 'Board Certified Internal Medicine', 'NeuroScience'),
(4594, 1, 102, 'Alexandra', 'Board Certified Internal Medicine', 'NeuroScience'),
(4595, 1, 102, 'Alexandra', 'Board Certified Internal Medicine', 'NeuroScience'),
(4596, 1, 102, 'Alexandra', 'Board Certified Internal Medicine', 'NeuroScience'),
(4597, 1, 102, 'Alexandra', 'Board Certified Internal Medicine', 'NeuroScience'),
(4598, 1, 102, 'Alexandra', 'Board Certified Internal Medicine', 'NeuroScience'),
(4599, 1, 102, 'Shahidul Alam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4600, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4601, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4602, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4603, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4604, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4605, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4606, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(4777, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(5777, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience'),
(6776, 1, 103, 'Md. Shariful Islam', 'Board Certified Internal Surgeon', 'NeuroScience'),
(7667, 1, 104, 'Ayesha Akter', 'Board Certified External Surgeon', 'NeuroScience'),
(7669, 1, 104, 'Engineer Ayesha Akter', 'Board Certified External Surgeon', 'NeuroScience'),
(7776, 1, 102, 'Shahidul Islam', 'Board Certified Internal Medicine', 'NeuroScience');

-- --------------------------------------------------------

--
-- Table structure for table `global_departments`
--

CREATE TABLE `global_departments` (
  `Department_ID` int(11) NOT NULL,
  `Department_Name` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `global_departments`
--

INSERT INTO `global_departments` (`Department_ID`, `Department_Name`) VALUES
(101, 'Cardiology'),
(105, 'Gastrology'),
(104, 'Nephrology'),
(102, 'Neurology'),
(103, 'Pathology');

-- --------------------------------------------------------

--
-- Table structure for table `hospital`
--

CREATE TABLE `hospital` (
  `Hospital_Serial` int(11) NOT NULL,
  `Hospital_Name` varchar(120) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Registration_No` varchar(50) DEFAULT NULL,
  `acc_password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital`
--

INSERT INTO `hospital` (`Hospital_Serial`, `Hospital_Name`, `Address`, `Registration_No`, `acc_password`) VALUES
(1, 'Addeen', 'Mogbazar,Dhaka', 'H100', '1111'),
(2, 'Habibio Care', 'Niribili, Dhaka', 'H200', '$2y$10$JP..v.AYCo.TyB5V6Qkp3e2S4hmXvK0BuT.t5Z4HwKOkqw6zNk4q6');

-- --------------------------------------------------------

--
-- Table structure for table `hospital_departments`
--

CREATE TABLE `hospital_departments` (
  `Hospital_Serial` int(11) NOT NULL,
  `Department_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_address`
--

CREATE TABLE `patient_address` (
  `MED_ID` int(11) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_address`
--

INSERT INTO `patient_address` (`MED_ID`, `Address`) VALUES
(1, 'East Goran,Khilgaon,Dhaka'),
(2, 'East Goran,Khilgaon,Dhaka'),
(3, 'East Rampura,Dhaka'),
(4, '70/3 Dokkhingaon, bashaboo'),
(5, 'East Goran,Khilgaon,Dhaka'),
(6, 'sdfsdfsdfsdf'),
(7, 'Janina'),
(8, 'sdfasdfasdfas'),
(9, 'Pabna'),
(10, 'East Goran,Khilgaon,Dhaka'),
(11, 'fgfgfg'),
(12, 'sdfsdf'),
(13, '70/3 Dokkhingaon, basao'),
(14, 'asdasd'),
(15, 'skdjfhkljasdf'),
(16, '22/B Niribili, Savar'),
(17, 'Banglamotor, Dhaka'),
(18, 'sdfasdfkjlasdf'),
(19, 'Hazipara, Rampura'),
(20, 'Chowdhury Para, Dhaka'),
(21, 'Mohakhali'),
(22, 'Mohakhali'),
(23, 'dhaka'),
(24, 'Kopakhi'),
(25, 'Banglamotor, Dhaka'),
(26, 'sadarpur'),
(27, 'Howladerbari'),
(28, NULL),
(29, NULL),
(30, NULL),
(31, NULL),
(32, 'Bhanga, Faridpur'),
(33, 'Bhanga, Faridpur'),
(34, 'dhaka'),
(35, 'Bhanga, Faridpur'),
(36, 'Baker Street'),
(37, 'Baker Street'),
(38, 'Baker Street'),
(39, 'Baker Street'),
(40, 'Baker Street'),
(41, 'Baker Street'),
(42, 'Baker Street'),
(43, 'Baker Street'),
(44, 'Baker Street'),
(45, 'Baker Street'),
(46, 'Baker Street'),
(47, 'Niribili'),
(48, 'Niribili'),
(49, 'Niribili'),
(50, 'Niribili'),
(51, 'Niribili');

-- --------------------------------------------------------

--
-- Table structure for table `patient_allergies`
--

CREATE TABLE `patient_allergies` (
  `MED_ID` int(11) DEFAULT NULL,
  `Allergy_Name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_allergies`
--

INSERT INTO `patient_allergies` (`MED_ID`, `Allergy_Name`) VALUES
(8, 'Dust Allergy'),
(8, 'Chingri Allergy'),
(15, 'Dust Allergy'),
(8, 'bolod'),
(17, 'Women'),
(19, 'Dust Allergy'),
(8, 'Flue'),
(8, 'Bibaho'),
(20, 'Dust Allergy'),
(8, 'begun allergi'),
(24, 'Dust Allergy'),
(25, 'Dust Allergy'),
(26, 'Dust Allergy'),
(27, 'Dust Allergy'),
(15, 'Dust Allergy'),
(15, 'Hilsha Allergy'),
(40, 'Dust Allergy'),
(41, 'Dust Allergy'),
(42, 'Dust Allergy'),
(43, 'Dust Allergy'),
(44, 'Dust Allergy'),
(45, 'Dust Allergy'),
(46, 'Dust Allergy'),
(47, 'Dust Allergy'),
(48, 'Dust Allergy'),
(49, 'Dust Allergy'),
(50, 'Dust Allergy'),
(51, 'Dust Allergy'),
(6, 'Dust Allergy'),
(23, 'Pfizer');

-- --------------------------------------------------------

--
-- Table structure for table `patient_details`
--

CREATE TABLE `patient_details` (
  `MED_ID` int(11) NOT NULL,
  `Name` varchar(80) DEFAULT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `DOB` date DEFAULT NULL CHECK (`DOB` < '2025-02-09'),
  `Blood_Group` varchar(3) DEFAULT NULL,
  `acc_pass` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_details`
--

INSERT INTO `patient_details` (`MED_ID`, `Name`, `Gender`, `DOB`, `Blood_Group`, `acc_pass`) VALUES
(1, 'Adnan Hassan', NULL, '2003-11-20', 'A+', '$2y$10$lWYeh'),
(2, 'Adnan Hassan', NULL, '2003-11-20', 'A+', '$2y$10$7sMF1'),
(3, 'Ahsan Habib', NULL, '2003-11-20', 'A+', '$2y$10$EXjBn'),
(4, 'Kushal', NULL, '2002-06-14', 'B+', 'Kushal2888'),
(5, 'Hama', NULL, '2024-05-22', 'AB+', 'Bilai1234'),
(6, 'Suriaya', NULL, '2024-08-26', 'B+', '125125'),
(7, 'Apurba', 'Female', '2024-11-14', 'O-', '145145'),
(8, 'Thamina Khurshed Rimjhim', 'Female', '2001-08-21', 'A+', '999999'),
(9, 'Sovnom', 'Female', '2004-09-14', 'B+', 'paglapagla'),
(10, 'Adnan', 'Male', '2002-11-21', 'A+', '12341234'),
(11, 'fgfg', 'Male', '2024-10-01', 'A-', '111'),
(12, 'klsdfj;asdf', 'Female', '2024-09-13', 'A-', 'qqqqq'),
(13, 'mastan', 'Female', '2024-10-11', 'B-', '3453534345'),
(14, 'asdsd', 'Female', '2024-11-06', 'B+', 'asdasd'),
(15, 'Suraiya Akter', 'Female', '2023-11-16', 'A+', '655655'),
(16, 'Dipanjali Kundu', 'Female', '1994-06-15', 'A+', '877877'),
(17, 'Amartya Chowdhury', 'Male', '2001-08-06', 'A+', '555555'),
(18, 'sdklfhllksdjfk', 'Male', '2025-02-04', 'A+', '332323'),
(19, 'Rafiul Islam', 'Male', '2001-06-05', 'O+', '1212'),
(20, 'Shakib Howlader', 'Male', '2006-01-06', 'O+', '123456789'),
(21, 'Homayra Heme', 'Female', '2024-11-18', 'A-', '123456'),
(22, 'Heme Homayra', 'Female', '2024-10-14', 'A-', '888888'),
(23, 'H. M. Ahsan Habib Kushal', 'Male', '2000-07-16', 'B+', '123456'),
(24, 'Akib hasan', 'Male', '2003-09-03', 'O+', '123456'),
(25, 'adnan hasan', 'Male', '2000-11-21', 'B+', '12341234'),
(26, 'Md. Tanvir Ahmed', 'Male', '2011-08-25', 'B-', '5555'),
(27, 'Shupti', 'Female', '2007-02-20', 'B+', '11199'),
(28, NULL, NULL, NULL, NULL, NULL),
(29, NULL, NULL, NULL, NULL, NULL),
(30, NULL, NULL, NULL, NULL, NULL),
(31, NULL, NULL, NULL, NULL, NULL),
(32, 'Kohinoor Akter', 'Female', '1978-12-16', 'B+', '123456'),
(33, 'Kohinoor Akter', 'Female', '1978-12-16', 'B+', '123456789'),
(34, 'Esparanza', 'Female', '2002-07-18', 'B+', 'Kushal'),
(35, 'Esparandaa', 'Male', '2003-02-02', 'AB+', '123456'),
(36, 'Kushal Ahsan', 'Male', '2002-07-16', 'B+', 'BakerStreet'),
(37, 'KushalAhsan', 'Male', '2002-07-16', 'B+', 'BakerStreet'),
(38, 'KushalAhsan', 'Male', '2022-07-16', 'B+', 'BakerStreet'),
(39, 'KushalAhsan', 'Male', '2022-07-16', 'B+', 'BakerStreet'),
(40, 'KushalAhsan', 'Male', '2022-07-16', 'B+', 'BakerStreet'),
(41, 'KushalAhsan', 'Male', '2022-07-16', 'B+', 'BakerStreet'),
(42, 'KushalAhsan', 'Male', '2022-07-16', 'B+', 'BakerStreet'),
(43, 'KushalAhsan', 'Male', '2022-07-16', 'B+', 'BakerStreet'),
(44, 'H M Kushal', 'Male', '2002-07-16', 'B+', 'Kushal789'),
(45, 'H M Kushal', 'Male', '2002-07-16', 'B+', 'Kushal789'),
(46, 'H M Kushal', 'Male', '2002-07-16', 'B+', 'Kushal789'),
(47, 'H M AHSAN HABIB KUSHAL', 'Male', '2002-07-16', 'B+', 'Kushal789'),
(48, 'H M AHSAN HABIB KUSHAL', 'Male', '2000-07-16', 'B+', 'Kushal789'),
(49, 'H M AHSAN HABIB KUSHAL', 'Male', '2000-07-16', 'B+', 'Kushal789'),
(50, 'H M AHSAN HABIB KUSHAL', 'Male', '2000-07-16', 'B+', 'Kushal789'),
(51, 'H M AHSAN HABIB KUSHAL', 'Male', '2000-07-16', 'B+', 'Kushal789');

-- --------------------------------------------------------

--
-- Table structure for table `patient_email`
--

CREATE TABLE `patient_email` (
  `MED_ID` int(11) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_email`
--

INSERT INTO `patient_email` (`MED_ID`, `Email`) VALUES
(8, 'rim@jhim.net.org.topper.bos'),
(9, 'pabnapagolsovnom@gmail.com'),
(10, 'adnancse58@gmail.com'),
(11, 'ftydfsdft@gmal.com'),
(12, 'fdgdfgdf@sefsfsdf'),
(13, 'pabnasovnom@gmail.com'),
(14, 'fdgdfgdf@sefsfsdf'),
(15, 'suraiya222@gmail.com'),
(16, 'dikundu@gmail.com'),
(17, 'amartya123@gmail.com'),
(18, 'kushalsam@gmail.com'),
(19, 'rafi017751@gmail.com'),
(20, 'shimanto@gmail.com'),
(21, 'Heme@gmail.com'),
(22, 'HemeHomayra@gmail.com'),
(23, 'kushalahsan@gmail.com'),
(24, 'kopakhi@gmai.fazil.com'),
(25, 'dikundu@gmail.com'),
(26, 'tanvir@howlader.com'),
(27, 'shupi!@howlader.com'),
(28, NULL),
(29, NULL),
(30, NULL),
(31, NULL),
(32, 'none@gmail.com'),
(33, 'none@gmail.com'),
(34, 'kushalahsan@gmail.com'),
(35, 'none@gmail.com'),
(36, 'BakerStreet@gmail.com'),
(37, 'BakerStreet@gmail.com'),
(38, 'BakerStreet@gmail.com'),
(39, 'BakerStreet@gmail.com'),
(40, 'BakerStreet@gmail.com'),
(41, 'BakerStreet@gmail.com'),
(42, 'BakerStreet@gmail.com'),
(43, 'BakerStreet@gmail.com'),
(44, 'ahsanhabib12@niter.edu.bd'),
(45, 'ahsanhabib12@niter.edu.bd'),
(46, 'ahsanhabib12@niter.edu.bd'),
(47, 'ahsanhabib12@niter.edu.bd'),
(48, 'ahsanhabib12@niter.edu.bd'),
(49, 'ahsanhabib12@niter.edu.bd'),
(50, 'ahsanhabib12@niter.edu.bd'),
(51, 'ahsanhabib12@niter.edu.bd');

-- --------------------------------------------------------

--
-- Table structure for table `patient_mobile`
--

CREATE TABLE `patient_mobile` (
  `MED_ID` int(11) DEFAULT NULL,
  `Mobile_No` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_mobile`
--

INSERT INTO `patient_mobile` (`MED_ID`, `Mobile_No`) VALUES
(1, '01782188484'),
(2, '01782188484'),
(3, '01712211284'),
(4, '01770370419'),
(5, '01782188484'),
(6, '01782188481'),
(7, '01282188484'),
(8, '01787718452'),
(9, '0170470239'),
(10, '01782188484'),
(11, '456456453'),
(12, '54345345'),
(13, '0178218666'),
(14, '121212121'),
(15, '01444556668'),
(16, '01712211284'),
(17, '017182546987'),
(18, '234234234'),
(19, '01712211278'),
(20, '01611063551'),
(21, '012326585624'),
(22, '012326585624'),
(23, '01712211284'),
(24, '0198765432'),
(25, '012326585624'),
(26, '016789010'),
(27, '0171616161'),
(28, NULL),
(29, NULL),
(30, NULL),
(31, NULL),
(32, '01770370419'),
(33, '01770370419'),
(34, '01712211284'),
(35, '01770370419'),
(36, '01888882828'),
(37, '01888882828'),
(38, '01888882828'),
(39, '01888882828'),
(40, '01888882828'),
(41, '01888882828'),
(42, '01888882828'),
(43, '01888882828'),
(44, '01888882828'),
(45, '01888882828'),
(46, '01888882828'),
(47, '01712211824'),
(48, '01712211824'),
(49, '01712211824'),
(50, '01712211824'),
(51, '01712211824');

-- --------------------------------------------------------

--
-- Table structure for table `prescription`
--

CREATE TABLE `prescription` (
  `Prescription_Serial` int(11) NOT NULL,
  `MED_ID` int(11) DEFAULT NULL,
  `DOC_ID` int(11) DEFAULT NULL,
  `Prescription_Date` date DEFAULT NULL,
  `Pres_File_Path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription`
--

INSERT INTO `prescription` (`Prescription_Serial`, `MED_ID`, `DOC_ID`, `Prescription_Date`, `Pres_File_Path`) VALUES
(1025, 8, NULL, '2025-08-08', 'uploads/prescriptions/10.3934_publichealth.2024004.pdf'),
(1026, 27, 2008, '2025-08-08', 'uploads/MIT_Level_CSE_CV_Template.docx'),
(1027, 23, NULL, '2025-08-11', 'uploads/prescriptions/Dhaka_ramadan_calendar (3).pdf'),
(1028, 15, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1029, 15, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1030, 15, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1031, 15, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1032, 15, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1033, 40, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1034, 41, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1035, 42, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1036, 43, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1037, 44, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1038, 45, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1039, 46, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1040, 47, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1041, 48, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1042, 49, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1043, 50, NULL, '2026-01-18', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1044, 51, NULL, '2026-01-19', 'uploads/prescriptions/Coursera U1TX2W3C4HL5 Responsible AI Use Google - Ahsan Habib.pdf'),
(1045, 19, 4602, '2026-01-19', 'uploads/Coursera BHJK7IJBT6PO Google Drive - Ahsan Habib.pdf'),
(1046, 6, NULL, '2026-01-19', 'uploads/prescriptions/Coursera 1AB35385QEXA Prompting Art Google - Ahsan Habib.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `vaccine_tracking`
--

CREATE TABLE `vaccine_tracking` (
  `MED_ID` int(11) DEFAULT NULL,
  `Hospital_Serial` int(11) DEFAULT NULL,
  `Vaccine_Name` varchar(30) DEFAULT NULL,
  `Dose_No` int(11) DEFAULT NULL,
  `Vacc_Admin_Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccine_tracking`
--

INSERT INTO `vaccine_tracking` (`MED_ID`, `Hospital_Serial`, `Vaccine_Name`, `Dose_No`, `Vacc_Admin_Date`) VALUES
(8, NULL, 'AntiSarsCov2', 1, '2025-02-05'),
(20, NULL, 'AntiSarsCov2', 1, '2023-02-06'),
(24, NULL, 'AntiSarsCov2', 1, '2021-01-01'),
(26, NULL, 'Pfizer', 234345, '2022-01-09'),
(27, NULL, 'pfizer', 101222, '2022-02-03'),
(8, NULL, 'StraightRmnc', 101, '2008-08-08'),
(15, NULL, 'Sputnik V', 1, '2026-01-19'),
(15, NULL, 'Sputnik V', 2, '2026-01-19'),
(15, NULL, 'Sputnik V', 3, '2026-01-19'),
(15, NULL, 'Sputnik VI', 1, '2026-01-19'),
(15, NULL, 'Sputnik VI', 2, '2026-01-19'),
(15, NULL, 'Sputnik VI', 3, '2026-01-19'),
(15, NULL, 'Sputnik VI', 3, '2026-01-19'),
(15, NULL, 'Sputnik X', 1, '2026-01-19'),
(15, NULL, 'Sputnik X', 3, '2026-01-19'),
(49, NULL, 'Sputnik X', 3, '2026-01-19'),
(50, NULL, 'Sputnik X', 3, '2026-01-19'),
(51, NULL, 'Sputnik X', 3, '2026-01-19');

-- --------------------------------------------------------

--
-- Table structure for table `visited_doctor`
--

CREATE TABLE `visited_doctor` (
  `MED_ID` int(11) DEFAULT NULL,
  `DOC_ID` int(11) DEFAULT NULL,
  `Visited_Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `diagnosis`
--
ALTER TABLE `diagnosis`
  ADD KEY `Hospital_Serial` (`Hospital_Serial`),
  ADD KEY `diagnosis_fk_1` (`MED_ID`);

--
-- Indexes for table `disease_history`
--
ALTER TABLE `disease_history`
  ADD KEY `MED_ID` (`MED_ID`);

--
-- Indexes for table `doctor_details`
--
ALTER TABLE `doctor_details`
  ADD PRIMARY KEY (`DOC_ID`),
  ADD KEY `Hospital_Serial` (`Hospital_Serial`),
  ADD KEY `Department_ID` (`Department_ID`);

--
-- Indexes for table `global_departments`
--
ALTER TABLE `global_departments`
  ADD PRIMARY KEY (`Department_ID`),
  ADD UNIQUE KEY `Department_Name` (`Department_Name`);

--
-- Indexes for table `hospital`
--
ALTER TABLE `hospital`
  ADD PRIMARY KEY (`Hospital_Serial`);

--
-- Indexes for table `hospital_departments`
--
ALTER TABLE `hospital_departments`
  ADD PRIMARY KEY (`Hospital_Serial`,`Department_ID`),
  ADD KEY `Department_ID` (`Department_ID`);

--
-- Indexes for table `patient_address`
--
ALTER TABLE `patient_address`
  ADD KEY `MED_ID` (`MED_ID`);

--
-- Indexes for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD KEY `MED_ID` (`MED_ID`);

--
-- Indexes for table `patient_details`
--
ALTER TABLE `patient_details`
  ADD PRIMARY KEY (`MED_ID`);

--
-- Indexes for table `patient_email`
--
ALTER TABLE `patient_email`
  ADD KEY `MED_ID` (`MED_ID`);

--
-- Indexes for table `patient_mobile`
--
ALTER TABLE `patient_mobile`
  ADD KEY `MED_ID` (`MED_ID`);

--
-- Indexes for table `prescription`
--
ALTER TABLE `prescription`
  ADD PRIMARY KEY (`Prescription_Serial`),
  ADD KEY `MED_ID` (`MED_ID`),
  ADD KEY `DOC_ID` (`DOC_ID`);

--
-- Indexes for table `vaccine_tracking`
--
ALTER TABLE `vaccine_tracking`
  ADD KEY `MED_ID` (`MED_ID`),
  ADD KEY `Hospital_Serial` (`Hospital_Serial`);

--
-- Indexes for table `visited_doctor`
--
ALTER TABLE `visited_doctor`
  ADD KEY `MED_ID` (`MED_ID`),
  ADD KEY `DOC_ID` (`DOC_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hospital`
--
ALTER TABLE `hospital`
  MODIFY `Hospital_Serial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_details`
--
ALTER TABLE `patient_details`
  MODIFY `MED_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `prescription`
--
ALTER TABLE `prescription`
  MODIFY `Prescription_Serial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1047;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `diagnosis`
--
ALTER TABLE `diagnosis`
  ADD CONSTRAINT `diagnosis_fk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`),
  ADD CONSTRAINT `diagnosis_ibfk_3` FOREIGN KEY (`Hospital_Serial`) REFERENCES `hospital` (`Hospital_Serial`);

--
-- Constraints for table `disease_history`
--
ALTER TABLE `disease_history`
  ADD CONSTRAINT `disease_history_ibfk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`);

--
-- Constraints for table `doctor_details`
--
ALTER TABLE `doctor_details`
  ADD CONSTRAINT `doctor_details_ibfk_1` FOREIGN KEY (`Hospital_Serial`) REFERENCES `hospital` (`Hospital_Serial`),
  ADD CONSTRAINT `doctor_details_ibfk_2` FOREIGN KEY (`Department_ID`) REFERENCES `global_departments` (`Department_ID`);

--
-- Constraints for table `hospital_departments`
--
ALTER TABLE `hospital_departments`
  ADD CONSTRAINT `hospital_departments_ibfk_1` FOREIGN KEY (`Hospital_Serial`) REFERENCES `hospital` (`Hospital_Serial`),
  ADD CONSTRAINT `hospital_departments_ibfk_2` FOREIGN KEY (`Department_ID`) REFERENCES `global_departments` (`Department_ID`);

--
-- Constraints for table `patient_address`
--
ALTER TABLE `patient_address`
  ADD CONSTRAINT `patient_address_ibfk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`);

--
-- Constraints for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD CONSTRAINT `patient_allergies_ibfk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`);

--
-- Constraints for table `patient_email`
--
ALTER TABLE `patient_email`
  ADD CONSTRAINT `patient_email_ibfk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`);

--
-- Constraints for table `patient_mobile`
--
ALTER TABLE `patient_mobile`
  ADD CONSTRAINT `patient_mobile_ibfk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`);

--
-- Constraints for table `prescription`
--
ALTER TABLE `prescription`
  ADD CONSTRAINT `prescription_ibfk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`),
  ADD CONSTRAINT `prescription_ibfk_2` FOREIGN KEY (`DOC_ID`) REFERENCES `doctor_details` (`DOC_ID`);

--
-- Constraints for table `vaccine_tracking`
--
ALTER TABLE `vaccine_tracking`
  ADD CONSTRAINT `vaccine_tracking_ibfk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`),
  ADD CONSTRAINT `vaccine_tracking_ibfk_2` FOREIGN KEY (`Hospital_Serial`) REFERENCES `hospital` (`Hospital_Serial`);

--
-- Constraints for table `visited_doctor`
--
ALTER TABLE `visited_doctor`
  ADD CONSTRAINT `visited_doctor_ibfk_1` FOREIGN KEY (`MED_ID`) REFERENCES `patient_details` (`MED_ID`),
  ADD CONSTRAINT `visited_doctor_ibfk_2` FOREIGN KEY (`DOC_ID`) REFERENCES `doctor_details` (`DOC_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
