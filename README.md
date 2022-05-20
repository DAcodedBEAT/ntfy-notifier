Ntfy Notifier
================

Provides [Ntfy](https://github.com/binwiederhier/ntfy) integration for Symfony Notifier.

DSN example
-----------

```
NTFY_DSN=ntfy://[USER:PASSWORD@]NTFY_URL/TOPIC?[scheme=[https]]
```

where:

- `NTFY_URL` is the ntfy server which you are using
  - if `default` is provided, this will default to the public ntfy server hosted on [ntfy.sh](https://ntfy.sh/).
  - _note_: you can provide specific ports here if the selfhosted ntfy server is running on a non-standard web port.
    - example: `NTFY_DSN=ntfy://foo.bar:8080/myntfytopic`
- `TOPIC` is the topic on this ntfy server.

- Depending on whether the server is configured to support access control:
  - `USER` is the username to access the given topic on the ntfy server which you are using
  - `PASSWORD` is the username to access the given topic on the ntfy server which you are using

Optional configuration:
- `scheme` should be adjusted to the appropriate value for the in the ntfy server (defaults to `https` if not set)
  - example: `http` should be used if the ntfy server is listening on the insecure HTTP protocol 

