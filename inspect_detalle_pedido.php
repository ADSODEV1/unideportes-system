<?php
$c = mysqli_connect('127.0.0.1', 'root', '', 'unideportes');
if (!$c) {
    echo 'CONNECT FAIL ' . mysqli_connect_error();
    exit(1);
}
mysqli_set_charset($c, 'utf8mb4');
$result = mysqli_query($c, 'SHOW CREATE TABLE detalle_pedido');
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo $row['Create Table'];
} else {
    echo 'QUERY FAIL ' . mysqli_error($c);
}
?>