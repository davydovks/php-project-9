-- CREATE DATABASE IF NOT EXISTS page_analyzer;
-- DROP TABLE IF EXISTS urls;
CREATE TABLE IF NOT EXISTS urls (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255),
    created_at timestamp
);
-- DROP TABLE IF EXISTS checks;
CREATE TABLE IF NOT EXISTS checks (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    status int,
    h1 varchar(255),
    title varchar(255),
    descritpion varchar(255),
    created_at timestamp
);
