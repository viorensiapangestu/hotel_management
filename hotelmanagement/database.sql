-- 1. Create the guests table
CREATE TABLE guests (
    guest_id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    phone varchar(20),
    id_number varchar(50),
    created_at datetime DEFAULT current_timestamp(),
    email varchar(100),
    PRIMARY KEY (guest_id),
    UNIQUE KEY (email)
);

-- 2. Create the roomstatus table (Lookup table)
CREATE TABLE roomstatus (
    status_id int(11) NOT NULL AUTO_INCREMENT,
    status_name varchar(50) NOT NULL,
    PRIMARY KEY (status_id),
    UNIQUE KEY (status_name)
);

-- 3. Create the roomtype table
CREATE TABLE roomtype (
    room_type_id int(11) NOT NULL AUTO_INCREMENT,
    room_type_name varchar(50) NOT NULL,
    price decimal(10,2) NOT NULL,
    capacity int(11) NOT NULL,
    description text,
    amenities text,
    PRIMARY KEY (room_type_id)
);

-- 4. Create the rooms table
CREATE TABLE rooms (
    room_id int(11) NOT NULL AUTO_INCREMENT,
    room_number varchar(100) NOT NULL,
    room_type_id int(11) NOT NULL,
    status_id int(11) NOT NULL,
    PRIMARY KEY (room_id),
    UNIQUE KEY (room_number),
    FOREIGN KEY (room_type_id) REFERENCES roomtype(room_type_id),
    FOREIGN KEY (status_id) REFERENCES roomstatus(status_id)
);

-- 5. Create the users table (Staff/Admin)
CREATE TABLE users (
    user_id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    email varchar(100) NOT NULL,
    password varchar(255) NOT NULL,
    role varchar(50) NOT NULL,
    created_at datetime DEFAULT current_timestamp(),
    PRIMARY KEY (user_id),
    UNIQUE KEY (email)
);

-- 6. Create the reservations table
CREATE TABLE reservations (
    reservation_id int(11) NOT NULL AUTO_INCREMENT,
    guest_id int(11) NOT NULL,
    room_type_id int(11),
    room_id int(11),
    reservation_date datetime DEFAULT current_timestamp(),
    check_in_date date NOT NULL,
    check_out_date date NOT NULL,
    status varchar(50) NOT NULL,
    total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
    payment_status varchar(20) DEFAULT 'Unpaid',
    PRIMARY KEY (reservation_id),
    FOREIGN KEY (guest_id) REFERENCES guests(guest_id)
);

-- 7. Create the stay table (Tracks actual arrival/departure)
CREATE TABLE stay (
    stay_id int(11) NOT NULL AUTO_INCREMENT,
    reservation_id int(11) NOT NULL,
    actual_check_in datetime,
    actual_check_out datetime,
    handled_by int(11),
    check_in_time datetime,
    check_out_time datetime,
    PRIMARY KEY (stay_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    FOREIGN KEY (handled_by) REFERENCES users(user_id)
);

-- 8. Create the companions table
CREATE TABLE companions (
    companion_id int(11) NOT NULL AUTO_INCREMENT,
    reservation_id int(11) NOT NULL,
    name varchar(100) NOT NULL,
    PRIMARY KEY (companion_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

-- 9. Create the services table
CREATE TABLE services (
    service_id int(11) NOT NULL AUTO_INCREMENT,
    category varchar(50) NOT NULL,
    service_name varchar(100) NOT NULL,
    price decimal(10,2) NOT NULL,
    PRIMARY KEY (service_id)
);

-- 10. Create the guestservices table
CREATE TABLE guestservices (
    guest_service_id int(11) NOT NULL AUTO_INCREMENT,
    reservation_id int(11) NOT NULL,
    service_id int(11) NOT NULL,
    quantity int(11) NOT NULL DEFAULT 1,
    total_price decimal(10,2) NOT NULL,
    order_time datetime DEFAULT current_timestamp(),
    PRIMARY KEY (guest_service_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);

-- 11. Create the invoices table
CREATE TABLE invoices (
    invoice_id int(11) NOT NULL AUTO_INCREMENT,
    reservation_id int(11) NOT NULL,
    total_amount decimal(10,2) NOT NULL,
    issued_date datetime DEFAULT current_timestamp(),
    status varchar(50) NOT NULL,
    PRIMARY KEY (invoice_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

-- 12. Create the payments table
CREATE TABLE payments (
    payment_id int(11) NOT NULL AUTO_INCREMENT,
    invoice_id int(11) NOT NULL,
    payment_date date NOT NULL,
    payment_method varchar(50) NOT NULL,
    amount decimal(10,2) NOT NULL,
    PRIMARY KEY (payment_id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
);