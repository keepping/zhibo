<?php

/**
 *
 */
class Connect
{
    private static $transaction;

    /**
     * @return mixed
     */
    public static function beginTransaction()
    {
        self::$transaction = $GLOBALS['db']->StartTrans();
    }

    public static function rollback()
    {
        $GLOBALS['db']->Rollback(self::$transaction);
    }

    public static function commit()
    {
        $GLOBALS['db']->Commit(self::$transaction);
    }

    public static function inTransaction()
    {
        return $GLOBALS['db']->InTransaction();
    }

    public static function lastInsertId()
    {
        return $GLOBALS['db']->insert_id();
    }

    public static function exec($sql)
    {
        return $GLOBALS['db']->query($sql) ? $GLOBALS['db']->affected_rows() : false;
    }
    /**
     * @param      $sql
     * @param bool $is_all
     * @return mixed
     */
    public static function query($sql, $is_all = true)
    {
        return $is_all ? $GLOBALS['db']->getAll($sql, 1, 1) : $GLOBALS['db']->getRow($sql, 1, 1);
    }
}
