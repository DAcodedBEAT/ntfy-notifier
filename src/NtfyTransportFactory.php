<?php

namespace Ntfy\Symfony\Component\Notifier\Bridge\Ntfy;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

final class NtfyTransportFactory extends AbstractTransportFactory
{
    /**
     * @return NtfyTransport
     */
    public function create(Dsn $dsn): TransportInterface
    {
        if ('ntfy' !== $dsn->getScheme()) {
            throw new UnsupportedSchemeException($dsn, 'ntfy', $this->getSupportedSchemes());
        }

        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $topic = $dsn->getPath();
        $transport = (new NtfyTransport($dsn, $topic))->setHost($host);
        if (!empty($port = $dsn->getPort())) {
            $transport->setPort($port);
        }

        if (!empty($user = $dsn->getUser()) && !empty($password = $dsn->getPassword())) {
            $transport->setUser($user);
            $transport->setPassword($password);
        }

        $transport->setScheme($dsn->getOption('scheme', 'https'));

        return $transport;
    }

    protected function getSupportedSchemes(): array
    {
        return ['ntfy'];
    }
}
