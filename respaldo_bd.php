<?php
require __DIR__ . '/auth.php'; require_login(); require __DIR__ . '/conexion.php';
$nombre='respaldo_farmacia_' . date('Y-m-d_H-i-s') . '.sql';
header('Content-Type: application/sql; charset=utf-8');header('Content-Disposition: attachment; filename="'.$nombre.'"');
echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n";
$tablas=$mysqli->query('SHOW TABLES');
while($t=$tablas->fetch_row()){$tabla=$t[0];$safe=str_replace('`','``',$tabla);$ddl=$mysqli->query('SHOW CREATE TABLE `'.$safe.'`')->fetch_assoc();echo "\nDROP TABLE IF EXISTS `{$safe}`;\n".$ddl['Create Table'].";\n";$filas=$mysqli->query('SELECT * FROM `'.$safe.'`');while($r=$filas->fetch_assoc()){$cols=[];$vals=[];foreach($r as $col=>$val){$cols[]='`'.str_replace('`','``',$col).'`';$vals[]=$val===null?'NULL':"'".$mysqli->real_escape_string((string)$val)."'";}echo 'INSERT INTO `'.$safe.'` ('.implode(',',$cols).') VALUES ('.implode(',',$vals).");\n";}}
echo "SET FOREIGN_KEY_CHECKS=1;\n";
