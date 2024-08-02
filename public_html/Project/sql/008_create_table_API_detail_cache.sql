CREATE TABLE DetailCache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detail_id VARCHAR(255) NOT NULL,
    detail_type ENUM('hotel', 'restaurant', 'attraction') NOT NULL,
    detail_name VARCHAR(255),
    detail_description TEXT,
    detail_image_url VARCHAR(255),
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
