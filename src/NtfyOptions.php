<?php

namespace Ntfy\Symfony\Component\Notifier\Bridge\Ntfy;

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final class NtfyOptions implements MessageOptionsInterface
{
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Passed directly to Request Headers
     */
    public function toArray(): array
    {
        $options = array_merge(['Content-Type' => 'text/plain'], $this->options);
        if (isset($options['Tags'])) {
            $options['Tags'] = implode(',', $options['Tags']);
        }

        return $options;
    }

    public function getRecipientId(): ?string
    {
        return null;
    }

    /**
     * @see https://ntfy.sh/docs/publish/#scheduled-delivery
     */
    public function scheduleMessage(\DateTimeInterface $dateTime): self
    {
        $this->options['Delay'] = $dateTime->getTimestamp();

        return $this;
    }

    /**
     * @see https://ntfy.sh/docs/publish/#tags-emojis
     */
    public function tags(array $tags): self
    {
        $this->options['Tags'] = $tags;

        return $this;
    }

    /**
     * @see https://ntfy.sh/docs/publish/#click-action
     */
    public function click(string $url): self
    {
        $this->options['Click'] = $url;

        return $this;
    }

    /**
     * @see https://ntfy.sh/docs/publish/#attach-file-from-a-url
     */
    public function attachFromUrl(string $url): self
    {
        $this->options['Attach'] = $url;

        return $this;
    }

    /**
     * @see https://ntfy.sh/docs/publish/#e-mail-notifications
     */
    public function email(string $email): self
    {
        $this->options['Email'] = $email;

        return $this;
    }

    /**
     * @see https://ntfy.sh/docs/publish/#message-caching
     */
    public function disableCache(): self
    {
        $this->options['Cache'] = 'no';

        return $this;
    }

    /**
     * @see https://ntfy.sh/docs/publish/#disable-firebase
     */
    public function disableFirebase(): self
    {
        $this->options['Firebase'] = 'no';

        return $this;
    }
}
