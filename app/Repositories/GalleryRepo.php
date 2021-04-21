<?php

namespace App\Repositories;

use App\Repositories\BaseRepo;
use App\gallery;
use App\tag;
use App\gallery_tagging;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * 
 */
class GalleryRepo extends BaseRepo
{
	protected $select;
	protected $cols;
	
	function __construct()
	{
		$q = new gallery;

		$this->defineModel($q);
	}

	public function gid(string $gid) :self
	{
		$this->model->where('gid', $gid);
	
		return $this;
	}

	public function gids(array $gids) :self
	{
		$gids = array_filter($gids);

		if ($gids) {
			$this->model->whereIn('gid', $gids);
		}

		return $this;
	}

	public function archived(bool $bool=true) :self
	{
		$this->model->where('archived', $bool);

		return $this;
	}

	public function inGroup(string $name) :self
	{
		$this->model->whereHas('gallery_group.group', 
			function ($query) use ($name) {
				$query->where('name', 'like', "%{$name}%");
			}
		);

		return $this;
	}

	public function tagged(string $name) :self
	{
		$this->model->whereHas('gallery_tagging.tag', 
			function ($query) use ($name) {
				$query->where('name', 'like', "%{$name}%");
			}
		);

		return $this;
	}

	public function add(array $data)
	{
		$fields = [
			'gid' => 1,
			'token' => 1,
			'title' => 1,
            'archived' => 0,
            'archiver_key' => 0, 
            'category' => 0, 
            'thumb' => 0,
            'uploader' => 0,
            'posted' => 0,
            'filecount' => 0,
            'filesize' => 0,
            'expunged' => 0,
            'rating' => 0,
            'torrentcount' => 0,
            'tags' => 0
		];

		foreach ($data as $key => $value) {
			if (!array_key_exists($key, $fields)) {
				unset($data[$key]);
			}
		}

		foreach ($fields as $key => $require) {
			if (empty($data[$key])) {
				if ($require) {
					return false;
				}
			
				continue;
			}

			if (is_array($data[$key])) {
				continue;
			}

			if (empty($gallery)) {
				$gallery = $this->gid($data['gid'])->first(false);

				if (empty($gallery)) {
					$gallery = new gallery;
				}
			}

			$gallery->{$key} = $data[$key] ?? NULL;
		}

		$gallery->save();

		if (!empty($data['tags'])) {
			$this->addTags($gallery->id, $data['tags']);
		}

		return true;
	}

	protected function addTags(int $gallery_id, array $tags)
	{
		foreach ($tags as $tag_name) {
			if (empty($tag_name) || !is_string($tag_name)) {
				continue;
			}

			$tag = tag::where('name', $tag_name)->first();

			if (empty($tag)) {
				$tag = new tag;
				$tag->name = $tag_name;
				$tag->save();		
			
			} else {
				$exist = gallery_tagging::
					where('tag_id', $tag->id)
					->where('gallery_id', $gallery_id)
					->first();

				if (!empty($exist)) {
					continue;
				}

			}
			
			$tagging = new gallery_tagging;
			$tagging->gallery_id = $gallery_id;
			$tagging->tag_id = $tag->id;
			$tagging->save();
		}
	}

	public function handleRequest(Request $request) :self
	{
		$data = $request->all();

		$wheres = ['rating'];
		$likes = ['gid','token','category','title'];

		foreach ($data as $k => $v) {
			switch (true) {
				case in_array($k, $wheres):
					$this->model->where($k, '=', $v);
					break;

				case in_array($k, $likes):
					$this->model->where($k, 'like', "%{$v}%");
					break;

				case $k === 'tags': {
					if (strpos($v,',') !== false) {
						$tags = array_filter(explode(',', $v));
					} else {
						$tags = [$v];
					}

					foreach ($tags as $tag) $this->tagged($tag);

					break;
				}

				case $k === 'groups': {
					if (strpos($v,',') !== false) {
						$groups = array_filter(explode(',', $v));
					} else {
						$groups = [$v];
					}

					foreach ($groups as $group) $this->inGroup($group);

					break;
				}
				
				default: break;
			}
		}

		if (!empty($request->_fields)) {
			// todo: model should be an string
			// todo: getTable method should return columns
			$this->select($request->_fields);

			if (!empty($request_fields['tags']) && empty($data['tags'])) 
			{
				$this->model->with('gallery_tagging.tag');
			}

			if (!empty($request_fields['groups']) && empty($data['groups'])) 
			{
				$this->model->with('gallery_groups.group');
			}
		}

		return $this;
	}
}