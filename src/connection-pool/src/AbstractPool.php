<?php declare(strict_types=1);


namespace Swoft\Connection\Pool;

use Swoft\Connection\Pool\Exception\ConnectionPoolException;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

/**
 * Class AbstractPool
 *
 * @since 2.0
 */
abstract class AbstractPool implements PoolInterface
{
    /**
     * Minimum active number of connections
     *
     * @var int
     */
    protected $minActive = 5;

    /**
     * Maximum active number of connections
     *
     * @var int
     */
    protected $maxActive = 10;

    /**
     * Maximum waiting for the number of connections, if there is no limit to 0
     *
     * @var int
     */
    protected $maxWait = 0;

    /**
     * Maximum waiting time(second), if there is not limit to 0
     *
     * @var float
     */
    protected $maxWaitTime = 0;

    /**
     * Maximum idle time(second)
     *
     * @var int
     */
    protected $maxIdleTime = 60;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var \SplQueue
     */
    protected $queue;

    /**
     * Current count
     *
     * @var int
     */
    protected $count = 0;

    /**
     * Next connect id
     *
     * @var int
     */
    protected $connectionId = 0;

    /**
     * @return int
     */
    public function getConnectionId(): int
    {
        $this->connectionId++;
        return $this->connectionId;
    }

    /**
     * @return ConnectionInterface
     * @throws ConnectionPoolException
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->getConnectionByChannel();
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function release(ConnectionInterface $connection): void
    {
        $this->releaseToChannel($connection);
    }

    /**
     * Remove connection by reconnect error
     */
    public function remove(): void
    {
        $this->count--;
    }

    /**
     * Get connection by channel
     *
     * @return ConnectionInterface
     * @throws ConnectionPoolException
     */
    private function getConnectionByChannel(): ConnectionInterface
    {
        // Create channel
        if ($this->channel === null) {
            $this->channel = new Channel($this->maxActive);
        }

        // To reach `minActive` number
        if ($this->count < $this->minActive) {
            return $this->create();
        }

        // Pop connection
        $connection = null;
        if (!$this->channel->isEmpty()) {
            $connection = $this->popByChannel();
        }

        // Pop connection is not null
        if ($connection !== null) {
            return $connection;
        }

        // Channel is empty or  not reach `maxActive` number
        if ($this->count < $this->maxActive) {

            return $this->create();
        }

        // Out of `maxWait` number
        $stats = $this->channel->stats();
        if ($this->maxWait > 0 && $stats['consumer_num'] >= $this->maxWait) {
            throw new ConnectionPoolException(
                sprintf('Channel consumer is full, maxActive=%d, maxWait=%d, currentCount=%d',
                    $this->maxActive, $this->maxWaitTime, $this->count)
            );
        }

        // Sleep coroutine and resume coroutine after `maxWaitTime`, Return false is waiting timeout
        $connection = $this->channel->pop($this->maxWaitTime);
        if ($connection === false) {
            throw new ConnectionPoolException(
                sprintf('Channel pop timeout by %fs', $this->maxWaitTime)
            );
        }

        return $connection;
    }

    /**
     * @return ConnectionInterface
     *
     * @throws ConnectionPoolException
     */
    private function create(): ConnectionInterface
    {
        // Count before to fix more connection bug
        $this->count++;

        try {
            $connection = $this->createConnection();
        } catch (\Throwable $e) {
            // Create error to reset count
            $this->count--;

            throw new ConnectionPoolException(
                sprintf('Create connection error(%s)', $e->getMessage())
            );
        }

        return $connection;
    }

    /**
     * Pop by channel
     *
     * @return ConnectionInterface|null
     */
    private function popByChannel(): ?ConnectionInterface
    {
        $time       = time();
        $connection = null;

        while (!$this->channel->isEmpty()) {
            /* @var ConnectionInterface $connection */
            $connection = $this->channel->pop();
            $lastTime   = $connection->getLastTime();

            // Out of `maxIdleTime`
            if ($time - $lastTime > $this->maxIdleTime) {
                $this->count--;
                continue;
            }

            return $connection;
        }

        return $connection;
    }

    /**
     * Release to channel
     *
     * @param ConnectionInterface $connection
     */
    private function releaseToChannel(ConnectionInterface $connection)
    {
        $stats = $this->channel->stats();
        if ($stats['queue_num'] < $this->maxActive) {
            $this->channel->push($connection);
        }
    }
}