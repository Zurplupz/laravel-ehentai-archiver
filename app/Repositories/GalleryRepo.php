<?php

namespace App\Repositories;

use App\Repositories\BaseRepo;
use App\gallery;
use App\tag;
use App\gallery_tagging;
use Illuminate\Database\Eloquent\Collection;

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

	public function archived() :self
	{
		$this->model->where('archived', 1);

		return $this;
	}

	public function inGroup(string $name) :self
	{
		$this->model->with([
			'gallery_group.group' => function ($query) 
			{
				$query->where('name', 'like', "{$name}");
			}
		]);

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
				$gallery = $this->gid($data['gid'])->first();

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
		if (!$data instanceof Collection) {
			return $data;
		}

		$data->each(function ($model) {
			$group_list = [];

			$model->gallery_group->each(
				function ($gal_group) use (&$group_list) {
					$group_list[] = $gal_group->group->name;
				}
			);

			$model->group_list = $group_list;
		});

		return $data;
	}

	public function get()
	{
		$result = $this->model->get();

		$result = $this->flattenRelationships($result);
		
		$q = new gallery;

		$this->defineModel($q->query());

		return $result;
	}

	public function first()
	{
		$result = $this->model->first();

		$result = $this->flattenRelationships($result);
		
		$q = new gallery;

		$this->defineModel($q->query());

		return $result;
	}
}