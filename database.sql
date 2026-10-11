CREATE DATABASE IF NOT EXISTS student_stays
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE student_stays;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS properties (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    city VARCHAR(80) NOT NULL,
    neighborhood VARCHAR(100) NOT NULL,
    address VARCHAR(220) NOT NULL,
    price INT UNSIGNED NOT NULL,
    gender ENUM('Women', 'Men', 'Any') NOT NULL DEFAULT 'Any',
    rating DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    review_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    description TEXT NOT NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    image_url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY properties_city_price (city, price),
    KEY properties_gender (gender),
    KEY properties_featured_rating (featured, rating)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS amenities (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(60) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY amenities_name_unique (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_amenities (
    property_id BIGINT UNSIGNED NOT NULL,
    amenity_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (property_id, amenity_id),
    CONSTRAINT property_amenities_property_fk FOREIGN KEY (property_id)
        REFERENCES properties (id) ON DELETE CASCADE,
    CONSTRAINT property_amenities_amenity_fk FOREIGN KEY (amenity_id)
        REFERENCES amenities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT UNSIGNED NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    caption VARCHAR(180) NOT NULL DEFAULT '',
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY property_images_property_order_unique (property_id, sort_order),
    CONSTRAINT property_images_property_fk FOREIGN KEY (property_id)
        REFERENCES properties (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS interested_users (
    user_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, property_id),
    KEY interested_property_idx (property_id),
    CONSTRAINT interested_users_user_fk FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT interested_users_property_fk FOREIGN KEY (property_id)
        REFERENCES properties (id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO amenities (id, name) VALUES
    (1, 'Wi-Fi'),
    (2, 'Meals'),
    (3, 'Laundry'),
    (4, 'Housekeeping'),
    (5, 'AC'),
    (6, 'Study room'),
    (7, 'Gym'),
    (8, 'CCTV'),
    (9, 'Power backup'),
    (10, 'Common lounge'),
    (11, 'Parking'),
    (12, 'Attached bath');

INSERT IGNORE INTO properties
    (id, name, city, neighborhood, address, price, gender, rating, review_count, description, featured, image_url)
VALUES
    (1, 'The Sunday House', 'Bengaluru', 'Indiranagar', '12th Main Road, Indiranagar', 14500, 'Women', 4.9, 48,
     'A sun-soaked, thoughtfully run home tucked into one of Bengaluru’s loveliest neighbourhoods. Find your morning coffee around the corner, a friendly face in the kitchen, and a little more room to make this place yours.', 1,
     'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=900&q=85'),
    (2, 'Mango Tree Living', 'Bengaluru', 'Koramangala', '5th Block, Koramangala', 11500, 'Any', 4.8, 36,
     'A relaxed, green-filled home made for good routines and new friendships. Settle into a bright private room, share a meal in the open kitchen, or take your laptop to the leafy common lounge.', 1,
     'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=85'),
    (3, 'Little Fern House', 'Pune', 'Koregaon Park', 'Lane 5, Koregaon Park', 9800, 'Women', 4.8, 29,
     'A calm, characterful space with leafy balconies and the kind of kitchen where conversations run long. Thoughtfully furnished rooms and helpful on-site support make moving in feel easy.', 1,
     'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=85'),
    (4, 'Sunday Social', 'Mumbai', 'Powai', 'Hiranandani Gardens, Powai', 16800, 'Men', 4.7, 52,
     'Your quiet corner in a city that never stops. This welcoming home pairs a comfortable study space with an easygoing common room, a reliable power backup, and a neighbourhood full of good food.', 1,
     'https://images.unsplash.com/photo-1600210491892-03d54c0aaf87?auto=format&fit=crop&w=900&q=85'),
    (5, 'The Courtyard Club', 'Hyderabad', 'Gachibowli', 'Botanical Garden Road, Gachibowli', 12500, 'Any', 4.9, 41,
     'A bright, breezy place to come home to after a long day. Enjoy roomy shared spaces, a quiet nook to focus, and a warm community that makes a new city feel familiar.', 1,
     'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=900&q=85'),
    (6, 'Olive & Oak', 'Delhi', 'Hauz Khas', 'Aurobindo Marg, Hauz Khas', 15000, 'Women', 4.7, 33,
     'Old Delhi character meets a fresh, airy interior. Thoughtful little touches, a secure building, and excellent places to eat nearby make this a lovely base for your next chapter.', 0,
     'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=900&q=85'),
    (7, 'The Blue Door', 'Chennai', 'Adyar', 'LB Road, Adyar', 8500, 'Men', 4.6, 24,
     'A comfortable, no-fuss home with friendly housemates and everything you need close by. Settle in, find your favourite tiffin place, and get on with the good stuff.', 0,
     'https://images.unsplash.com/photo-1600121848594-d8644e57abab?auto=format&fit=crop&w=900&q=85'),
    (8, 'Paper Kite House', 'Pune', 'Viman Nagar', 'Konark Nagar, Viman Nagar', 7600, 'Any', 4.8, 31,
     'A bright, happy home just a short ride from campus. There is space to study, room to unwind, and a welcoming little community ready to make your first week easier.', 0,
     'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=900&q=85');

UPDATE properties
SET name = CASE id
        WHEN 1 THEN 'Indiranagar Students PG'
        WHEN 2 THEN 'Koramangala Shared PG'
        WHEN 3 THEN 'Koregaon Park Girls PG'
        WHEN 4 THEN 'Powai Budget PG'
        WHEN 5 THEN 'Gachibowli Student PG'
        WHEN 6 THEN 'Hauz Khas Shared PG'
        WHEN 7 THEN 'Adyar Student PG'
        WHEN 8 THEN 'Viman Nagar Budget PG'
        ELSE name
    END,
    price = CASE id
        WHEN 1 THEN 6800
        WHEN 2 THEN 6200
        WHEN 3 THEN 5200
        WHEN 4 THEN 8500
        WHEN 5 THEN 5800
        WHEN 6 THEN 7000
        WHEN 7 THEN 4800
        WHEN 8 THEN 4200
        ELSE price
    END,
    description = CASE id
        WHEN 1 THEN 'A practical shared PG in Indiranagar with Wi-Fi, meals, laundry and a study area. Close to everyday shops and transport, with a clear monthly rent of ₹6,800.'
        WHEN 2 THEN 'An easygoing shared PG in Koramangala with meals, Wi-Fi and laundry. A handy base near colleges, bus routes and affordable places to eat.'
        WHEN 3 THEN 'A straightforward girls PG in Koregaon Park with Wi-Fi, meals and a study space. Share a clean, comfortable room and keep monthly rent predictable.'
        WHEN 4 THEN 'A no-fuss shared PG in Powai with meals, Wi-Fi and a secure building. Handy for students looking to keep accommodation close to campus.'
        WHEN 5 THEN 'A simple, well-connected shared PG in Gachibowli. Meals, Wi-Fi, laundry and power backup are included for a sensible monthly rent.'
        WHEN 6 THEN 'A budget-friendly girls PG in Hauz Khas with meals, Wi-Fi and a secure entrance. Convenient for classes, the metro and local shops.'
        WHEN 7 THEN 'An affordable shared PG in Adyar with Wi-Fi, meals and laundry. A comfortable everyday option near local buses, shops and campus.'
        WHEN 8 THEN 'An affordable, straightforward shared PG in Viman Nagar with Wi-Fi, meals and a study space. Keep more of your budget for everything outside rent.'
        ELSE description
    END,
    image_url = CASE id
        WHEN 1 THEN 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=900&q=85'
        WHEN 2 THEN 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=900&q=85'
        WHEN 3 THEN 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?auto=format&fit=crop&w=900&q=85'
        WHEN 4 THEN 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=900&q=85'
        WHEN 5 THEN 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=900&q=85'
        WHEN 6 THEN 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?auto=format&fit=crop&w=900&q=85'
        WHEN 7 THEN 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=900&q=85'
        WHEN 8 THEN 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=900&q=85'
        ELSE image_url
    END
WHERE id BETWEEN 1 AND 8;

INSERT IGNORE INTO property_amenities (property_id, amenity_id) VALUES
    (1, 1), (1, 2), (1, 3), (1, 4), (1, 6), (1, 8), (1, 10), (1, 12),
    (2, 1), (2, 2), (2, 3), (2, 5), (2, 8), (2, 9), (2, 10),
    (3, 1), (3, 2), (3, 3), (3, 4), (3, 6), (3, 8), (3, 12),
    (4, 1), (4, 2), (4, 3), (4, 5), (4, 6), (4, 8), (4, 9),
    (5, 1), (5, 2), (5, 3), (5, 4), (5, 6), (5, 7), (5, 10),
    (6, 1), (6, 2), (6, 4), (6, 5), (6, 8), (6, 11), (6, 12),
    (7, 1), (7, 2), (7, 3), (7, 8), (7, 9), (7, 11),
    (8, 1), (8, 2), (8, 3), (8, 4), (8, 6), (8, 10);

INSERT IGNORE INTO property_images (property_id, image_url, caption, sort_order) VALUES
    (1, 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=85', 'A warm, light-filled living room', 1),
    (1, 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1200&q=85', 'A quiet place to unwind', 2),
    (2, 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1200&q=85', 'Easygoing shared living', 1),
    (2, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=85', 'A bright corner to call your own', 2),
    (3, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=85', 'Soft light and thoughtful details', 1),
    (3, 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1200&q=85', 'Room to settle in', 2),
    (4, 'https://images.unsplash.com/photo-1600210491892-03d54c0aaf87?auto=format&fit=crop&w=1200&q=85', 'A comfortable place to come home to', 1),
    (4, 'https://images.unsplash.com/photo-1600121848594-d8644e57abab?auto=format&fit=crop&w=1200&q=85', 'A calm, considered interior', 2),
    (5, 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1200&q=85', 'A little breathing room', 1),
    (5, 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=85', 'Come together in the common room', 2),
    (6, 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1200&q=85', 'Thoughtful details throughout', 1),
    (6, 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=85', 'A welcoming place to reset', 2),
    (7, 'https://images.unsplash.com/photo-1600121848594-d8644e57abab?auto=format&fit=crop&w=1200&q=85', 'Simple, comfortable living', 1),
    (7, 'https://images.unsplash.com/photo-1600210491892-03d54c0aaf87?auto=format&fit=crop&w=1200&q=85', 'A shared space to relax', 2),
    (8, 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=85', 'A happy place to land', 1),
    (8, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=85', 'A bright start to your morning', 2);

INSERT INTO property_images (property_id, image_url, caption, sort_order) VALUES
    (1, 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=1200&q=85', 'Shared PG bedroom with bunk beds', 1),
    (1, 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=1200&q=85', 'Bright shared bedroom', 2),
    (2, 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=1200&q=85', 'Simple shared bedroom', 1),
    (2, 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?auto=format&fit=crop&w=1200&q=85', 'Comfortable PG bedroom', 2),
    (3, 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?auto=format&fit=crop&w=1200&q=85', 'Bright bedroom with a study corner', 1),
    (3, 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=1200&q=85', 'Shared room with bunk beds', 2),
    (4, 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=85', 'Basic, comfortable PG room', 1),
    (4, 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=1200&q=85', 'Bedroom with natural light', 2),
    (5, 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=1200&q=85', 'Shared bedroom with bunk beds', 1),
    (5, 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?auto=format&fit=crop&w=1200&q=85', 'Neat shared PG bedroom', 2),
    (6, 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?auto=format&fit=crop&w=1200&q=85', 'Simple furnished bedroom', 1),
    (6, 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=85', 'Clean, comfortable room', 2),
    (7, 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=85', 'Everyday shared PG bedroom', 1),
    (7, 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=1200&q=85', 'Shared room with bunk beds', 2),
    (8, 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=1200&q=85', 'Bright budget PG bedroom', 1),
    (8, 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=85', 'Practical furnished bedroom', 2)
ON DUPLICATE KEY UPDATE
    image_url = VALUES(image_url),
    caption = VALUES(caption);
