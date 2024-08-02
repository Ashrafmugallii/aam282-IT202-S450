CREATE TABLE Journals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    from_airport_code VARCHAR(10) NOT NULL,
    to_airport_code VARCHAR(10) NOT NULL,
    entry_date DATE NOT NULL,
    content TEXT NOT NULL,
    photos TEXT,
    hotel_details JSON,
    restaurant_details JSON,
    attraction_details JSON,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id)
);
