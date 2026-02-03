<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function getBanners(Request $request)
    {
        $query = Banner::query();

        if ($request->has('active_only')) {
            $query->active();
        }

        $banners = $query->ordered()->latest()->get();

        return $this->success('Banners retrieved successfully', $banners);
    }

    public function getSingleBanner(Request $request, $id)
    {
        $banner = Banner::find($id);
        if (! $banner) {
            return $this->error('Banner not found', 404);
        }

        return $this->success('Banner retrieved successfully', $banner);
    }

    public function addBanner(Request $request)
    {
        $this->validate($request, [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|url',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $banner = new Banner;
        $banner->title = $request->title;
        $banner->description = $request->description;
        $banner->link = $request->link;
        $banner->is_active = $request->is_active ?? true;
        $banner->sort_order = $request->sort_order ?? 0;

        if ($request->hasFile('image')) {
            $image_name = time().'-'.uniqid().'.'.$request->file('image')->extension();
            $request->file('image')->move(public_path('images/banner'), $image_name);
            $banner->image = 'images/banner/'.$image_name;
        }

        $banner->save();

        return $this->success('Banner added successfully', $banner);
    }

    public function updateBanner(Request $request)
    {
        $this->validate($request, [
            'banner_id' => 'required|exists:banners,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|url',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $banner = Banner::find($request->banner_id);
        if (! $banner) {
            return $this->error('Banner not found', 404);
        }

        $banner->title = $request->title ?? $banner->title;
        $banner->description = $request->description ?? $banner->description;
        $banner->link = $request->link ?? $banner->link;
        $banner->is_active = $request->is_published ?? $banner->is_active;
        $banner->sort_order = $request->sort_order ?? $banner->sort_order;

        if ($request->hasFile('image')) {
            if ($banner->image) {
                @unlink(public_path($banner->image));
            }
            $image_name = time().'-'.uniqid().'.'.$request->file('image')->extension();
            $request->file('image')->move(public_path('images/banner'), $image_name);
            $banner->image = 'images/banner/'.$image_name;
        }

        $banner->save();

        return $this->success('Banner updated successfully', $banner);
    }

    public function deleteBanner(Request $request, $id)
    {
        $banner = Banner::find($id);
        if (! $banner) {
            return $this->error('Banner not found', 404);
        }

        if ($banner->image) {
            @unlink(public_path($banner->image));
        }

        $banner->delete();

        return $this->success('Banner deleted successfully');
    }
}
