<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogImage;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function getBlogs(Request $request)
    {
        $query = Blog::with('tags', 'user:id,name,email');

        if ($request->has('tag')) {
            $query->byTag($request->tag);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('published_only')) {
            $query->published();
        }

        $blogs = $query->latest()->paginate($request->per_page ?? 10);

        $formattedBlogs = collect($blogs->items())->map(function ($blog) {
            return $this->formatBlog($blog);
        });

        return response()->json([
            'status' => true,
            'message' => 'Blogs retrieved successfully',
            'blogs' => $formattedBlogs,
            'pagination' => [
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
            ],
        ]);
    }

    private function formatBlog($blog)
    {
        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'content' => $blog->content,
            'published_at' => $blog->published_at ? $blog->published_at->format('Y-m-d\TH:i:s.v\Z') : null,
            'views' => $blog->views,
            'is_featured' => $blog->is_featured,
            'author' => [
                'id' => $blog->user->id,
                'name' => $blog->user->name,
            ],
            'image' => $blog->images->first()?->image_path,
            'tags' => $blog->tags->pluck('name')->toArray(),
        ];
    }

    public function getSingleBlog(Request $request, $id)
    {
        $blog = Blog::with('tags', 'user:id,name,email')->find($id);
        if (! $blog) {
            return $this->error('Blog not found', 404);
        }

        if ($request->has('increment_views')) {
            $blog->increment('views');
        }

        return $this->success('Blog retrieved successfully', $this->formatBlog($blog));
    }

    public function addBlog(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images' => 'array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_published' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:blog_tags,id',
        ]);

        $blog = new Blog;
        $blog->user_id = $request->user()->id;
        $blog->title = $request->title;
        $blog->slug = Str::slug($request->title).'-'.time();
        $blog->content = $request->content;
        $blog->is_published = $request->is_published ?? false;

        if ($request->is_published) {
            $blog->published_at = now();
        }

        $blog->save();

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $image_name = time().'-'.uniqid().'.'.$image->extension();
                $image->move(public_path('images/blog'), $image_name);
                BlogImage::create([
                    'blog_id' => $blog->id,
                    'image_path' => 'images/blog/'.$image_name,
                    'sort_order' => $index,
                ]);
            }
        }

        if ($request->has('tags')) {
            $blog->tags()->sync($request->tags);
        }

        $blog->load('images', 'tags', 'user:id,name,email');

        return $this->success('Blog added successfully', $this->formatBlog($blog));
    }

    public function updateBlog(Request $request)
    {
        $this->validate($request, [
            'blog_id' => 'required|exists:blogs,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images' => 'array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_published' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:blog_tags,id',
        ]);

        $blog = Blog::find($request->blog_id);
        if (! $blog) {
            return $this->error('Blog not found', 404);
        }

        $blog->title = $request->title;
        $blog->slug = Str::slug($request->title).'-'.time();
        $blog->content = $request->content;
        $blog->is_published = $request->is_published ?? false;

        if ($request->is_published && ! $blog->published_at) {
            $blog->published_at = now();
        }

        $blog->save();

        if ($request->hasFile('images')) {
            foreach ($blog->images as $oldImage) {
                @unlink(public_path($oldImage->image_path));
                $oldImage->delete();
            }

            foreach ($request->file('images') as $index => $image) {
                $image_name = time().'-'.uniqid().'.'.$image->extension();
                $image->move(public_path('images/blog'), $image_name);
                BlogImage::create([
                    'blog_id' => $blog->id,
                    'image_path' => 'images/blog/'.$image_name,
                    'sort_order' => $index,
                ]);
            }
        }

        if ($request->has('tags')) {
            $blog->tags()->sync($request->tags);
        }

        $blog->load('images', 'tags', 'user:id,name,email');

        return $this->success('Blog updated successfully', $this->formatBlog($blog));
    }

    public function deleteBlog(Request $request, $id)
    {
        $blog = Blog::find($id);
        if (! $blog) {
            return $this->error('Blog not found', 404);
        }

        foreach ($blog->images as $image) {
            @unlink(public_path($image->image_path));
        }

        $blog->delete();

        return $this->success('Blog deleted successfully');
    }

    public function getBlogCategories()
    {
        $categories = BlogCategory::active()->ordered()->get();

        return $this->success('Blog categories retrieved successfully', $categories);
    }

    public function addBlogCategory(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'integer',
        ]);

        $category = BlogCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'color' => $request->color,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return $this->success('Blog category added successfully', $category);
    }

    public function updateBlogCategory(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required|exists:blog_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $category = BlogCategory::find($request->category_id);
        if (! $category) {
            return $this->error('Blog category not found', 404);
        }

        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $category->description = $request->description;
        $category->color = $request->color;
        $category->sort_order = $request->sort_order ?? 0;
        $category->is_active = $request->is_active ?? true;
        $category->save();

        return $this->success('Blog category updated successfully', $category);
    }

    public function deleteBlogCategory(Request $request, $id)
    {
        $category = BlogCategory::find($id);
        if (! $category) {
            return $this->error('Blog category not found', 404);
        }

        $category->delete();

        return $this->success('Blog category deleted successfully');
    }

    public function getBlogTags()
    {
        $tags = BlogTag::all();

        return $this->success('Blog tags retrieved successfully', $tags);
    }

    public function addBlogTag(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
        ]);

        $tag = BlogTag::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return $this->success('Blog tag added successfully', $tag);
    }

    public function updateBlogTag(Request $request)
    {
        $this->validate($request, [
            'tag_id' => 'required|exists:blog_tags,id',
            'name' => 'required|string|max:255',
        ]);

        $tag = BlogTag::find($request->tag_id);
        if (! $tag) {
            return $this->error('Blog tag not found', 404);
        }

        $tag->name = $request->name;
        $tag->slug = Str::slug($request->name);
        $tag->save();

        return $this->success('Blog tag updated successfully', $tag);
    }

    public function deleteBlogTag(Request $request, $id)
    {
        $tag = BlogTag::find($id);
        if (! $tag) {
            return $this->error('Blog tag not found', 404);
        }

        $tag->delete();

        return $this->success('Blog tag deleted successfully');
    }
}
