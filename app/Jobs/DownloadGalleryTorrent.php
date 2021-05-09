<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\gallery;

use App\Http\ApiClients\ExhentaiClient;
use App\Http\ApiClients\DelugeClient;

class DownloadGalleryTorrent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gid;
    protected $token;
    protected $title;
    protected $path;
    protected $torrents;

    protected $host;
    protected $port;
    protected $password;

    protected $gallery;

    // todo: dynamically estimate download time
    public $timeout = 0;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $gallery_data, array $torrent_params)
    {
        $attr = ['gid','token','title','torrents'];

        foreach ($gallery_data as $k => $v) {
            if (empty($v) && in_array($k, $attr)) {
                throw new \Exception("Missing gallery property: {$k}", 1);                
            }

            $this->{$k} = $v;
        }

        $attr = ['host','port','password'];

        foreach ($torrent_params as $k => $v) {
            if (empty($v) && in_array($k, $attr)) {
                throw new \Exception("Missing torrent client param: {$k}", 1);                
            }

            $this->{$k} = $v;
        }

        $this->path = $this->setFilePath($gallery_data['path'] ?? '');
    }

    protected function setFilePath(string $dir='')
    {
        if (!$dir) {
            $storage_info = config('filesystems.disks');
            $dir = $storage_info['local']['root'];
        }

        if (!preg_match('/\/$/', $dir)) {
            $dir .= '/';
        }

        //\Log::error('title', ['title' => $this->title]);

        $title = preg_replace('/[^\w\s\]\[\)\(]/u', '', $this->title);
        $title = preg_replace('/\s{2,}/', ' ', $title);

        //\Log::error('title', compact('title'));

        $tlen = strlen($title);
        $dlen = strlen($dir);
        $total_len = $tlen + $dlen;

        if ($total_len >= 230) {
            $title = substr($title, 0, $total_len - 230);
        }

        $dir .= $title;

        //\Log::error('directory', compact('dir'));

        if (!is_dir($dir)) {
            $created_dir = mkdir($dir, 0777, true);

            if (!$created_dir) {
                throw new \Exception("Couldn't create directory: {$dir}");
            }
        }
        
        return $dir . '/' . uniqid() . '.zip';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $context = [
            'gid' => $this->gid, 
            'torrents' => $this->torrents,
            'torrent_client_password' => $this->password, 
            'torrent_client_host' => $this->host, 
            'torrent_client_port' => $this->port
        ];

        $this->gallery = gallery::where('gid', $this->gid)->first();

        if (empty($this->gallery)) {
            $err = "Gallery {$this->gid} doesn't exist in database";

            \Log::error($err, $context);
            return;         
        }

        try {
            $this->deluge = new DelugeClient($this->password, $this->host, $this->port);

            $path = $this->saveTorrentFile($this->gid, $this->torrents);

            $context['path'] = $path;

            $torrent_id = $this->scheduleTorrentDownload($path);

            $context['torrent_id'] = $torrent_id;

            $status = $this->getTorrentStatus($torrent_id);

            $path = $status['result']['files'][0]['path'];
            $eta = $status['result']['eta'];

            $context['path'] = $path;
            $context['eta'] = $eta;

            // todo: add torrent model
            $this->gallery->archive_path = $path;
            
            // todo: schedule task to check torrent download finished
            $this->gallery->archived = true;
            $this->gallery->save();
        }

        catch (\Exception $e) {
            $this->retryOrDie($e->getMessage(), $context);
            return;
        }
    }

    protected function saveTorrentFile(int $gid, array $torrents) :string
    {
        $exhentai = new ExhentaiClient;

        // todo: save file with the gallery name before replacing forbidden chars
        foreach ($torrents as $t) {
            if (empty($t['hash'])) {
                continue;
            }

            $p = $this->path . $t['hash'] . '.torrent';

            $success = $exhentai->downloadTorrentFile($gid, $t['hash'], $p);

            if ($success) {
                return $p;
            }
        }

        throw new \Exception("No torrent could be downloaded");
    }

    protected function scheduleTorrentDownload(string $path)
    {
        $base64 = base64_encode(file_get_contents($path));

        $r = $this->deluge->addFile($path, $base64);

        if (empty($r) || !empty($r['error'])) {
            throw new \Exception($r['error'] ?? 'Deluge Request Error');            
        }

        return $r['result'];
    }

    protected function getTorrentStatus(string $id) :array
    {
        $r = $this->deluge->getTorrentStatus($id, ['status','state','eta','files']);

        if (!empty($r['error']) || empty($r['result']['files'][0]['path'])) 
        {
            throw new \Exception($r['error'] ?? 'Deluge Request Error', 1);
        }

        return $r;
    }

    protected function retryOrDie(string $error, array $context=[])
    {
        if ($this->attempts() > 3) {
            throw new \Exception($error, 1);
        }

        $x = ' (Retrying, ' . $this->attempts() . ' out of 3 attempts)';

        \Log::error($error . $x, $context);
        $this->release(180);
    }
}
