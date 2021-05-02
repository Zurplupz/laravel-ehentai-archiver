<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DownloadGalleryTorrent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gid;
    protected $token;
    protected $path;
    protected $torrents;

    protected $gallery;

    // todo: dynamically estimate download time
    public $timeout = 0;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $gallery_data)
    {
        $attr = ['gid','token','torrents'];

        foreach ($gallery_data as $k => $v) {
            if (empty($v) && in_array($k, $attr)) {
                throw new \Exception("Missing gallery property: {$k}", 1);                
            }

            $this->{$k} = $v;
        }

        if (!empty($gallery_data['path'])) {

            $path = $gallery_data['path'];

            if (!preg_match('/\/$/', $path)) {
                $path .= '/';
            }

            $this->path = $path . uniqid() . '.zip';

        } else {
            $storage_info = config('filesystems.disks');

            $path = $storage_info['local']['root'];

            $this->path = $path . '/' . uniqid() . '.zip';
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
