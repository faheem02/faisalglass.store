-- Migration: Drop FOREIGN KEY constraints on employee_id columns
-- This allows deleting employees without cascade-restricting
-- Transaction data (salary, payments, ledger, cashbook, bankbook) remains intact
-- Run this ONCE on your database

ALTER TABLE `employee_ledger` DROP FOREIGN KEY `employee_ledger_ibfk_1`;
ALTER TABLE `employee_payments` DROP FOREIGN KEY `employee_payments_ibfk_1`;
ALTER TABLE `employee_salary` DROP FOREIGN KEY `employee_salary_ibfk_1`;
