CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name varchar(255),
    email varchar(255)
);
CREATE TABLE IF NOT EXISTS articles (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    author_id INT,
    title varchar(255),
    description varchar(255)
);
