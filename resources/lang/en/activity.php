<?php

/**
 * Contains all of the translation strings for different activity log
 * events. These should be keyed by the value in front of the colon (:)
 * in the event name. If there is no colon present, they should live at
 * the top level.
 */
return [
    'auth' => [
        'fail' => 'Failed log in',
        'success' => 'Logged in',
        'password-reset' => 'Password reset',
        'reset-password' => 'Requested password reset',
        'checkpoint' => 'Two-factor authentication requested',
        'recovery-token' => 'Used two-factor recovery token',
        'token' => 'Solved two-factor challenge',
        'ip-blocked' => 'Blocked request from unlisted IP address for :identifier',
        'sftp' => [
            'fail' => 'Failed SFTP log in',
        ],
        'discord' => [
            'login' => 'Logged in with Discord',
        ],
        'reset-password-requested' => 'Requested a password reset link',
        'passkey' => [
            'add' => 'Added passkey :name',
            'remove' => 'Removed passkey :name',
            'login' => 'Logged in with passkey :name',
        ],
    ],
    'user' => [
        'user' => [
            'create' => 'Created a new user :email',
        ],
        'account' => [
            'email-changed' => 'Changed email from :old to :new',
            'password-changed' => 'Changed password',
            'discord-linked' => 'Linked a Discord account',
            'discord-unlinked' => 'Unlinked Discord',
        ],
        'api-key' => [
            'create' => 'Created new API key :identifier',
            'delete' => 'Deleted API key :identifier',
        ],
        'ssh-key' => [
            'create' => 'Added SSH key :fingerprint to account',
            'delete' => 'Removed SSH key :fingerprint from account',
        ],
        'two-factor' => [
            'create' => 'Enabled two-factor auth',
            'delete' => 'Disabled two-factor auth',
        ],
    ],
    'server' => [
        'reinstall' => 'Reinstalled server',
        'console' => [
            'command' => 'Executed ":command" on the server',
        ],
        'power' => [
            'start' => 'Started the server',
            'stop' => 'Stopped the server',
            'restart' => 'Restarted the server',
            'kill' => 'Killed the server process',
        ],
        'backup' => [
            'download' => 'Downloaded the :name backup',
            'delete' => 'Deleted the :name backup',
            'restore' => 'Restored the :name backup (deleted files: :truncate)',
            'restore-complete' => 'Completed restoration of the :name backup',
            'restore-failed' => 'Failed to complete restoration of the :name backup',
            'start' => 'Started a new backup :name',
            'complete' => 'Marked the :name backup as complete',
            'fail' => 'Marked the :name backup as failed',
            'lock' => 'Locked the :name backup',
            'unlock' => 'Unlocked the :name backup',
            'auto' => 'Set automatic backups to every :hours hours (0 = off)',
        ],
        'database' => [
            'create' => 'Created new database :name',
            'rotate-password' => 'Password rotated for database :name',
            'delete' => 'Deleted database :name',
            'open-manager' => 'Opened database :name in phpMyAdmin',
        ],
        'file' => [
            'compress_one' => 'Compressed :directory:files.0',
            'compress_other' => 'Compressed :count files in :directory',
            'read' => 'Viewed the contents of :file',
            'copy' => 'Created a copy of :file',
            'create-directory' => 'Created directory :directory:name',
            'decompress' => 'Decompressed :files in :directory',
            'delete_one' => 'Deleted :directory:files.0',
            'delete_other' => 'Deleted :count files in :directory',
            'download' => 'Downloaded :file',
            'pull' => 'Downloaded a remote file from :url to :directory',
            'rename_one' => 'Renamed :directory:files.0.from to :directory:files.0.to',
            'rename_other' => 'Renamed :count files in :directory',
            'write' => 'Wrote new content to :file',
            'upload' => 'Began a file upload',
            'uploaded' => 'Uploaded :directory:file',
            'restore_one' => 'Restored :files.0 from the trash',
            'restore_other' => 'Restored :count files from the trash',
            'trash' => [
                'purge' => 'Deleted :count entries from the trash for good',
            ],
        ],
        'sftp' => [
            'denied' => 'Blocked SFTP access due to permissions',
            'create_one' => 'Created :files.0',
            'create_other' => 'Created :count new files',
            'write_one' => 'Modified the contents of :files.0',
            'write_other' => 'Modified the contents of :count files',
            'delete_one' => 'Deleted :files.0',
            'delete_other' => 'Deleted :count files',
            'create-directory_one' => 'Created the :files.0 directory',
            'create-directory_other' => 'Created :count directories',
            'rename_one' => 'Renamed :files.0.from to :files.0.to',
            'rename_other' => 'Renamed or moved :count files',
        ],
        'allocation' => [
            'create' => 'Added :allocation to the server',
            'notes' => 'Updated the notes for :allocation from ":old" to ":new"',
            'primary' => 'Set :allocation as the primary server allocation',
            'delete' => 'Deleted the :allocation allocation',
        ],
        'schedule' => [
            'create' => 'Created the :name schedule',
            'update' => 'Updated the :name schedule',
            'execute' => 'Manually executed the :name schedule',
            'delete' => 'Deleted the :name schedule',
        ],
        'task' => [
            'create' => 'Created a new ":action" task for the :name schedule',
            'update' => 'Updated the ":action" task for the :name schedule',
            'delete' => 'Deleted a task for the :name schedule',
        ],
        'settings' => [
            'rename' => 'Renamed the server from :old to :new',
            'description' => 'Changed the server description from :old to :new',
        ],
        'startup' => [
            'edit' => 'Changed the :variable variable from ":old" to ":new"',
            'image' => 'Updated the Docker Image for the server from :old to :new',
        ],
        'subuser' => [
            'create' => 'Added :email as a subuser',
            'update' => 'Updated the subuser permissions for :email',
            'delete' => 'Removed :email as a subuser',
        ],
        'players' => [
            'whitelist_add' => 'Added :target to the whitelist',
            'whitelist_remove' => 'Removed :target from the whitelist',
            'op' => 'Made :target an operator',
            'deop' => 'Removed operator :target',
            'ban' => 'Banned :target',
            'pardon' => 'Unbanned :target',
            'ban_ip' => 'Banned IP :target',
            'pardon_ip' => 'Unbanned IP :target',
            'kick' => 'Kicked :target',
            'whitelist_on' => 'Turned the whitelist on',
            'whitelist_off' => 'Turned the whitelist off',
        ],
        'clone' => 'Copied all files from :source to :target',
        'sleep' => [
            'update' => 'Changed the sleep mode settings',
        ],
        'crashed' => 'The server crashed',
        'software' => [
            'install' => 'Installed :type :version',
        ],
        'ownership' => [
            'offer' => 'Offered the server to :to',
            'cancel' => 'Withdrew the offer to give the server away',
            'accept' => 'The server was handed over from :from to :to',
            'decline' => ':to declined the server',
        ],
        'geyser' => [
            'install' => 'Installed Geyser and Floodgate (Bedrock players)',
            'uninstall' => 'Removed Geyser and Floodgate',
        ],
        'subdomain' => [
            'set' => 'Set the subdomain :subdomain',
            'delete' => 'Removed the subdomain',
        ],
    ],
    'meta' => [
        'system_user' => 'System User',
        'system' => 'System',
        'using_api_key' => 'Using API Key',
        'using_sftp' => 'Using SFTP',
        'clear_filters' => 'Clear Filters',
        'server_title' => 'Activity Log',
        'server_empty' => 'No activity logs available for this server.',
        'account_title' => 'Account Activity Log',
    ],
];
