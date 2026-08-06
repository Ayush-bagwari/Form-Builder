<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'slug',
        'schema',
        'settings',
        'status',
        'version',
        'ai_status',
        'ai_prompt',
    ];

    protected $casts = [
        'schema' => 'array',
        'settings' => 'array',
        'version' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = static::generateUniqueSlug($form->title ?: 'form');
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title) ?: 'form';
        $slug = $baseSlug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . Str::random(6);
            $count++;
            if ($count > 10) {
                $slug = $baseSlug . '-' . uniqid();
                break;
            }
        }

        return $slug;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function aiLogs()
    {
        return $this->hasMany(AiGenerationLog::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('forms.public', ['slug' => $this->slug]);
    }
}
