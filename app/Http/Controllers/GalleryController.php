<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\ApiClients\ExhentaiClient;
use App\Repositories\GalleryRepo;
use App\Http\Requests\ArchiveRequest;
use App\Http\Requests\GalleryRequest;
use App\Jobs\DownloadGallery;
use App\Http\Resources\GalleryResource;

class GalleryController extends Controller
{
    protected $galleries;

    function __construct()
    {
        $this->galleries = new GalleryRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(GalleryRequest $request)
    {
        $response = $this->galleries->handleRequest($request)->get();
    
        return new GalleryResource($response);
    }

    public function archiveStatus(Request $request)
    {
        $gids = $request->gids ?? [];

        $cols = ['id','gid','token','archived','archive_path'];

        $list = $this->galleries->gids($gids)->select($cols)->get();

        if (empty($list)) {
            return;
        }

        $list = $list->toArray();
        $response = [];

        foreach ($list as $gallery) {
            $status = 'pending';

            // todo: add status missing files
            if ($gallery['archived']===1 && !empty($gallery['archive_path'])) {
                $status = 'archived';
            }

            $response[$gallery['gid']] = compact('status');
        }

        return response()->json($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ArchiveRequest $request)
    {
        $data = $request->json()->all();

        $debug = $request->debug ?? false;

        $gid_token_pairs = [];
        $response = [];

        foreach ($data['galleries'] as $id => $data) {
            // lookup metadata
            $pair = gidTokenFromUrl($data['url']);

            if (!$pair) {
                $response[$id] = [
                    'status' => 'error'
                ];
                continue;
            }

            $g = $this->galleries->gid($pair[0])->first();

            if ($g) {
                $response[$id] = [
                    'status' => $g->archived ? 'archived' : 'pending'
                ];
                continue;
            }

            $gid_token_pairs[] = $pair;

        }

        if (empty($gid_token_pairs)) {
            return response()->json($response);
        }

        $exhentai = new ExhentaiClient;
            
        $r = $exhentai->getGalleriesMetadata($gid_token_pairs);

        if (empty($r)) {

            $status = $exhentai->status ?? 500;
            $error = $exhentai->lastError() ?? 'Unknown request error';

            if ($status === 302) {
                $status = 500;

                $error = 'Error requesting to exhentai.org, check user credentials and cookies';

                \Log::warning($error, compact('gid_token_pairs'));
            }

            abort($status, $error);
        }

        foreach ($r['gmetadata'] as $metadata) {
            $gid = $metadata['gid'];

            if (!empty($metadata['error'])) {

                $response[$gid] = [
                    'status' => 'not_found'
                ];

                $this->galleries->add($data['galleries'][$gid]);
            }

            $this->galleries->add($metadata);

            $response[$gid] = [
                'status' => 'pending'
            ];

            if ($debug) continue;

            DownloadGallery::dispatch([
                'gid' => $gid,
                'token' => $metadata['token'],
                'archiver_key' => $metadata['archiver_key']
            ]); 
        }

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $gallery = $this->galleries->find($id);

        return response($gallery->toJson())->header('Content-Type','application/json');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
