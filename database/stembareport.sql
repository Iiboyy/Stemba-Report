-- Create database
CREATE DATABASE IF NOT EXISTS stembareport;
USE stembareport;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reports table
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(100) NOT NULL,
    date_lost DATE NOT NULL,
    image VARCHAR(255),
    status ENUM('hilang','ditemukan') DEFAULT 'hilang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@stemba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO reports (user_id, title, description, location, date_lost, image, status) VALUES
(1, 'Tas Sekolah Hitam', 'Tas ransel warna hitam merk Eiger, berisi buku dan alat tulis', 'Lab Komputer', '2024-03-10', 'sample1.jpg', 'hilang'),
(1, 'Dompet Kulit Coklat', 'Dompet kulit warna coklat, berisi KTP dan kartu pelajar', 'Perpustakaan', '2024-03-09', 'sample2.jpg', 'hilang');