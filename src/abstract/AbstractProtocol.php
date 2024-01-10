<?php namespace Starutils\Starcorn\abstract;


use Socket;
use Starutils\Starcorn\{Config, StarSocket};


abstract class AbstractProtocol
{
    protected string $protocol;
    protected string $protocol_version;

    protected array $clients_read = array();
    protected array $clients_write = array();
    protected array $clients_except = array();

    protected array $clients_buffer = array();
    protected array $clients_message = array();

    protected StarSocket $socket;
    protected static Config $config;

    public function __construct($config)
    {
        self::$config = $config;
        $this->init();
    }

    public function socket(): StarSocket
    {
        return $this->socket;
    }

    public function clients(): array
    {
        return [$this->clients_read, $this->clients_write, $this->clients_except];
    }

    protected abstract function init(): void;

    public abstract function request_handler(string $id): void;

    public abstract function connect(string $key): void;

    public abstract function disconnect(string $id, Socket $client): void;

    public abstract function set_buffer(string $id, string $value): void;

    public abstract function get_buffer(string $id): ?string;

    public abstract function set_message(string $id, string $value): void;

    public abstract function get_message(string $id): string;
}
