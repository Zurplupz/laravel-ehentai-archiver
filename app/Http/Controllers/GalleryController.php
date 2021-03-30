<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\ApiClients\ExhentaiClient;
use App\Repositories\GalleryRepo;

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
    public function store(Request $request)
    {
        $data = $request->json()->all();
    
        if (empty($data['galleries'])) {
            abort(400, 'no galleries given');
        }

        $gid_token_pairs = [];

        foreach ($data['galleries'] as $id => $url) {
            // lookup metadata
            $pair = gidTokenFromUrl($url);

            if (!$pair) {
                continue;
            }

            $g = $this->galleries->gid($pair[0])->archived()->first();

            if ($g) {
                continue;
            }

            $gid_token_pairs[] = $pair;

        }
            
        $r = $this->exhentai->getGalleriesMetadata($gid_token_pairs);

        foreach ($r['gmetadata'] as $metadata) {
            $this->galleries->add($metadata);
        }

        return response()->json($r);
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
