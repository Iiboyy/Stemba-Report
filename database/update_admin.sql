ALTER TABLE users MODIFY role ENUM('user','admin') DEFAULT 'user';

INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@stemba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE role='admin';