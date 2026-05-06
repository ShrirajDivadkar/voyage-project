-- ============================================================
-- Migration: Add booking_type and extra columns to bookings
-- Run this once in phpMyAdmin or MySQL CLI
-- ============================================================

ALTER TABLE `bookings`
  ADD COLUMN `booking_type` ENUM('destination','hotel','flight') NOT NULL DEFAULT 'destination' AFTER `user_id`,
  ADD COLUMN `hotel_location` VARCHAR(255) NULL DEFAULT NULL AFTER `destination_name`,
  ADD COLUMN `flight_from` VARCHAR(100) NULL DEFAULT NULL AFTER `hotel_location`,
  ADD COLUMN `flight_to` VARCHAR(100) NULL DEFAULT NULL AFTER `flight_from`,
  ADD COLUMN `checkin_date` DATE NULL DEFAULT NULL AFTER `departure_date`,
  ADD COLUMN `checkout_date` DATE NULL DEFAULT NULL AFTER `checkin_date`,
  ADD COLUMN `passengers` INT(11) NULL DEFAULT NULL AFTER `checkout_date`;
