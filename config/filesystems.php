<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
         * The `url` is deliberately root relative instead of being built from
         * APP_URL. The same installation is reached through several hosts at
         * once: the Herd domain on the developer's machine, 127.0.0.1 while
         * `artisan serve` is up, and the LAN address of the machine on every
         * phone that scans a table QR code. Baking one host into the URL means
         * every photo and every .glb is requested from a host the device may
         * not even be able to reach, and a photograph the browser cannot fetch
         * is indistinguishable from a dish that has none. A relative URL is
         * resolved against whatever host the page was actually served from.
         */
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
         * Supabase Storage speaks the S3 protocol, so the regular S3 driver is
         * enough. It is only used when MENUVISION_DISK=supabase.
         *
         * Two details differ from AWS and are easy to get wrong:
         *
         * - `use_path_style_endpoint` must be true: Supabase serves the bucket
         *   in the path, not in the host name.
         * - `url` has to be the public object prefix of the bucket, not the S3
         *   endpoint. The endpoint (`.../storage/v1/s3`) requires signed
         *   requests and a browser cannot use it directly. The public prefix is
         *   `https://<ref>.supabase.co/storage/v1/object/public/<bucket>`, and
         *   it only works when the bucket is public for reads. Writes and
         *   deletes still go through the authenticated S3 endpoint.
         *
         * `throw` is on so a failed upload raises instead of returning false and
         * leaving a row pointing at a file that was never written.
         */
        'supabase' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => true,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
