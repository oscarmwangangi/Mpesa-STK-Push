CREATE DATABASE mpesa_payments;

USE mpesa_payments;

CREATE TABLE payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(15) NOT NULL,
    amount DOUBLE NOT NULL,
    merchant_request_id VARCHAR(50) NOT NULL,
    checkout_request_id VARCHAR(50) NOT NULL,
    result_code INT(11),
    result_desc VARCHAR(255),
    mpesa_receipt_number VARCHAR(50),
    transaction_date VARCHAR(20),
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
