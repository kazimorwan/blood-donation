-- Sample data for testing. Import AFTER schema.sql.
-- Every sample user has the password:  password
-- Admin login:  mobile 01700000000
-- User login:   mobile 01711000001 (Rahim)

USE `blood_donations`;

INSERT INTO `areas` (`id`, `name`) VALUES
(1, 'Dhanmondi'), (2, 'Mirpur'), (3, 'Uttara'), (4, 'Gulshan'), (5, 'Mohammadpur'),
(6, 'Chattogram City'), (7, 'Sylhet City'), (8, 'Rajshahi City'), (9, 'Khulna City');

INSERT INTO `users` (`id`, `name`, `mobile_no`, `password`, `blood_group`, `area_id`, `dob`, `nid`, `last_donation_date`, `is_verified`, `status`, `role`) VALUES
(1,  'Admin',           '01700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'O+',  1, '1990-01-01', NULL, NULL, 1, 1, 'admin'),
(2,  'Rahim Uddin',     '01711000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'O+',  1, '1995-04-12', '1995123456789', DATE_SUB(CURDATE(), INTERVAL 170 DAY), 1, 1, 'user'),
(3,  'Nusrat Jahan',    '01811000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'A+',  6, '1998-08-21', NULL, DATE_SUB(CURDATE(), INTERVAL 30 DAY),  0, 1, 'user'),
(4,  'Karim Hossain',   '01911000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'B+',  2, '1992-02-15', NULL, DATE_SUB(CURDATE(), INTERVAL 250 DAY), 1, 1, 'user'),
(5,  'Sadia Rahman',    '01611000004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'AB+', 7, '1996-11-30', NULL, DATE_SUB(CURDATE(), INTERVAL 190 DAY), 0, 1, 'user'),
(6,  'Tanvir Ahmed',    '01511000005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'O-',  8, '1990-07-07', NULL, DATE_SUB(CURDATE(), INTERVAL 300 DAY), 1, 1, 'user'),
(7,  'Mim Akter',       '01711000006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'A-',  9, '1999-03-03', NULL, DATE_SUB(CURDATE(), INTERVAL 20 DAY),  0, 1, 'user'),
(8,  'Fahim Chowdhury', '01811000007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'B-',  3, '1994-09-09', NULL, DATE_SUB(CURDATE(), INTERVAL 220 DAY), 1, 1, 'user'),
(9,  'Anika Tabassum',  '01911000008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'O+',  6, '1997-12-12', NULL, DATE_SUB(CURDATE(), INTERVAL 5 DAY),   1, 1, 'user'),
(10, 'Jubayer Alam',    '01611000009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'AB-', 4, '1993-05-25', NULL, NULL, 0, 1, 'user'),
(11, 'Farhana Yasmin',  '01511000010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'A+',  8, '1991-10-10', NULL, DATE_SUB(CURDATE(), INTERVAL 120 DAY), 1, 1, 'user'),
(12, 'Imran Hasan',     '01711000011', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'B+',  7, '1989-06-18', NULL, DATE_SUB(CURDATE(), INTERVAL 400 DAY), 0, 2, 'user'),
(13, 'Tasnim Sultana',  '01811000012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'O+',  9, '2000-01-20', NULL, NULL, 1, 1, 'user');

-- status: 1=pending 2=ongoing 3=accepted 4=completed 5=cancelled
INSERT INTO `donation_requests` (`id`, `patient_problem`, `blood_seeker_id`, `blood_group`, `donation_date`, `time`, `quantity`, `area_id`, `status`) VALUES
(1, 'Heart surgery. Dhaka Medical College Hospital, Ward 5.', 4,  'O-',  DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 2, 1, 1),
(2, 'Dengue patient. Chattogram Medical Hospital.',            5,  'A+',  DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:30:00', 2, 6, 2),
(3, 'Accident case. Square Hospital, Panthapath.',             2,  'B+',  DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', 1, 1, 3),
(4, 'Thalassemia patient. United Hospital, Gulshan.',          6,  'O+',  DATE_SUB(CURDATE(), INTERVAL 6 DAY), '11:00:00', 1, 4, 4),
(5, 'Delivery case. Uttara Crescent Hospital.',                8,  'AB+', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', 1, 3, 1),
(6, 'Operation postponed.',                                    10, 'B-',  DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL,       1, 4, 5);

-- status: 1=accepted 2=cancelled 3=completed
INSERT INTO `donation_requests_transactions` (`donation_request_id`, `doner_id`, `donation_date`, `status`) VALUES
(2, 11, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 1),
(3, 4,  DATE_ADD(CURDATE(), INTERVAL 5 DAY), 1),
(4, 9,  DATE_SUB(CURDATE(), INTERVAL 6 DAY), 3),
(1, 6,  DATE_ADD(CURDATE(), INTERVAL 2 DAY), 2);
