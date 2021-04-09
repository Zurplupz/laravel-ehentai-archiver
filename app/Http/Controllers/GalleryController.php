<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\ApiClients\ExhentaiClient;
use App\Repositories\GalleryRepo;
use App\Http\Requests\ArchiveRequest;

class GalleryController extends Controller
{
    protected $exhentai;
    protected $galleries;

    function __construct()
    {
        $this->exhentai = new ExhentaiClient;
        $this->galleries = new GalleryRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
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
            
        $r = $this->exhentai->getGalleriesMetadata($gid_token_pairs);

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
        //
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
