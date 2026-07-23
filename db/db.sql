CREATE DATABASE expense_tracker_;
USE expense_tracker_;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    type ENUM('expense', 'income') NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db'
);

CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    category_id INT,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Sample data
INSERT INTO categories (name, type, color) VALUES
('Food', 'expense', '#e74c3c'), ('Transport', 'expense', '#f39c12'),
('Shopping', 'expense', '#9b59b6'), ('Salary', 'income', '#27ae60'),
('Freelance', 'income', '#2ecc71'), ('Bills', 'expense', '#3498db');

-- Test user: vicky / 123456
INSERT INTO users (username, email, password) VALUES 
('vicky', 'sarojvicky101@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

CREATE TABLE `goals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `month_year` VARCHAR(7) NOT NULL,  
  `goal_amount` DECIMAL(10,2) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);