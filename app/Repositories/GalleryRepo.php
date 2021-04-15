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
	
	function __construct()
	{
		$q = new gallery;

		$this->defineModel($q->query());	
	}

	public function gid(string $gid) :self
	{
		$this->model->where('gid', $gid);
	
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

	protected function flattenRelationships($data)
	{
		if (!$data instanceof Model) 
		{
			if (!$data instanceof Collection) {
				return $data;
			}

			$data->each(function ($model) {
				$this->flattenRelationships($model);
			});

			return $data;
		}

		$tag_list = [];

		$data->gallery_tagging->each(
			function ($relationship) use (&$tag_list) {
				if (empty($relationship->tag)) {
					return;
				}

				$id = $relationship->tag->id;

				$tag_list[$id] = $relationship->tag->name;
			}
		);

		$data->tag_list = $tag_list;

		$group_list = [];

		$data->gallery_group->each(
			function ($relationship) use (&$group_list) {
				if (empty($relationship->group)) {
					return;
				}

				$id = $relationship->group->id;

				$group_list[$id] = $relationship->group->name;
			}
		);

		$data->group_list = $group_list;

		return $data;
	}

	public function handleRequest(Request $request) :self
	{
		$data = $request->all();

		$wheres = ['rating'];
		$likes = ['gid','token','category'];

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

		return $this;
	}

	protected function reset()
	{		
		$q = new gallery;

		$this->defineModel($q->query());
	}

	public function get(bool $flatten=true)
	{
		$result = $this->model->get();

		$this->reset();

		return $flatten 
			? $this->flattenRelationships($result) 
			: $result;
	}

	public function first(bool $flatten=true)
	{
		$result = $this->model->first();

		$this->reset();

		return $flatten 
			? $this->flattenRelationships($result) 
			: $result;
	}

	public function find($id, bool $flatten=true)
	{
		$result = $this->model->find($id);

		$this->reset();

		return $flatten 
			? $this->flattenRelationships($result) 
			: $result;
	}
}