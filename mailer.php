<?php

require_once './const.php';

/**
 *
 * Sends email
 *
 * @param string $email
 * @param string $from
 * @param string $to
 * @param string $subj
 * @param string $body
 * @return bool
 */
function sendEmail(string $email, string $from, string $to, string $subj, string $body) : bool
{

    sleep(rand(1,10));
    return true;

}

/**
 *
 * Make notification body
 *
 * @param string $username
 * @return string
 */
function makeBody(string $username) : string
{
    return sprintf('%s, your subscription is expiring soon', $username);
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
    $query = sprintf("SELECT u.id as cursor, u.username, e.email FROM users u
        LEFT JOIN emails e ON e.user_id=u.id
        WHERE
            u.confirmed=1
            AND e.valid=1
            ". $idCursor."
            AND DATE_FORMAT(FROM_UNIXTIME(e.checkts), '%%Y-%%m-%%d') >= DATE_SUB(CURDATE(), INTERVAL %u DAY)
            AND DATE_FORMAT(FROM_UNIXTIME(e.checkts), '%%Y-%%m-%%d') <= DATE_ADD(CURDATE(), INTERVAL %u DAY)
            AND DATE_FORMAT(FROM_UNIXTIME(u.validts), '%%Y-%%m-%%d') = DATE_ADD(CURDATE(), INTERVAL %u DAY)
            LIMIT 50
    ", (NOTIFICATION_INTERVAL - 1), NOTIFICATION_INTERVAL);

    $result = mysqli_query($link, $query);

    while ($row = $result->fetch_assoc()) {

        try {
            sendEmail($row['email'], 'Karma8', $row['username'], 'Subscription expire soon', makeBody($row['userrname']));
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

