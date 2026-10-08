<?php

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

/**
 * Recoded Ptero's Wings: copy all files of another server on the same node into a server.
 */
class DaemonCloneRepository extends DaemonRepository
{
    /**
     * Starts copying $source into the server set on this repository. Wings answers at once;
     * the copy runs in the background (see status()).
     *
     * @throws DisplayException when Wings refuses (e.g. a server is running)
     * @throws DaemonConnectionException
     */
    public function start(Server $source, string $batch): array
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api/servers/%s/recoded/clone', $this->server->uuid), [
                'json' => ['source' => $source->uuid, 'batch' => $batch],
            ]);
        } catch (ClientException $exception) {
            $body = json_decode((string) $exception->getResponse()->getBody(), true);
            if (in_array($exception->getResponse()->getStatusCode(), [404, 409], true) && is_string($body['error'] ?? null)) {
                throw new DisplayException($body['error']);
            }
            throw new DaemonConnectionException($exception);
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * The status of the last copy into the server, or null when Wings knows of none.
     *
     * @throws DaemonConnectionException
     */
    public function status(): ?array
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api/servers/%s/recoded/clone', $this->server->uuid));
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404) {
                return null;
            }
            throw new DaemonConnectionException($exception);
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }

        return json_decode((string) $response->getBody(), true);
    }
}
