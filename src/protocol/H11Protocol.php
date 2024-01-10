<?php namespace Starutils\Starcorn\protocol;


require_once(__DIR__.'/../StarSocket.php');
require_once(__DIR__.'/../abstract/AbstractProtocol.php');
require_once(__DIR__.'/../enum/EnumHTTPMethod.php');

use Socket;
use Starutils\Starcorn\StarSocket;
use Starutils\Starcorn\abstract\AbstractProtocol;
use Starutils\Starcorn\enum\EnumHTTPMethod;


final class H11Protocol extends AbstractProtocol
{
    protected static string $sep = "\r\n";

    protected string $protocol = 'HTTP';
    protected string $protocol_version = '1.1';

    protected function init(): void
    {
        $this->socket = new StarSocket(domain: self::$config->socket_domain(),
                                       type: self::$config->socket_type(),
                                       protocol: self::$config->socket_protocol());

        $this->socket->bind(address: self::$config->host(), port: self::$config->port());
        $this->socket->listen(self::$config->socket_backlog());
    }

    public function request_handler(string $id): void
    {
        $scope = [];
        $this->set_start_line($id, $scope);
        if ($this->is_current_request($scope))
        {
            $this->set_client_address($id, $scope);
            $this->parse_body_request($id, $scope);

            $app = self::$config->app();
            $content = $app($scope);
            $this->clients_write[$id] = $this->clients_read[$id];
            $this->set_message($id, $content);
        }
        unset($this->clients_read[$id], $this->clients_buffer[$id], $scope);
    }

    public function connect(string $key): void
    {
        $socket = $this->socket->accept();
        $this->clients_read[$key] = $socket;
    }

    public function disconnect(string $id, Socket $client): void {
        $this->socket::close($client);
        unset(
            $this->clients_read[$id],
            $this->clients_write[$id],
            $this->clients_except[$id],
            $this->clients_buffer[$id],
            $this->clients_message[$id]
        );
    }

    public function set_buffer(string $id, string $value, bool $add = true): void
    {
        if($add) @$this->clients_buffer[$id] .= $value;
        else $this->clients_buffer[$id] = $value;
    }

    public function get_buffer(string $id): ?string
    {
        if(array_key_exists($id, $this->clients_buffer))
        {
            return $this->clients_buffer[$id];
        }
        return null;
    }

    public function set_message(string $id, string $value): void
    {
        $this->clients_message[$id] = $value;
    }

    public function get_message(string $id): string
    {
        return $this->clients_message[$id];
    }

    protected function set_start_line(string $id, array &$scope): void
    {
        [$row, $buffer] = explode(self::$sep, $this->get_buffer($id), 2);
        [$method, $path, $protocol] = explode(' ', $row);
        $scope['method'] = $method;
        $scope['path'] = $path;

        [$protocol, $protocol_version] = explode("/", $protocol);
        $scope['protocol'] = $protocol;
        $scope['protocol_version'] = $protocol_version;

        $this->set_buffer($id, $buffer, false);
    }

    protected function set_client_address(string $id, array &$scope): void
    {
        socket_getpeername($this->clients_read[$id], $ip, $port);
        $scope['ip'] = $ip;
        $scope['port'] = $port;
    }

    protected function is_current_request(array $scope): bool
    {
        if($this->protocol !== $scope['protocol'] ||
            $this->protocol_version !== $scope['protocol_version']) return false;

        if (!EnumHTTPMethod::tryFrom($scope['method'])) return false;

        return true;
    }

    protected function parse_body_request(string $id, array &$scope): void
    {
        $body = '';
        $headers = array();
        $buffer = $this->clients_buffer[$id];

        while($buffer)
        {
            [$row, $buffer] = explode(self::$sep, $buffer, 2);
            @[$name, $value] = explode(":", $row);

            if($name and $value) $headers[trim($name)] = trim($value);
            else
            {
                $body = trim($buffer);
                break;
            }
        }

        $scope['body'] = $body;
        $scope['headers'] = $headers;
    }
}
