<?php
/**
 * Ez a szkript biztosítja a MantisBT adatbázisához történő kapcsolódást!
 * Ezen szkriptet tartalmazó modult csak akkor inicializálja, ha használni kívánja a MantisTools class-t!
 */

    function MantisConnect(){
		$required = ['user','pass','name','host'];
		foreach ($required as $con){
			if (!defined('MANTIS_'.strtoupper($con))){
				return 1;
			}
		}

		try {
			$db = new MysqliDb(MANTIS_HOST,MANTIS_USER,MANTIS_PASS,MANTIS_NAME);
			@$db->connect();
		}
		catch (Exception $e){
			return 2;
		}

		return $db;
    }

    $MantisDB = MantisConnect();