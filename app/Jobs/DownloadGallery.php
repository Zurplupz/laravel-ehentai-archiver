<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\ApiClients\ExhentaiClient;
use Symfony\Component\DomCrawler\Crawler;
use App\Http\ApiClients\LiteDownloader;
use App\Repositories\GalleryRepo;

// todo: schedule downloads
// todo: when scheduled download, get new archiver key from api
// todo: check when it was added or last updated, if old get new archiver key
class DownloadGallery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gid;
    protected $token;
    protected $archiver_key;
    protected $path;

    // todo: dynamically estimate download time
    public $timeout = 0;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $gallery_data)
    {
        $attr = ['gid','token','archiver_key'];

        foreach ($gallery_data as $k => $v) {
            if (empty($v) && in_array($k, $attr)) {
                throw new \Exception("Missing gallery property: {$k}", 1);                
            }

            $this->{$k} = $v;
        }

        if (!empty($gallery_data['path'])) {
            $this->path = $gallery_data['path'] . '/' . uniqid() . '.zip';

        } else {
            $storage_info = config('filesystems.disks');

            $path = $storage_info['local']['root'];

            $this->path = $path . '/' . md5(uniqid()) . '.zip';
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $galleries = new GalleryRepo;

        $g = $galleries->gid($this->gid)->first(false);

        if (empty($g)) {
            $err = "Gallery {$this->gid} doesn't exist in database";

            \Log::error($err, ['gid' => $this->gid ]);

            throw new \Exception($err, 1);            
        }

        // todo: check if expunged
        if ($g->archived && !empty($g->archive_path)) {
            $err = "Gallery {$this->gid} is already archived";

            \Log::error($err, ['gid' => $this->gid ]);
            return;
        }

        $url = $this->getDownloadUrl();

        if (!$url) {
            return;
        }

        $client = new LiteDownloader;

        $downloaded = $client->download($url, $this->path);

        if (!$downloaded) {
            $error = "Error downloading file: {$url}";

            $this->retryOrDie($error, [
                'gid' => $this->gid, 'path' => $this->path, 'url' => $url
            ]);

            return;
        }

        $g->archived = true;
        $g->archive_path = $this->path;

        $g->save();
    }

    protected function getDownloadUrl()
    {
        $exhentai = new ExhentaiClient;

        # request page
        $page = $exhentai->requestArchive([
            'gid' => $this->gid, 
            'token' => $this->token, 
            'or' => $this->archiver_key
        ], 'resampled');

        if (empty($page)) {
            $error = __METHOD__ . ": Error getting archiver page";
            $this->retryOrDie($error);
            return;
        }

        // todo: check credit cost
        $crawler = new Crawler($page);

        $script = $crawler->filter('script[type]')->text() ?? '';
        
        if (empty($script)) {
            $error = __METHOD__ . ": Error getting script from archiver page";
            $this->retryOrDie($error, compact('script'));
            return;
        }

        $find = preg_match('/"(?<url>https:\/\/[^"]+)"?/iu', $script, $match);

        if (empty($match['url'])) {
            $error = __METHOD__ . ": Error getting download url";
            $this->retryOrDie($error, compact('script','match'));
            return;
        }

        return $match['url'] . '?start=1';
    }

    protected function retryOrDie(string $error, array $context=[])
    {
        if ($this->attempts() > 3) {
            throw new \Exception($error, 1);
        }

        $x = ' (' . $this->attempts() . ' attempts)';

        \Log::error($error . $x, compact('script','match'));
        $this->release(180);
    }
}
