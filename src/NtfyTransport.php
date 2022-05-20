<?php

namespace Ntfy\Symfony\Component\Notifier\Bridge\Ntfy;

use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class NtfyTransport extends AbstractTransport
{
    protected const HOST = 'ntfy.sh';

    private $dsn;
    private $topic;
    private $user;
    private $password;
    private $scheme;

    public function __construct(Dsn $dsn, string $topic, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->dsn = $dsn;
        $this->topic = $topic;

        parent::__construct($client, $dispatcher);
    }

    private static function getNtfyHeadersFromMessage(PushMessage $message): array
    {
        $notification = $message->getNotification();
        $ntfyHeaders = [];

        $ntfyHeaders['Title'] = $notification->getSubject() ?? $message->getSubject();

        $priority = $notification->getImportance() ?? Notification::IMPORTANCE_LOW;
        if ($priority === Notification::IMPORTANCE_MEDIUM) {
            $priority = 'default';
        }
        $ntfyHeaders['Priority'] = $priority;

        if ($notification !== null && !empty($notification->getEmoji())) {
            $ntfyHeaders['Tags'] = [$notification->getEmoji()];
        }

        return $ntfyHeaders;
    }

    public function setUser(?string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setScheme(?string $scheme): self
    {
        $this->scheme = $scheme;

        return $this;
    }

    public function __toString(): string
    {
        return $this->dsn->getOriginalDsn();
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof PushMessage &&
            (null === $message->getOptions() || $message->getOptions() instanceof NtfyOptions);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof PushMessage) {
            throw new UnsupportedMessageTypeException(
                __CLASS__,
                PushMessage::class,
                $message
            );
        }

        if (($options = $message->getOptions()) && !$options instanceof NtfyOptions) {
            throw new LogicException(
                sprintf('The "%s" transport only supports instances of "%s" for options.',
                    __CLASS__,
                    NtfyOptions::class
                )
            );
        }

        if (null === $options) {
            $options = new NtfyOptions();
        }

        $endpoint = $this->scheme . '://' . $this->getEndpoint();

        $messageContent = $message->getContent();

        $notificationHeaders = self::getNtfyHeadersFromMessage($message);
        $ntfyMessageOptionHeaders = (array)$options;

        if (isset($notificationHeaders['Tags'])) {  // handle emoji from Notification
            $tags = $notificationHeaders['Tags'];
            if (isset($ntfyMessageOptionHeaders['Tags'])) {
                $tags .= ',' . $ntfyMessageOptionHeaders['Tags'];
            }
            $ntfyMessageOptionHeaders['Tags'] = $tags;
            unset($notificationHeaders['Tags']);
        }

        $messageOptions = [
            'headers' => array_merge($notificationHeaders, $ntfyMessageOptionHeaders),
            'body' => $messageContent,
        ];

        if (!empty($this->user) && !empty($this->password)) {  // if DSN is configured to have user/pass, set it here
            $messageOptions['auth_basic'] = [$this->user, $this->password];
        }

        // send off the message
        $response = $this->client->request('POST', $endpoint, $messageOptions);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the ntfy server.', $response, 0, $e);
        }

        if (200 !== $statusCode) {
            $errorMessage = $response->getContent(false);

            throw new TransportException('Unable to post the ntfy message: ' . $errorMessage, $response);
        }

        $success = $response->toArray(false);

        $sentMessage = new SentMessage($message, (string)$this);
        $sentMessage->setMessageId($success['id']);

        return $sentMessage;
    }

    protected function getEndpoint(): string
    {
        $baseUri = parent::getEndpoint();

        return $baseUri . $this->topic;
    }
}
