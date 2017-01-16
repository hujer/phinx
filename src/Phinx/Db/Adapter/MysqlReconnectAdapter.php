<?php

namespace Phinx\Db\Adapter;

/**
 * MySQL Adapter reconnecting automatically when MySQL connection times out on long-running migrations
 */
class MysqlReconnectAdapter extends MysqlAdapter
{
    const MYSQL_SERVER_GONE_AWAY = '2006 MySQL server has gone away';

    /**
     * Reconnect if $callable call fails with MySQL server has gone away and call $callable again.
     * Fail if any other error occurs or if any error occurs after reconnecting
     *
     * @param $callable
     * @return mixed
     * @throws \Exception
     * @throws \PDOException
     */
    protected function wrapReconnectOnGoneAway($callable) {

        try {

            return $callable();

        }  catch (\PDOException $e) {

            // $e->getCode() returns generic 'HY000' so we have to strpos over the message
            if (strpos($e->getMessage(), self::MYSQL_SERVER_GONE_AWAY) !== false) {

                $this->connection = NULL;   // $this->connect() reconnects only if null === $this->connection
                $this->connect();

                return $callable();
            }

            throw $e;
        }

    }

    /**
     * {@inheritdoc}
     */
    public function execute($sql)
    {
        return $this->wrapReconnectOnGoneAway(function() use ($sql) {

            return parent::execute($sql);

        });
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql)
    {
        return $this->wrapReconnectOnGoneAway(function() use ($sql) {

            return parent::query($sql);

        });
    }


}