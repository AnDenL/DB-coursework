DROP DATABASE IF EXISTS expeditions_db;
CREATE DATABASE expeditions_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expeditions_db;

CREATE TABLE regions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description MEDIUMTEXT,      -- can be without description
    photo_path VARCHAR(255),     -- photo is optional
    temperature_zone ENUM('Freezing', 'Cold', 'Temperate', 'Warm', 'Scorching') NOT NULL,
    humidity_level ENUM('Arid', 'Dry', 'Moderate', 'Humid', 'Swampy') NOT NULL,
    terrain_type ENUM('Forest', 'Mountains', 'Plains', 'Swamp', 'Coast', 'Underground', 'Desert') NOT NULL,
    population INT UNSIGNED NOT NULL
);

CREATE TABLE researchers (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    academic_degree VARCHAR(80) NOT NULL,
    phone VARCHAR(20) NOT NULL
);

CREATE TABLE equipment (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    estimated_cost DECIMAL(10, 2) NOT NULL
);

CREATE TABLE expeditions (
    id SERIAL PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    region_id BIGINT UNSIGNED NOT NULL,
    budget DECIMAL(10, 2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,              -- NULL = expedition haven't ended yet

    FOREIGN KEY (region_id) REFERENCES regions(id)
);

CREATE TABLE expedition_members (
    expedition_id BIGINT UNSIGNED NOT NULL,
    researcher_id BIGINT UNSIGNED NOT NULL,
    researcher_role ENUM('Leader', 'Folklorist', 'Collector', 'Restorer', 'Archivist', 'Bodyguard') NOT NULL,
    PRIMARY KEY (expedition_id, researcher_id),

    CONSTRAINT fk_members_expedition FOREIGN KEY (expedition_id) REFERENCES expeditions(id) ON DELETE CASCADE,
    CONSTRAINT fk_members_researcher FOREIGN KEY (researcher_id) REFERENCES researchers(id) ON DELETE RESTRICT
);

CREATE TABLE expedition_equipment (
    expedition_id BIGINT UNSIGNED NOT NULL,
    equipment_id BIGINT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL,
    PRIMARY KEY (expedition_id, equipment_id),

    CONSTRAINT fk_equip_expedition FOREIGN KEY (expedition_id) REFERENCES expeditions(id) ON DELETE CASCADE,
    CONSTRAINT fk_equip_item FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

CREATE TABLE artifacts (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description MEDIUMTEXT,         -- artifact can be without description
    photo_path VARCHAR(255),        -- photo is optional
    expedition_id BIGINT UNSIGNED NOT NULL,
    item_condition ENUM('Good', 'Mediocre', 'Needs restoration') NOT NULL,
    estimated_century INT,          -- NULL = not measured
    estimated_value DECIMAL(10, 2), -- NULL = not measured

    FOREIGN KEY (expedition_id) REFERENCES expeditions(id)
);
