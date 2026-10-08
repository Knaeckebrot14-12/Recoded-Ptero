<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file or folder deleted in the file manager and kept in the server's .trash folder, or
 * (kind "clone") everything a server had before the files of another server were copied into it.
 *
 * @property int $id
 * @property int $server_id
 * @property int|null $user_id
 * @property string $kind
 * @property string $batch
 * @property string $original_path
 * @property string $trash_path
 * @property bool $is_file
 * @property int $size
 * @property string|null $label
 * @property \Carbon\CarbonImmutable $created_at
 */
class ServerTrashItem extends Model
{
    public const KIND_FILE = 'file';
    public const KIND_CLONE = 'clone';

    public const UPDATED_AT = null;

    protected $table = 'server_trash';

    protected $guarded = ['id', 'created_at'];

    protected $casts = [
        'is_file' => 'boolean',
        'size' => 'integer',
        'created_at' => 'immutable_datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
