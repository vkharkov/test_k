<?php

require_once './const.php';


/**
 *
 * Check email
 *
 * @param string $email
 * @return bool
 */
function checkEmail(string $email) : bool
{

    sleep(rand(1,60));
    return true;

}

$link = mysqli_connect("localhost", "root");
mysqli_select_db($link, "test");


$idCursor = null;

/**
 * Цикл чтобы не таскать из базы много данных за раз
 */
do {

    /**
     * Немного sprintf чтобы вынести интервал уведомления в константу/конфиг
     */
    $query = sprintf("SELECT e.id as cursor, e.email FROM emails e
        LEFT JOIN users u ON u.id=e.user_id
        WHERE
            e.checked=0
            ". $idCursor."
            AND DATE_FORMAT(FROM_UNIXTIME(u.validts), '%%Y-%%m-%%d') = DATE_ADD(CURDATE(), INTERVAL %u DAY)
            LIMIT 50
    ", (NOTIFICATION_INTERVAL+2));

    $result = mysqli_query($link, $query);

    while ($row = $result->fetch_assoc()) {

        try {

            if ( checkEmail($row['email']) === true) {
                mysqli_query($link,"UPDATE emails SET valid=1, checked=1, checkts=unix_timestamp() WHERE id=" . $row['cursor']);
            } else {
                mysqli_query($link,"UPDATE emails SET valid=0, checked=1, checkts=unix_timestamp() WHERE id=" . $row['cursor']);
            }

        } catch (\Throwable $e) {
            // Log, break, do whatever you want
        }

        /**
         * Cheap cursor pagination for MySQL
         * Простенькая пагинация курсором для бедных. В случае с PostgreSQL стоит использовать возможности базы
         */
        $idCursor = ' AND u.id > ' . $row['cursor'];

    }

} while ( mysqli_num_rows($result) > 0);

