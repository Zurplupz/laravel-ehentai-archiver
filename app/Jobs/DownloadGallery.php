<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\gallery;
use App\Http\ApiClients\LiteDownloader;
use App\Services\CreditLogging;
use App\Services\DownloadForm;
use App\Services\DownloadPage;
use App\Exceptions\InsufficientCreditsException;

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
    protected $credits;
    protected $cost;
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
        $params = [
            'gid' => $this->gid, 
            'token' => $this->token, 
            'or' => $this->archiver_key
        ];

        $this->gallery = gallery::where('gid', $this->gid)->first();

        if (empty($this->gallery)) {
            $err = "Gallery {$this->gid} doesn't exist in database";

            \Log::error($err, compact('params'));
            return;         
        }

        if ($this->gallery->archived && !empty($this->gallery->archive_path)) {
            $err = "Gallery {$this->gid} is already archived";

            \Log::error($err, compact('params'));
            return;
        }

        // todo: add cost of gallery to database
        $this->credits = new CreditLogging;

        // todo: check if expunged
        $can = $this->checkIfCanDownload($params);

        if (!$can) {
            return;
        }

        $url = $this->getDownloadUrl($params);

        if (!$url) {
            return;
        }

        $download = $this->download($url);

        if (!$download) {
            return;
        }

        $this->gallery->archived = true;
        $this->gallery->archive_path = $this->path;

        $this->gallery->save();
    }

    protected function checkIfCanDownload(array $params, string $mode='resampled') :bool
    {
        try {
            $form = new DownloadForm($params);
        }

        catch (\Exception $e) {
            if ($e->getCode() >= 400) {
                \Log::error($e->getMessage(), compact('params', 'mode'));
                return false;
            }

            $this->retryOrDie($e->getMessage(), compact('params', 'mode'));
            return false;
        }

        $disabled = $form->isResampledButtonDisabled();

        if ($disabled) {
            \Log::warning('Gallery does not have resample archive', compact('params'));

            $mode = 'original';
            $disabled = $form->isOriginalButtonDisabled();

            if ($disabled) {
                \Log::error('Gallery download buttons disabled', compact('params'));
                return false;
            }
        }

        if ($mode === 'resampled') {
            $this->cost = $form->resampledArchiveCost();
        } else {
            $this->cost = $form->originalArchiveCost();
        }

        $this->gallery->credits = $this->cost;

        if (!$this->cost) {
            return true;
        }

        try {
            $this->credits->validateTransacion($this->cost);
        }

        catch (InsufficientCreditsException $e) {
            \Log::error($e->getMessage(), compact('params'));
            return false;
        }

        catch (\Trowable $e) {
            $this->retryOrDie($e->getMessage(), compact('params'));
            return false;
        }

        return true;
    }

    protected function getDownloadUrl(array $params, string $mode='resampled') :string
    {
        try {
            $page = new DownloadPage($params, $mode);
        }

        catch (\Exception $e) {
            if ($e->getCode() >= 400) {
                \Log::error($e->getMessage(), compact('params', 'mode'));
                return '';
            }

            $this->retryOrDie($e->getMessage(), compact('params','mode'));
            return '';            
        }

        $url =  $page->getFileUrl();

        if (empty($url)) {
            $error = __METHOD__ . ": Error getting download url";
            $this->retryOrDie($error, compact('params','mode'));
            return '';
        }

        return $url;
    }

    public function download(string $url) :bool
    {
        $client = new LiteDownloader;

        $downloaded = $client->download($url, $this->path);

        if (!$downloaded) {
            $error = "Error downloading file: {$url}";

            $this->retryOrDie($error, [
                'gid' => $this->gid, 'path' => $this->path, 'url' => $url
            ]);

            return false;
        }

        $this->credits->galleryDownload(
            $this->cost, 'Downloaded gallery: ' . $this->gid
        );

        return true;
    }

    protected function retryOrDie(string $error, array $context=[])
    {
        if ($this->attempts() > 3) {
            throw new \Exception($error, 1);
        }

        $x = ' (Retrying, ' . $this->attempts() . ' out of 3 attempts)';

        \Log::error($error . $x, compact('script','match'));
        $this->release(180);
    }
}
