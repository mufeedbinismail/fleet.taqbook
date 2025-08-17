<?php

class AddTrackingNoColToSoAndDebtorTransTable
{
    private mysqli $mysqli;

    public function __construct() {
        include __DIR__ . "/../config_db.php";

        $this->mysqli = new mysqli(
            $db_connections[0]['host'],
            $db_connections[0]['dbuser'],
            $db_connections[0]['dbpassword'],
            $db_connections[0]['dbname']
        );

        if ($this->mysqli->connect_error) {
            throw new Exception("Connection failed: " . $this->mysqli->connect_error);
        }
    }
    public function up()
    {
        $sql = "ALTER TABLE `debtor_trans` ADD COLUMN `tracking_no` VARCHAR(50) NULL DEFAULT NULL AFTER `reference`;";
        if (!$this->mysqli->query($sql)) {
            throw new Exception("Error adding tracking_no column to debtor_trans table: " . $this->mysqli->error);
        }

        $sql = "ALTER TABLE `sales_orders` ADD COLUMN `tracking_no` VARCHAR(50) NULL DEFAULT NULL AFTER `reference`;";
        if (!$this->mysqli->query($sql)) {
            throw new Exception("Error adding tracking_no column to sales_orders table: " . $this->mysqli->error);
        }
    }

    public function down()
    {
        $sql = "ALTER TABLE `debtor_trans` DROP COLUMN `tracking_no`;";
        if (!$this->mysqli->query($sql)) {
            throw new Exception("Error removing tracking_no column from debtor_trans table: " . $this->mysqli->error);
        }

        $sql = "ALTER TABLE `sales_orders` DROP COLUMN `tracking_no`;";
        if (!$this->mysqli->query($sql)) {
            throw new Exception("Error removing tracking_no column from sales_orders table: " . $this->mysqli->error);
        }
    }
}